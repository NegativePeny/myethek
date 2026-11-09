<?php

namespace App\Http\Controllers;

use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Territory;
use App\Models\User;
use App\Models\WalletMutation;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminOverviewController extends Controller
{
    protected AuditService $auditService;
    protected InventoryService $inventoryService;

    public function __construct(AuditService $auditService, InventoryService $inventoryService)
    {
        $this->auditService = $auditService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dashboard Web Desktop Admin Koperasi & Super Admin (ADM-001 s/d ADM-008)
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'all');

        // 1. Metrik Finansial
        $retailGmv = (float) Order::where('order_type', 'retail')->whereNotIn('status', ['cancelled', 'refunded'])->sum('total_amount');
        $procurementGmv = (float) Order::where('order_type', 'procurement')->whereNotIn('status', ['cancelled', 'refunded'])->sum('total_amount');

        $escrowHeld = (float) EscrowTransaction::where('status', 'held')->sum('gross_amount');
        $escrowReleased = (float) EscrowTransaction::where('status', 'released')->sum('gross_amount');
        $platformFeeTotal = (float) EscrowTransaction::where('status', 'released')->sum('platform_portion');

        $b2cOrdersCount = Order::where('order_type', 'retail')->where('customer_channel', 'b2c')->whereNotIn('status', ['cancelled', 'refunded'])->count();
        $b2bOrdersCount = Order::where('order_type', 'retail')->where('customer_channel', 'b2b')->whereNotIn('status', ['cancelled', 'refunded'])->count();
        $settledPaymentsTotal = (float) Payment::where('status', 'paid')->sum('amount');

        // 2. Metrik Jaringan Wilayah & Operasional Gudang
        $pilotSlug = config('myethek.pilot_merchant_slug', 'gudang-pusat-koperasi-kota-blitar');
        $pilotMerchant = Merchant::where('slug', $pilotSlug)->first() ?? Merchant::first();
        $lowStockItems = $pilotMerchant
            ? MerchantInventory::with('product')->where('merchant_id', $pilotMerchant->id)->get()->filter(fn ($i) => $i->available_stock <= $i->reorder_level)
            : collect();

        $counts = [
            'total_villages' => Territory::where('level', 'village')->count(),
            'total_districts' => Territory::where('level', 'district')->count(),
            'total_regencies' => Territory::where('level', 'regency')->count(),
            'sub_agents' => Merchant::where('tier', 'sub_agent')->count(),
            'agents' => Merchant::where('tier', 'agent')->count(),
            'distributors' => Merchant::where('tier', 'distributor')->count(),
            'drivers' => User::where('role', 'driver')->count(),
            'customers' => User::where('role', 'customer')->count(),
            'b2b_customers' => User::where('role', 'customer')->where('customer_type', 'b2b')->count(),
            'low_stock_count' => $lowStockItems->count(),
        ];

        // 3. Mutasi Dompet / Ledger Terakhir
        $latestMutations = WalletMutation::with('wallet.user')
            ->latest()
            ->take(15)
            ->get();

        // 4. Daftar Pesanan & Filter (ADM-001, ADM-002, ADM-007)
        $ordersQuery = Order::with(['merchant.territory', 'customer', 'items', 'delivery.driver', 'payment']);
        if ($statusFilter !== 'all') {
            $ordersQuery->where('status', $statusFilter);
        }
        $recentOrders = $ordersQuery->latest()->take(20)->get();

        $drivers = User::where('role', 'driver')->where('is_active', true)->get();

        return view('admin.index', [
            'retailGmv' => $retailGmv,
            'procurementGmv' => $procurementGmv,
            'escrowHeld' => $escrowHeld,
            'escrowReleased' => $escrowReleased,
            'platformFeeTotal' => $platformFeeTotal,
            'b2cOrdersCount' => $b2cOrdersCount,
            'b2bOrdersCount' => $b2bOrdersCount,
            'settledPaymentsTotal' => $settledPaymentsTotal,
            'counts' => $counts,
            'lowStockItems' => $lowStockItems,
            'latestMutations' => $latestMutations,
            'recentOrders' => $recentOrders,
            'drivers' => $drivers,
            'statusFilter' => $statusFilter,
            'pilotMerchant' => $pilotMerchant,
        ]);
    }

    /**
     * Admin Menerima atau Menolak Order dengan Alasan Tercatat (ADM-003, ADM-008)
     */
    public function updateOrderDecision(Request $request, int $orderId)
    {
        $order = Order::with(['items', 'delivery', 'payment'])->findOrFail($orderId);

        $validated = $request->validate([
            'decision' => 'required|in:accept,reject',
            'reason' => 'required_if:decision,reject|nullable|string|max:255',
        ], [
            'reason.required_if' => 'Alasan penolakan order wajib dicatat (ADM-003).',
        ]);

        $beforeStatus = $order->status;

        if ($validated['decision'] === 'reject') {
            $order->update([
                'status' => 'cancelled',
                'rejection_reason' => $validated['reason'],
                'notes' => trim(($order->notes ?? '') . ' | Ditolak Admin: ' . $validated['reason']),
            ]);

            if ($order->delivery) {
                $order->delivery->update(['status' => 'cancelled']);
            }

            $this->inventoryService->releaseReservedStockForOrder(
                $order,
                'Order ditolak Admin: ' . $validated['reason']
            );

            $this->auditService->log(
                action: 'admin_order_rejected',
                entity: $order,
                entityId: $order->id,
                beforeState: ['status' => $beforeStatus],
                afterState: ['status' => 'cancelled'],
                reason: $validated['reason']
            );

            return redirect()->back()->with('success', "Order {$order->order_number} telah ditolak dengan alasan tercatat dan reservasi stok dilepas.");
        }

        // ADM-008: Admin tidak boleh mengubah status paid secara manual tanpa prosedur finance;
        // untuk order non-COD yang belum paid, wajib tunggu verifikasi pembayaran DOKU.
        if ($order->payment_method !== 'cod' && $order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Order belum berstatus PAID dari DOKU. Admin tidak diizinkan mengubah status pembayaran secara manual (ADM-008, BR-007).');
        }

        $order->update([
            'status' => 'accepted',
            'packing_status' => $order->packing_status === 'not_started' ? 'picking' : $order->packing_status,
        ]);

        $this->auditService->log(
            action: 'admin_order_accepted',
            entity: $order,
            entityId: $order->id,
            beforeState: ['status' => $beforeStatus],
            afterState: ['status' => 'accepted'],
            reason: $validated['reason'] ?? 'Order diterima Admin dan diteruskan ke Picking List Gudang'
        );

        return redirect()->back()->with('success', "Order {$order->order_number} diterima dan diteruskan ke Picking List Gudang.");
    }

    /**
     * Admin Menetapkan / Meninjau Penugasan Driver KurirQ (ADM-006)
     */
    public function assignDriver(Request $request, int $orderId)
    {
        $order = Order::with('delivery')->findOrFail($orderId);

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::where('role', 'driver')->findOrFail($validated['driver_id']);
        $courierJobId = $order->delivery?->courier_job_id ?? ('KRQ-' . date('Ymd') . '-' . strtoupper(Str::random(5)));

        if ($order->delivery) {
            $order->delivery->update([
                'driver_id' => $driver->id,
                'courier_provider' => config('myethek.shipping.provider', 'KurirQ'),
                'courier_job_id' => $courierJobId,
                'status' => 'assigned',
                'assigned_at' => now(),
                'rejection_reason' => null,
            ]);
        }

        if (in_array($order->status, ['packed', 'ready_for_pickup', 'ready_to_ship'])) {
            $order->update(['status' => 'assigned']);
        }

        $this->auditService->log(
            action: 'admin_dispatch_driver',
            entity: $order,
            entityId: $order->id,
            beforeState: ['driver_id' => null],
            afterState: ['driver_id' => $driver->id, 'courier_job_id' => $courierJobId],
            reason: "Dispatch KurirQ ke driver {$driver->name}"
        );

        return redirect()->back()->with('success', "Driver KurirQ ({$driver->name}) berhasil ditugaskan untuk order {$order->order_number}.");
    }
}
