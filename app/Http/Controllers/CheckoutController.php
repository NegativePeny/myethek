<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\EscrowTransaction;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Territory;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\DokuPaymentService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected CartService $cartService;
    protected DeliveryService $deliveryService;
    protected InventoryService $inventoryService;
    protected DokuPaymentService $dokuPaymentService;

    public function __construct(
        CartService $cartService,
        DeliveryService $deliveryService,
        InventoryService $inventoryService,
        DokuPaymentService $dokuPaymentService
    ) {
        $this->cartService = $cartService;
        $this->deliveryService = $deliveryService;
        $this->inventoryService = $inventoryService;
        $this->dokuPaymentService = $dokuPaymentService;
    }

    /**
     * Tampilkan Halaman Formulir Checkout & Pemilihan Pengiriman
     */
    public function index(Request $request)
    {
        $revalidation = $this->cartService->revalidateCart();
        $items = $this->cartService->getItems();

        if (empty($items)) {
            return redirect()->route('storefront.cart')->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $merchant = $this->cartService->getMerchant();
        if (!$merchant) {
            return redirect()->route('storefront.cart')->with('error', 'Toko penyedia belum valid.');
        }

        if (!$this->cartService->meetsMinimumOrder($merchant)) {
            $requiredMin = $this->cartService->getRequiredMinimumOrder($merchant);
            $diff = $requiredMin - $this->cartService->getSubtotal();
            $labelType = (auth()->check() && auth()->user()->isB2BCustomer()) ? 'Grosir B2B Koperasi' : 'toko';

            return redirect()->route('storefront.cart')->with(
                'error',
                "Belum memenuhi minimal order {$labelType} Rp " . number_format($requiredMin, 0, ',', '.') .
                '. Kurang Rp ' . number_format($diff, 0, ',', '.')
            );
        }

        $customer = auth()->user();
        $savedAddresses = $customer ? $customer->addresses()->with('territory')->orderByDesc('is_default')->get() : collect();

        $activeVillageId = session('active_village_id', $customer?->territory_id);
        if ($activeVillageId && !session('active_village_id')) {
            session(['active_village_id' => $activeVillageId]);
        }
        $currentTerritory = Territory::with('parent')->find($activeVillageId);

        $customerLat = $currentTerritory?->latitude ?? -8.109300;
        $customerLon = $currentTerritory?->longitude ?? 112.189400;
        $merchantLat = $merchant->latitude ?? -8.098254;
        $merchantLon = $merchant->longitude ?? 112.168153;

        $distanceKm = $this->deliveryService->calculateDistance(
            $merchantLat,
            $merchantLon,
            $customerLat,
            $customerLon
        );

        $subtotal = $this->cartService->getSubtotal();
        $defaultDelivery = config('myethek.pilot_mode', true) ? 'kurirq' : 'ojol';
        $selectedDeliveryType = $request->query('delivery', $defaultDelivery);

        $costBreakdown = $this->deliveryService->calculateTotal(
            $subtotal,
            $selectedDeliveryType,
            $distanceKm
        );

        $availableVillages = Territory::where('level', 'village')
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('storefront.checkout', [
            'items' => $items,
            'merchant' => $merchant,
            'currentTerritory' => $currentTerritory,
            'distanceKm' => $distanceKm,
            'costBreakdown' => $costBreakdown,
            'selectedDeliveryType' => $selectedDeliveryType,
            'customer' => $customer,
            'savedAddresses' => $savedAddresses,
            'availableVillages' => $availableVillages,
            'cartWarnings' => $revalidation['warnings'] ?? [],
        ]);
    }

    /**
     * Proses Penyimpanan Pesanan, Reservasi Stok, dan Inisiasi Pembayaran DOKU
     */
    public function store(Request $request)
    {
        $revalidation = $this->cartService->revalidateCart();
        if (!$revalidation['valid']) {
            return redirect()->route('storefront.cart')->with(
                'error',
                'Terdapat produk yang habis: ' . implode(', ', $revalidation['out_of_stock']) . '. Silakan sesuaikan keranjang Anda.'
            );
        }

        $items = $this->cartService->getItems();
        if (empty($items)) {
            return redirect()->route('storefront.cart')->with('error', 'Keranjang belanja kosong.');
        }

        $merchant = $this->cartService->getMerchant();
        if (!$merchant) {
            return redirect()->route('storefront.cart')->with('error', 'Toko tidak valid.');
        }

        // Validasi minimum order B2B Rp1.000.000 sebelum ongkir & diskon (B2B-003, BR-004)
        if (!$this->cartService->meetsMinimumOrder($merchant)) {
            $requiredMin = $this->cartService->getRequiredMinimumOrder($merchant);
            return redirect()->route('storefront.cart')->with(
                'error',
                'Nilai barang belum mencapai batas minimum order Rp ' . number_format($requiredMin, 0, ',', '.')
            );
        }

        $validated = $request->validate([
            'address_id' => 'nullable|exists:user_addresses,id',
            'recipient_name' => 'required_without:address_id|nullable|string|max:100',
            'phone' => 'required_without:address_id|nullable|string|max:25',
            'shipping_address' => 'required_without:address_id|nullable|string|max:500',
            'territory_id' => 'nullable|exists:territories,id',
            'delivery_type' => 'required|in:ojol,kurirq,merchant_courier,self_pickup',
            'payment_method' => 'required|in:qris,shopeepay,va_bri,va_bni,va_mandiri,cod',
            'notes' => 'nullable|string|max:500',
        ]);

        $customer = auth()->user();

        // Resolusi Alamat Tersimpan vs Input Penerima Baru (CUS-003, CUS-005)
        $recipientName = $validated['recipient_name'] ?? $customer?->name ?? 'Pelanggan';
        $recipientPhone = $validated['phone'] ?? $customer?->phone ?? '08160000001';
        $shippingAddress = $validated['shipping_address'] ?? $customer?->address ?? '';
        $activeVillageId = $validated['territory_id'] ?? session('active_village_id', $customer?->territory_id);

        if (!empty($validated['address_id']) && $customer) {
            $userAddr = UserAddress::where('user_id', $customer->id)->find($validated['address_id']);
            if ($userAddr) {
                $recipientName = $userAddr->recipient_name;
                $recipientPhone = $userAddr->recipient_phone;
                $shippingAddress = $userAddr->full_address;
                $activeVillageId = $userAddr->territory_id ?? $activeVillageId;
            }
        }

        $currentTerritory = Territory::find($activeVillageId);

        // Validasi area layanan Kota Blitar pada mode Pilot (CUS-004)
        if (config('myethek.pilot_mode', false) && $currentTerritory && !$this->deliveryService->isWithinBlitarServiceArea($currentTerritory)) {
            return redirect()->back()->with('error', 'Alamat pengiriman berada di luar area layanan Pilot Kota Blitar.');
        }

        $customerLat = $currentTerritory?->latitude ?? -8.109300;
        $customerLon = $currentTerritory?->longitude ?? 112.189400;
        $merchantLat = $merchant->latitude ?? -8.098254;
        $merchantLon = $merchant->longitude ?? 112.168153;

        $distanceKm = $this->deliveryService->calculateDistance(
            $merchantLat,
            $merchantLon,
            $customerLat,
            $customerLon
        );

        $subtotal = $this->cartService->getSubtotal();
        $costs = $this->deliveryService->calculateTotal(
            $subtotal,
            $validated['delivery_type'],
            $distanceKm
        );

        if (!$customer) {
            $customer = User::where('phone', $recipientPhone)->first();
            if (!$customer) {
                $customer = User::create([
                    'name' => $recipientName,
                    'email' => 'cust_' . time() . '_' . Str::random(4) . '@myethek.local',
                    'phone' => $recipientPhone,
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'customer',
                    'territory_id' => $activeVillageId,
                    'address' => $shippingAddress,
                ]);
            }
        }

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $isPilot = (bool) config('myethek.pilot_mode', false);
        $customerChannel = $customer->isB2BCustomer() ? 'b2b' : 'b2c';

        DB::transaction(function () use (
            $orderNumber,
            $customer,
            $merchant,
            $costs,
            $validated,
            $items,
            $customerLat,
            $customerLon,
            $recipientName,
            $recipientPhone,
            $shippingAddress,
            $isPilot,
            $customerChannel
        ) {
            // 1. Simpan Order (Status awal pending; pembayaran DOKU diverifikasi melalui backend webhook - PAY-003, BR-006)
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'merchant_id' => $merchant->id,
                'order_type' => 'retail',
                'customer_channel' => $customerChannel,
                'status' => 'pending',
                'packing_status' => 'not_started',
                'subtotal' => $costs['subtotal'],
                'discount_amount' => $costs['discount_amount'] ?? 0,
                'shipping_fee' => $costs['shipping_fee'],
                'platform_fee' => $costs['platform_fee'],
                'handling_cost' => (float) config('myethek.finance.handling_cost_per_order', 1000),
                'total_amount' => $costs['total_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'shipping_address' => $shippingAddress,
                'shipping_latitude' => $customerLat,
                'shipping_longitude' => $customerLon,
            ]);

            // 2. Simpan Order Items
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'] ?? 0,
                    'subtotal' => $item['subtotal'],
                ]);

                if (!$isPilot) {
                    $inventory = MerchantInventory::where('merchant_id', $merchant->id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if ($inventory) {
                        $inventory->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Pada mode Pilot, gunakan sistem Reservasi Stok Gudang (WH-007, WH-008)
            if ($isPilot) {
                $order->load('items');
                $this->inventoryService->reserveStockForOrder($order, $customer);
            }

            // 3. Simpan Delivery Logistics dengan OTP Dinamis + Hash + Expiry (BR-010, SOT 10.1)
            $randomOtp = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $otpExpiryMinutes = (int) config('myethek.otp.expiry_minutes', 180);

            Delivery::create([
                'order_id' => $order->id,
                'delivery_type' => $validated['delivery_type'],
                'courier_provider' => config('myethek.shipping.provider', 'KurirQ'),
                'distance_km' => $costs['distance_km'],
                'fee' => $costs['shipping_fee'],
                'receiver_name' => $recipientName,
                'completion_otp' => $randomOtp,
                'otp_hash' => Hash::make($randomOtp),
                'otp_expires_at' => now()->addMinutes($otpExpiryMinutes),
                'otp_attempts' => 0,
                'status' => 'pending',
            ]);

            // 4. Buat Payment Record DOKU (PAY-001, PAY-002)
            if ($validated['payment_method'] !== 'cod') {
                $this->dokuPaymentService->createPaymentForOrder($order, $validated['payment_method']);
            }

            // 5. Catat Ledger / Escrow Record
            EscrowTransaction::create([
                'order_id' => $order->id,
                'gross_amount' => $costs['total_amount'],
                'merchant_portion' => $costs['subtotal'],
                'driver_portion' => $costs['shipping_fee'],
                'platform_portion' => $costs['platform_fee'],
                'status' => 'held',
            ]);
        });

        $this->cartService->clear();

        return redirect()->route('storefront.orderSuccess', $orderNumber);
    }

    /**
     * Halaman Pesanan Berhasil, Instruksi Pembayaran DOKU, & Pelacakan Milestone Logistik
     */
    public function orderSuccess(string $orderNumber)
    {
        $order = Order::with(['merchant.territory', 'items.product', 'delivery', 'customer', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $waItemsList = '';
        foreach ($order->items as $i) {
            $waItemsList .= "- {$i->product_name} ({$i->quantity} {$i->unit_name}) = Rp " . number_format($i->subtotal, 0, ',', '.') . '%0A';
        }

        $waText = "Halo {$order->merchant->store_name}, saya {$order->customer->name} telah memesan di Mak Ethek.%0A" .
            "No Pesanan: {$order->order_number}%0A" .
            "Penerima: " . ($order->recipient_name ?? $order->customer->name) . "%0A" .
            "Rincian:%0A{$waItemsList}" .
            'Total Bayar: Rp ' . number_format($order->total_amount, 0, ',', '.') . '%0A' .
            'Metode: ' . strtoupper($order->delivery->delivery_type ?? 'KURIRQ') . '%0A' .
            "Alamat: {$order->shipping_address}%0A" .
            'Mohon segera dikonfirmasi dan disiapkan. Terima kasih!';

        $cleanMerchantPhone = preg_replace('/[^0-9]/', '', $order->merchant->phone ?? '08120000001');
        if (str_starts_with($cleanMerchantPhone, '0')) {
            $cleanMerchantPhone = '62' . substr($cleanMerchantPhone, 1);
        }

        $waLink = "https://wa.me/{$cleanMerchantPhone}?text={$waText}";

        return view('storefront.order-success', [
            'order' => $order,
            'waLink' => $waLink,
        ]);
    }

    /**
     * Endpoint Resmi Webhook DOKU (PAY-003, PAY-004, BR-006)
     */
    public function dokuWebhook(Request $request)
    {
        $payload = $request->only(['payment_reference', 'transaction_status', 'amount', 'order_number']);
        $signature = (string) $request->header('Signature', '');
        $timestamp = (string) $request->header('Request-Timestamp', '2026-11-09T12:00:00Z');
        $requestId = (string) $request->header('Request-Id', 'REQ-001');

        $result = $this->dokuPaymentService->handleWebhook($payload, $signature, $timestamp, $requestId);

        return response()->json($result, $result['http_code'] ?? 200);
    }

    /**
     * Simulator Pembayaran DOKU Sandbox (Memudahkan Pengujian UAT Pembayaran Berhasil / Kedaluwarsa)
     */
    public function simulateDokuCallback(Request $request, string $orderNumber)
    {
        $order = Order::with('payment')->where('order_number', $orderNumber)->firstOrFail();
        $payment = $order->payment;

        if (!$payment) {
            return redirect()->back()->with('error', 'Data pembayaran DOKU tidak ditemukan untuk pesanan ini.');
        }

        $simStatus = strtoupper($request->input('status', 'SUCCESS'));
        $payload = [
            'payment_reference' => $payment->payment_reference,
            'order_number' => $order->order_number,
            'transaction_status' => $simStatus,
            'amount' => (float) $payment->amount,
        ];

        $timestamp = now()->toIso8601ZuluString();
        $requestId = 'SIM-' . strtoupper(Str::random(8));
        $signature = $this->dokuPaymentService->generateSignature($payload, $timestamp, $requestId);

        $result = $this->dokuPaymentService->handleWebhook($payload, $signature, $timestamp, $requestId);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
