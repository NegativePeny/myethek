<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\EscrowTransaction;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Territory;
use App\Models\User;
use App\Services\CartService;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected CartService $cartService;
    protected DeliveryService $deliveryService;

    public function __construct(CartService $cartService, DeliveryService $deliveryService)
    {
        $this->cartService = $cartService;
        $this->deliveryService = $deliveryService;
    }

    /**
     * Tampilkan Halaman Formulir Checkout & Pemilihan Pengiriman
     */
    public function index(Request $request)
    {
        $items = $this->cartService->getItems();
        if (empty($items)) {
            return redirect()->route('storefront.cart')->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $merchant = $this->cartService->getMerchant();
        if (!$merchant) {
            return redirect()->route('storefront.cart')->with('error', 'Toko penyedia belum valid.');
        }

        if (!$this->cartService->meetsMinimumOrder($merchant)) {
            $diff = $merchant->min_order_amount - $this->cartService->getSubtotal();
            return redirect()->route('storefront.cart')->with('error', 
                'Belum memenuhi minimal order toko Rp ' . number_format($merchant->min_order_amount, 0, ',', '.') . 
                '. Kurang Rp ' . number_format($diff, 0, ',', '.'));
        }

        $customer = auth()->user();

        // Ambil wilayah customer aktif (prioritaskan pilihan sesi atau domisili user)
        $activeVillageId = session('active_village_id', $customer?->territory_id);
        if ($activeVillageId && !session('active_village_id')) {
            session(['active_village_id' => $activeVillageId]);
        }
        $currentTerritory = Territory::with('parent')->find($activeVillageId);

        // Hitung estimasi jarak ke toko mitra
        $customerLat = $currentTerritory?->latitude ?? -8.109300;
        $customerLon = $currentTerritory?->longitude ?? 112.189400;
        $merchantLat = $merchant->latitude ?? -8.109156;
        $merchantLon = $merchant->longitude ?? 112.189544;

        $distanceKm = $this->deliveryService->calculateDistance(
            $merchantLat, $merchantLon,
            $customerLat, $customerLon
        );

        $subtotal = $this->cartService->getSubtotal();
        $selectedDeliveryType = $request->query('delivery', 'ojol');

        $costBreakdown = $this->deliveryService->calculateTotal(
            $subtotal,
            $selectedDeliveryType,
            $distanceKm
        );

        return view('storefront.checkout', [
            'items' => $items,
            'merchant' => $merchant,
            'currentTerritory' => $currentTerritory,
            'distanceKm' => $distanceKm,
            'costBreakdown' => $costBreakdown,
            'selectedDeliveryType' => $selectedDeliveryType,
            'customer' => $customer,
        ]);
    }

    /**
     * Proses Penyimpanan Pesanan dan Escrow Holding Koperasi
     */
    public function store(Request $request)
    {
        $items = $this->cartService->getItems();
        if (empty($items)) {
            return redirect()->route('storefront.cart')->with('error', 'Keranjang belanja kosong.');
        }

        $merchant = $this->cartService->getMerchant();
        if (!$merchant) {
            return redirect()->route('storefront.cart')->with('error', 'Toko tidak valid.');
        }

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'delivery_type' => 'required|in:ojol,merchant_courier,self_pickup',
            'payment_method' => 'required|in:qris,va_bri,va_bni,va_mandiri,cod',
            'notes' => 'nullable|string|max:500',
        ]);

        // Wilayah customer aktif
        $activeVillageId = session('active_village_id');
        $currentTerritory = Territory::find($activeVillageId);

        $customerLat = $currentTerritory?->latitude ?? -8.109300;
        $customerLon = $currentTerritory?->longitude ?? 112.189400;
        $merchantLat = $merchant->latitude ?? -8.109156;
        $merchantLon = $merchant->longitude ?? 112.189544;

        $distanceKm = $this->deliveryService->calculateDistance(
            $merchantLat, $merchantLon,
            $customerLat, $customerLon
        );

        $subtotal = $this->cartService->getSubtotal();
        $costs = $this->deliveryService->calculateTotal(
            $subtotal,
            $validated['delivery_type'],
            $distanceKm
        );

        // Ambil atau buat akun customer untuk transaksi
        if (auth()->check()) {
            $customer = auth()->user();
        } else {
            $customer = User::where('phone', $validated['phone'])->first();
            if (!$customer) {
                $customer = User::create([
                    'name' => $validated['recipient_name'],
                    'email' => 'cust_' . time() . '_' . Str::random(4) . '@myethek.local',
                    'phone' => $validated['phone'],
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'customer',
                    'territory_id' => $activeVillageId,
                    'address' => $validated['shipping_address'],
                ]);
            }
        }

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        // Eksekusi Transaction
        DB::transaction(function () use (
            $orderNumber, $customer, $merchant, $costs, $validated, $items, $customerLat, $customerLon
        ) {
            // 1. Simpan Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'order_type' => 'retail',
                'status' => 'pending',
                'subtotal' => $costs['subtotal'],
                'shipping_fee' => $costs['shipping_fee'],
                'platform_fee' => $costs['platform_fee'],
                'total_amount' => $costs['total_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => ($validated['payment_method'] === 'cod') ? 'unpaid' : 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'shipping_latitude' => $customerLat,
                'shipping_longitude' => $customerLon,
            ]);

            // 2. Simpan Order Items & Kurangi Stok Toko
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Pengurangan stok inventori merchant
                $inventory = MerchantInventory::where('merchant_id', $merchant->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($inventory) {
                    $inventory->decrement('stock', $item['quantity']);
                }
            }

            // 3. Simpan Delivery Logistics
            $randomOtp = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            Delivery::create([
                'order_id' => $order->id,
                'delivery_type' => $validated['delivery_type'],
                'distance_km' => $costs['distance_km'],
                'fee' => $costs['shipping_fee'],
                'completion_otp' => $randomOtp,
                'status' => 'pending',
            ]);

            // 4. Catat Escrow Holding Transaksi Koperasi
            EscrowTransaction::create([
                'order_id' => $order->id,
                'gross_amount' => $costs['total_amount'],
                'merchant_portion' => $costs['subtotal'],
                'driver_portion' => $costs['shipping_fee'],
                'platform_portion' => $costs['platform_fee'],
                'status' => 'held',
            ]);
        });

        // Kosongkan keranjang belanja
        $this->cartService->clear();

        return redirect()->route('storefront.orderSuccess', $orderNumber);
    }

    /**
     * Halaman Pesanan Berhasil & Pelacakan Milestone Logistik
     */
    public function orderSuccess(string $orderNumber)
    {
        $order = Order::with(['merchant.territory', 'items.product', 'delivery', 'customer'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Format pesan WhatsApp ke Warung Mitra
        $waItemsList = "";
        foreach ($order->items as $i) {
            $waItemsList .= "- {$i->product_name} ({$i->quantity} {$i->unit_name}) = Rp " . number_format($i->subtotal, 0, ',', '.') . "%0A";
        }

        $waText = "Halo {$order->merchant->store_name}, saya {$order->customer->name} telah memesan di Mak Ethek.%0A" .
            "No Pesanan: {$order->order_number}%0A" .
            "Rincian:%0A{$waItemsList}" .
            "Total Bayar: Rp " . number_format($order->total_amount, 0, ',', '.') . "%0A" .
            "Metode: " . strtoupper($order->delivery->delivery_type) . "%0A" .
            "Alamat: {$order->shipping_address}%0A" .
            "Mohon segera dikonfirmasi dan disiapkan. Terima kasih!";

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
}
