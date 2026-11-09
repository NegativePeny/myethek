<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    protected InventoryService $inventoryService;
    protected AuditService $auditService;

    public function __construct(InventoryService $inventoryService, AuditService $auditService)
    {
        $this->inventoryService = $inventoryService;
        $this->auditService = $auditService;
    }

    protected function getPilotWarehouseMerchant(): Merchant
    {
        $slug = config('myethek.pilot_merchant_slug', 'gudang-pusat-koperasi-kota-blitar');
        return Merchant::where('slug', $slug)->first() ?? Merchant::firstOrFail();
    }

    /**
     * Dashboard Web Desktop Admin Gudang (WH-001 s/d WH-009)
     */
    public function index(Request $request)
    {
        $merchant = $this->getPilotWarehouseMerchant();
        $tab = $request->query('tab', 'picking');

        // BR-007 & WH-001: Hanya order yang telah dibayar (paid) atau diotorisasi yang masuk ke Picking List Gudang
        $pickingOrders = Order::with(['items.product', 'customer', 'delivery.driver', 'payment', 'picker', 'packer'])
            ->where('merchant_id', $merchant->id)
            ->where('order_type', 'retail')
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhereIn('status', ['paid', 'accepted', 'processing', 'picking', 'packed', 'ready_to_ship', 'ready_for_pickup', 'assigned']);
            })
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->latest()
            ->get();

        // Inventori Gudang Utama (On-Hand, Reserved, Available, Reorder Level - WH-007, WH-009)
        $inventories = MerchantInventory::with('product.category')
            ->where('merchant_id', $merchant->id)
            ->orderBy('rack_location')
            ->get();

        $lowStockCount = $inventories->filter(fn ($inv) => $inv->available_stock <= $inv->reorder_level)->count();

        // Riwayat Pergerakan Stok (Stock Movements - WH-008)
        $stockMovements = StockMovement::with(['product', 'actor'])
            ->where('merchant_id', $merchant->id)
            ->latest()
            ->take(30)
            ->get();

        // Daftar Driver KurirQ Aktif untuk Handover/Dispatch
        $drivers = User::where('role', 'driver')->where('is_active', true)->get();

        // Hitung ringkasan KPI Gudang
        $stats = [
            'waiting_picking' => $pickingOrders->whereIn('packing_status', ['not_started', 'picking'])->count(),
            'packed_ready' => $pickingOrders->where('packing_status', 'packed')->count(),
            'handed_over' => $pickingOrders->where('packing_status', 'handed_over')->count(),
            'low_stock_sku' => $lowStockCount,
            'total_sku' => $inventories->count(),
        ];

        return view('warehouse.index', [
            'merchant' => $merchant,
            'pickingOrders' => $pickingOrders,
            'inventories' => $inventories,
            'stockMovements' => $stockMovements,
            'drivers' => $drivers,
            'stats' => $stats,
            'activeTab' => $tab,
        ]);
    }

    /**
     * Petugas Gudang mengonfirmasi kuantitas picked per SKU; selisih memicu exception (WH-002, WH-003, UAT #8)
     */
    public function confirmPickingItem(Request $request, int $orderItemId)
    {
        $item = OrderItem::with('order')->findOrFail($orderItemId);
        $order = $item->order;

        // BR-007: Pastikan order sudah paid atau disetujui
        if ($order->payment_status !== 'paid' && !in_array($order->status, ['paid', 'accepted', 'processing', 'picking'])) {
            return redirect()->back()->with('error', 'Order belum berstatus PAID / terotorisasi sehingga belum boleh masuk proses picking (BR-007).');
        }

        $validated = $request->validate([
            'picked_quantity' => 'required|integer|min:0|max:' . $item->quantity,
            'exception_note' => 'nullable|string|max:255',
            'resolve_exception' => 'nullable|boolean',
        ]);

        $pickedQty = (int) $validated['picked_quantity'];
        $hasDiscrepancy = $pickedQty < (int) $item->quantity;

        if ($hasDiscrepancy && empty($validated['exception_note']) && !$request->boolean('resolve_exception')) {
            $item->update([
                'picked_quantity' => $pickedQty,
                'picking_status' => 'exception',
                'exception_note' => 'Selisih stok fisik saat picking (kurang ' . ($item->quantity - $pickedQty) . ' ' . $item->unit_name . ')',
            ]);

            $order->update([
                'status' => 'picking',
                'packing_status' => 'picking',
                'picked_by' => Auth::id(),
                'picked_at' => now(),
            ]);

            return redirect()->back()->with('error', "Selisih kuantitas pada '{$item->product_name}' memicu status EXCEPTION. Berikan catatan resolusi/substitusi untuk melanjutkan.");
        }

        $item->update([
            'picked_quantity' => $pickedQty,
            'picking_status' => 'picked',
            'exception_note' => $validated['exception_note'] ?? null,
        ]);

        $order->update([
            'status' => 'picking',
            'packing_status' => 'picking',
            'picked_by' => Auth::id(),
            'picked_at' => now(),
        ]);

        return redirect()->back()->with('success', "Item '{$item->product_name}' ({$pickedQty} {$item->unit_name}) berhasil dikonfirmasi picked.");
    }

    /**
     * Tandai Packing Selesai & Commit Pengurangan Stok Gudang (WH-004, WH-005, WH-008)
     */
    public function completePacking(Request $request, int $orderId)
    {
        $order = Order::with('items')->findOrFail($orderId);

        if ($order->payment_status !== 'paid' && !in_array($order->status, ['paid', 'accepted', 'processing', 'picking'])) {
            return redirect()->back()->with('error', 'Order belum lunas / belum diotorisasi untuk dikemas.');
        }

        // Kriteria Penerimaan Gudang (PRD Bab 8): Order tidak dapat ditandai packed jika item belum dipenuhi atau exception belum diselesaikan
        $unpickedCount = $order->items->whereIn('picking_status', ['pending', 'exception'])->count();
        if ($unpickedCount > 0) {
            return redirect()->back()->with('error', "Tidak dapat menyelesaikan packing: masih ada {$unpickedCount} item yang belum di-pick atau berstatus exception.");
        }

        $packageCount = max(1, (int) $request->input('package_count', 1));

        // Commit pengurangan stok on-hand dan pelepasan reserved
        $this->inventoryService->commitStockForOrder($order, Auth::user());

        $order->update([
            'status' => 'ready_for_pickup',
            'packing_status' => 'packed',
            'packed_by' => Auth::id(),
            'packed_at' => now(),
            'package_count' => $packageCount,
        ]);

        $this->auditService->log(
            action: 'warehouse_order_packed',
            entity: $order,
            entityId: $order->id,
            beforeState: ['packing_status' => 'picking'],
            afterState: ['packing_status' => 'packed', 'package_count' => $packageCount],
            reason: 'Packing selesai oleh petugas gudang'
        );

        return redirect()->back()->with('success', "Order {$order->order_number} selesai dikemas ({$packageCount} kolli/paket) dan siap diserahkan ke Driver KurirQ.");
    }

    /**
     * Serah-Terima (Handover) Paket Gudang ke Driver KurirQ (WH-006, ADM-006)
     */
    public function handoverToDriver(Request $request, int $orderId)
    {
        $order = Order::with('delivery')->findOrFail($orderId);

        if (!in_array($order->packing_status, ['packed', 'handed_over']) && !in_array($order->status, ['ready_for_pickup', 'ready_to_ship', 'packed'])) {
            return redirect()->back()->with('error', 'Order harus selesai dikemas (PACKED) terlebih dahulu sebelum diserahkan ke driver.');
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
            'package_count' => 'nullable|integer|min:1',
        ]);

        $driver = User::where('role', 'driver')->findOrFail($validated['driver_id']);
        $handoverCode = 'HND-' . strtoupper(Str::random(6));
        $courierJobId = 'KRQ-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $order->update([
            'status' => 'assigned',
            'packing_status' => 'handed_over',
            'package_count' => $validated['package_count'] ?? $order->package_count ?? 1,
            'handed_over_at' => now(),
            'handover_code' => $handoverCode,
        ]);

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

        $this->auditService->log(
            action: 'warehouse_handover_driver',
            entity: $order,
            entityId: $order->id,
            beforeState: ['status' => 'ready_for_pickup'],
            afterState: ['status' => 'assigned', 'driver_id' => $driver->id, 'handover_code' => $handoverCode],
            reason: "Handover ke driver {$driver->name}"
        );

        return redirect()->back()->with('success', "Paket {$order->order_number} berhasil ditugaskan & tercatat serah-terima ({$handoverCode}) ke Driver {$driver->name}.");
    }

    /**
     * Penyesuaian Stok Gudang / Barang Masuk dengan Alasan & Audit Log (ADM-005, WH-008)
     */
    public function adjustInventory(Request $request, int $inventoryId)
    {
        $inventory = MerchantInventory::with('product')->findOrFail($inventoryId);

        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:50',
            'movement_type' => 'nullable|in:in,adjustment,return',
            'reason' => 'required|string|min:3|max:255',
        ], [
            'reason.required' => 'Alasan perubahan stok wajib diisi untuk keperluan Audit Log (ADM-005).',
        ]);

        if (isset($validated['reorder_level']) || isset($validated['rack_location'])) {
            $inventory->update([
                'reorder_level' => $validated['reorder_level'] ?? $inventory->reorder_level,
                'rack_location' => $validated['rack_location'] ?? $inventory->rack_location,
            ]);
        }

        $this->inventoryService->adjustStock(
            inventory: $inventory,
            newOnHandStock: (int) $validated['stock'],
            reason: $validated['reason'],
            actor: Auth::user(),
            type: $validated['movement_type'] ?? 'adjustment'
        );

        return redirect()->back()->with('success', "Stok '{$inventory->product->name}' berhasil diperbarui menjadi {$validated['stock']} dan tercatat di Audit Log.");
    }
}

