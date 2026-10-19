<?php

namespace App\Http\Controllers;

use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletMutation;
use App\Services\EscrowService;
use App\Services\ProcurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantDashboardController extends Controller
{
    /**
     * Dapatkan mitra aktif yang sedang login di portal merchant.
     */
    protected function getActiveMerchant(): Merchant
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Cek apakah ada switcher session aktif (untuk demo atau multi-tier holding)
            $sessionMerchantId = session('merchant_portal_id');
            if ($sessionMerchantId && ($user->isAdmin() || $user->isMerchant())) {
                $merchant = Merchant::with(['territory', 'user'])->find($sessionMerchantId);
                if ($merchant) {
                    return $merchant;
                }
            }

            // Jika user memiliki toko mitra langsung
            if ($user->merchant) {
                session(['merchant_portal_id' => $user->merchant->id]);
                return $user->merchant->load(['territory', 'user']);
            }
        }

        $sessionMerchantId = session('merchant_portal_id');
        if ($sessionMerchantId) {
            $merchant = Merchant::with(['territory', 'user'])->find($sessionMerchantId);
            if ($merchant) {
                return $merchant;
            }
        }

        // Fallback default: Sub-Agen Gedog
        $defaultMerchant = Merchant::with(['territory', 'user'])
            ->where('slug', 'warung-berkah-mak-ethek-gedog')
            ->first() ?? Merchant::firstOrFail();

        session(['merchant_portal_id' => $defaultMerchant->id]);
        return $defaultMerchant;
    }

    /**
     * Dashboard Utama Portal Toko Mitra
     */
    public function index()
    {
        $merchant = $this->getActiveMerchant();

        // Saldo Dompet Toko
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $merchant->user_id],
            ['balance_available' => 0, 'balance_pending' => 0]
        );

        // Metrik Omset & Transaksi
        $todayRevenue = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $pendingOrdersCount = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->where('status', 'pending')
            ->count();

        $activeOrdersCount = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->whereIn('status', ['confirmed', 'processing', 'ready_to_ship', 'shipping'])
            ->count();

        // 5 Pesanan Retail Masuk Terbaru
        $recentOrders = Order::with(['items.product', 'delivery', 'customer'])
            ->where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->latest()
            ->take(5)
            ->get();

        // Inventori dengan stok menipis (<= 10)
        $lowStocks = MerchantInventory::with('product')
            ->where('merchant_id', $merchant->id)
            ->where('stock', '<=', 10)
            ->get();

        // Daftar seluruh merchant untuk switch demo
        $allMerchants = Merchant::with('territory')->get();

        return view('merchant.index', [
            'merchant' => $merchant,
            'wallet' => $wallet,
            'todayRevenue' => $todayRevenue,
            'pendingOrdersCount' => $pendingOrdersCount,
            'activeOrdersCount' => $activeOrdersCount,
            'recentOrders' => $recentOrders,
            'lowStocks' => $lowStocks,
            'allMerchants' => $allMerchants,
        ]);
    }

    /**
     * Halaman Riwayat & Manajemen Pesanan Toko
     */
    public function orders(Request $request)
    {
        $merchant = $this->getActiveMerchant();
        $status = $request->query('status', 'all');
        $search = $request->query('q');

        $query = Order::with(['items.product', 'delivery.driver', 'customer'])
            ->where('merchant_id', $merchant->id)
            ->where('order_type', 'retail');

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'processing') {
            $query->whereIn('status', ['confirmed', 'processing', 'ready_to_ship']);
        } elseif ($status === 'shipping') {
            $query->where('status', 'shipping');
        } elseif ($status === 'completed') {
            $query->where('status', 'delivered');
        } elseif ($status === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => Order::where('merchant_id', $merchant->id)->where('order_type', 'retail')->count(),
            'pending' => Order::where('merchant_id', $merchant->id)->where('order_type', 'retail')->where('status', 'pending')->count(),
            'processing' => Order::where('merchant_id', $merchant->id)->where('order_type', 'retail')->whereIn('status', ['confirmed', 'processing', 'ready_to_ship'])->count(),
            'completed' => Order::where('merchant_id', $merchant->id)->where('order_type', 'retail')->where('status', 'delivered')->count(),
            'cancelled' => Order::where('merchant_id', $merchant->id)->where('order_type', 'retail')->where('status', 'cancelled')->count(),
        ];

        return view('merchant.orders', [
            'merchant' => $merchant,
            'orders' => $orders,
            'activeStatus' => $status,
            'counts' => $counts,
            'search' => $search,
            'allMerchants' => Merchant::with('territory')->get(),
        ]);
    }

    /**
     * Halaman Laporan Keuangan, Omset, dan Mutasi Saldo
     */
    public function finance()
    {
        $merchant = $this->getActiveMerchant();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $merchant->user_id],
            ['balance_available' => 0, 'balance_pending' => 0]
        );

        $todayRevenue = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $monthlyRevenue = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $totalRevenue = Order::where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $pendingEscrow = EscrowTransaction::whereHas('order', fn ($q) => $q->where('merchant_id', $merchant->id))
            ->where('status', 'held')
            ->sum('merchant_portion');

        $releasedEscrow = EscrowTransaction::whereHas('order', fn ($q) => $q->where('merchant_id', $merchant->id))
            ->where('status', 'released')
            ->sum('merchant_portion');

        $mutations = WalletMutation::where('wallet_id', $wallet->id)
            ->latest()
            ->take(20)
            ->get();

        return view('merchant.finance', [
            'merchant' => $merchant,
            'wallet' => $wallet,
            'todayRevenue' => $todayRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'totalRevenue' => $totalRevenue,
            'pendingEscrow' => $pendingEscrow,
            'releasedEscrow' => $releasedEscrow,
            'mutations' => $mutations,
            'allMerchants' => Merchant::with('territory')->get(),
        ]);
    }

    /**
     * Halaman Pengaturan Profil Toko Mitra
     */
    public function profile()
    {
        $merchant = $this->getActiveMerchant();

        return view('merchant.profile', [
            'merchant' => $merchant,
            'allMerchants' => Merchant::with('territory')->get(),
        ]);
    }

    /**
     * Simpan Perubahan Profil & Pengaturan Toko
     */
    public function updateProfile(Request $request)
    {
        $merchant = $this->getActiveMerchant();

        $validated = $request->validate([
            'store_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'min_order_amount' => 'required|numeric|min:0',
            'delivery_radius_km' => 'required|numeric|min:0.5|max:50',
            'opening_time' => 'required|string',
            'closing_time' => 'required|string',
            'self_delivery_active' => 'nullable|boolean',
        ], [
            'store_name.required' => 'Nama warung / toko wajib diisi.',
            'phone.required' => 'Nomor WhatsApp toko wajib diisi.',
            'address.required' => 'Alamat fisik toko wajib diisi.',
            'min_order_amount.required' => 'Minimal nominal belanja wajib ditentukan.',
            'delivery_radius_km.required' => 'Radius pengantaran wajib diisi.',
        ]);

        $merchant->update([
            'store_name' => $validated['store_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'min_order_amount' => $validated['min_order_amount'],
            'delivery_radius_km' => $validated['delivery_radius_km'],
            'opening_time' => $validated['opening_time'],
            'closing_time' => $validated['closing_time'],
            'self_delivery_active' => $request->has('self_delivery_active'),
        ]);

        return back()->with('success', 'Profil dan operasional toko berhasil diperbarui.');
    }

    /**
     * Ganti akun mitra di dashboard untuk simulasi multi-tier
     */
    public function switchMerchant(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
        ]);

        session(['merchant_portal_id' => $validated['merchant_id']]);
        $m = Merchant::find($validated['merchant_id']);

        return redirect()->back()->with('success', "Beralih ke portal toko: {$m->store_name} ({$m->tier})");
    }

    /**
     * Buka / Tutup Toko Mitra
     */
    public function toggleStatus()
    {
        $merchant = $this->getActiveMerchant();
        $merchant->update(['is_open' => !$merchant->is_open]);

        $statusText = $merchant->is_open ? 'BUKA' : 'TUTUP SEMENTARA';
        return redirect()->back()->with('success', "Status toko {$merchant->store_name} diubah menjadi {$statusText}.");
    }

    /**
     * Update Milestone Status Pesanan & Penyelesaian Escrow dengan OTP
     */
    public function updateOrderStatus(Request $request, int $orderId, EscrowService $escrowService)
    {
        $merchant = $this->getActiveMerchant();
        $order = Order::with(['delivery', 'escrow', 'merchant.user'])
            ->where('merchant_id', $merchant->id)
            ->findOrFail($orderId);

        $action = $request->input('action');

        switch ($action) {
            case 'confirm':
                $order->update(['status' => 'processing']);
                if ($order->delivery) {
                    $order->delivery->update(['status' => 'dispatched']);
                }
                return redirect()->back()->with('success', "Pesanan {$order->order_number} dikonfirmasi & sedang dikemas.");

            case 'ready':
                $order->update(['status' => 'ready_to_ship']);
                return redirect()->back()->with('success', "Pesanan {$order->order_number} telah siap diambil/diantar kurir.");

            case 'complete':
                $otp = $request->input('otp');
                $result = $escrowService->releaseEscrowForOrder($order, $otp);

                if ($result['success']) {
                    return redirect()->back()->with('success', $result['message']);
                }
                return redirect()->back()->with('error', $result['message']);

            default:
                return redirect()->back()->with('error', 'Aksi tidak dikenal.');
        }
    }

    /**
     * Halaman Modul Kulakan Grosir B2B Berjenjang
     */
    public function procurement(ProcurementService $procurementService)
    {
        $merchant = $this->getActiveMerchant();
        $supplier = $procurementService->resolveSupplierForMerchant($merchant);

        $catalog = [];
        if ($supplier) {
            $catalog = $procurementService->getWholesaleCatalogForBuyer($merchant, $supplier);
        }

        $procurementOrders = Order::with(['items.product', 'merchant.territory'])
            ->where('customer_id', $merchant->user_id)
            ->where('order_type', 'procurement')
            ->latest()
            ->get();

        return view('merchant.procurement', [
            'merchant' => $merchant,
            'supplier' => $supplier,
            'catalog' => $catalog,
            'procurementOrders' => $procurementOrders,
            'allMerchants' => Merchant::with('territory')->get(),
        ]);
    }

    /**
     * Proses Pembuatan Order Kulakan B2B
     */
    public function orderProcurement(Request $request, ProcurementService $procurementService)
    {
        $merchant = $this->getActiveMerchant();
        $supplier = $procurementService->resolveSupplierForMerchant($merchant);

        if (!$supplier) {
            return redirect()->back()->with('error', 'Mitra pemasok resmi wilayah Anda tidak ditemukan.');
        }

        $items = $request->input('items', []);
        $formattedItems = [];

        foreach ($items as $productId => $qty) {
            if ((int) $qty > 0) {
                $formattedItems[] = [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $qty,
                ];
            }
        }

        $result = $procurementService->createProcurementOrder($merchant, $supplier, $formattedItems);

        if ($result['success']) {
            return redirect()->route('merchant.procurement')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Konfirmasi Penerimaan Barang Kulakan (Tambah Stok Otomatis)
     */
    public function receiveProcurement(int $orderId, ProcurementService $procurementService)
    {
        $merchant = $this->getActiveMerchant();
        $order = Order::with('items')->where('customer_id', $merchant->user_id)->findOrFail($orderId);

        $result = $procurementService->receiveProcurementOrder($order, $merchant);

        if ($result['success']) {
            return redirect()->route('merchant.procurement')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Halaman Inventori & Manajemen Harga Eceran Toko
     */
    public function inventory()
    {
        $merchant = $this->getActiveMerchant();

        $inventories = MerchantInventory::with('product.category')
            ->where('merchant_id', $merchant->id)
            ->get();

        return view('merchant.inventory', [
            'merchant' => $merchant,
            'inventories' => $inventories,
            'allMerchants' => Merchant::with('territory')->get(),
        ]);
    }

    /**
     * Perbarui Harga Eceran Toko atau Ketersediaan
     */
    public function updateInventory(Request $request, int $inventoryId)
    {
        $merchant = $this->getActiveMerchant();
        $inv = MerchantInventory::where('merchant_id', $merchant->id)->findOrFail($inventoryId);

        $validated = $request->validate([
            'custom_price' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
        ]);

        $inv->update([
            'custom_price' => $validated['custom_price'] > 0 ? $validated['custom_price'] : null,
            'is_available' => $request->has('is_available'),
        ]);

        return redirect()->back()->with('success', "Inventori {$inv->product->name} berhasil diperbarui.");
    }
}
