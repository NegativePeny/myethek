<?php

namespace App\Services;

use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected AuditService $auditService;

    public function __construct(?AuditService $auditService = null)
    {
        $this->auditService = $auditService ?? new AuditService();
    }

    /**
     * Reservasi stok saat order dibuat (WH-007, SOT Bab 6.1 & 7).
     * Stok on-hand tetap, stock_reserved bertambah sehingga available berkurang.
     */
    public function reserveStockForOrder(Order $order, ?User $actor = null): void
    {
        DB::transaction(function () use ($order, $actor) {
            foreach ($order->items as $item) {
                $inventory = MerchantInventory::where('merchant_id', $order->merchant_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $beforeAvailable = $inventory->available_stock;
                $inventory->increment('stock_reserved', $item->quantity);
                $inventory->refresh();

                StockMovement::create([
                    'merchant_id' => $order->merchant_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'reserve',
                    'quantity' => $item->quantity,
                    'stock_before' => $beforeAvailable,
                    'stock_after' => $inventory->available_stock,
                    'reference_type' => 'order',
                    'reference_id' => $order->order_number,
                    'actor_id' => $actor?->id ?? $order->customer_id,
                    'reason' => "Reservasi stok checkout pesanan {$order->order_number}",
                ]);
            }
        });
    }

    /**
     * Lepaskan reservasi stok saat pembayaran gagal/expired atau order dibatalkan sebelum dikemas (WH-008, UAT #6).
     */
    public function releaseReservedStockForOrder(Order $order, string $reason = 'Pembayaran kedaluwarsa atau pesanan dibatalkan', ?User $actor = null): void
    {
        DB::transaction(function () use ($order, $reason, $actor) {
            foreach ($order->items as $item) {
                $inventory = MerchantInventory::where('merchant_id', $order->merchant_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $beforeAvailable = $inventory->available_stock;
                $releaseQty = min((int) $inventory->stock_reserved, (int) $item->quantity);

                if ($releaseQty > 0) {
                    $inventory->decrement('stock_reserved', $releaseQty);
                    $inventory->refresh();

                    StockMovement::create([
                        'merchant_id' => $order->merchant_id,
                        'product_id' => $item->product_id,
                        'movement_type' => 'release',
                        'quantity' => $releaseQty,
                        'stock_before' => $beforeAvailable,
                        'stock_after' => $inventory->available_stock,
                        'reference_type' => 'order',
                        'reference_id' => $order->order_number,
                        'actor_id' => $actor?->id,
                        'reason' => $reason,
                    ]);
                }
            }
        });
    }

    /**
     * Commit pengurangan stok fisik (on-hand) saat proses Picking/Packing di Gudang selesai (WH-003..008).
     */
    public function commitStockForOrder(Order $order, ?User $actor = null): void
    {
        DB::transaction(function () use ($order, $actor) {
            foreach ($order->items as $item) {
                $inventory = MerchantInventory::where('merchant_id', $order->merchant_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    continue;
                }

                $pickedQty = $item->picked_quantity !== null ? (int) $item->picked_quantity : (int) $item->quantity;
                $stockBefore = (int) $inventory->stock;

                $deductOnHand = min($stockBefore, $pickedQty);
                $deductReserved = min((int) $inventory->stock_reserved, (int) $item->quantity);

                $inventory->update([
                    'stock' => max(0, $stockBefore - $deductOnHand),
                    'stock_reserved' => max(0, (int) $inventory->stock_reserved - $deductReserved),
                ]);

                StockMovement::create([
                    'merchant_id' => $order->merchant_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'commit',
                    'quantity' => -$deductOnHand,
                    'stock_before' => $stockBefore,
                    'stock_after' => $inventory->stock,
                    'reference_type' => 'order',
                    'reference_id' => $order->order_number,
                    'actor_id' => $actor?->id,
                    'reason' => "Pengeluaran fisik gudang (packing) pesanan {$order->order_number}",
                ]);
            }
        });
    }

    /**
     * Penyesuaian stok gudang (Stock Adjustment / Barang Masuk) dengan alasan dan Audit Log (ADM-005, WH-008).
     */
    public function adjustStock(MerchantInventory $inventory, int $newOnHandStock, string $reason, User $actor, string $type = 'adjustment'): StockMovement
    {
        return DB::transaction(function () use ($inventory, $newOnHandStock, $reason, $actor, $type) {
            $beforeStock = (int) $inventory->stock;
            $delta = $newOnHandStock - $beforeStock;

            $inventory->update([
                'stock' => max(0, $newOnHandStock),
            ]);

            $movement = StockMovement::create([
                'merchant_id' => $inventory->merchant_id,
                'product_id' => $inventory->product_id,
                'movement_type' => $type,
                'quantity' => $delta,
                'stock_before' => $beforeStock,
                'stock_after' => $inventory->stock,
                'reference_type' => 'manual_adjustment',
                'reference_id' => (string) $inventory->id,
                'actor_id' => $actor->id,
                'reason' => $reason,
            ]);

            $this->auditService->log(
                action: 'stock_adjustment',
                entity: $inventory,
                entityId: $inventory->id,
                beforeState: ['stock' => $beforeStock, 'product_id' => $inventory->product_id],
                afterState: ['stock' => $inventory->stock, 'delta' => $delta],
                reason: $reason,
                actor: $actor
            );

            return $movement;
        });
    }
}

