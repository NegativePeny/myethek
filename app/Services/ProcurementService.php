<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TierPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementService
{
    /**
     * Tentukan mitra pemasok resmi sesuai jenjang dan hirarki wilayah.
     * Sub Agen -> Agen Kecamatan.
     * Agen -> Distributor Kabupaten.
     */
    public function resolveSupplierForMerchant(Merchant $buyer): ?Merchant
    {
        $territory = $buyer->territory;

        if ($buyer->isSubAgent()) {
            // Induk teritori Sub-Agen adalah Kecamatan (district)
            $districtId = $territory->parent_id;
            return Merchant::where('territory_id', $districtId)
                ->where('tier', 'agent')
                ->where('status', 'active')
                ->first();
        }

        if ($buyer->isAgent()) {
            // Induk teritori Agen adalah Kabupaten/Kota (regency)
            $regencyId = $territory->parent_id;
            return Merchant::where('territory_id', $regencyId)
                ->where('tier', 'distributor')
                ->where('status', 'active')
                ->first();
        }

        if ($buyer->isDistributor()) {
            return Merchant::where('tier', 'center')
                ->where('status', 'active')
                ->first();
        }

        return null;
    }

    /**
     * Ambil katalog kulakan grosir dengan harga grosir bertingkat (tier pricing) dan MOQ.
     */
    public function getWholesaleCatalogForBuyer(Merchant $buyer, Merchant $supplier): array
    {
        $products = Product::where('is_active', true)->with('category')->get();
        $supplierInventories = MerchantInventory::where('merchant_id', $supplier->id)
            ->get()
            ->keyBy('product_id');

        $tierPrices = TierPrice::where('buyer_tier', $buyer->tier)
            ->get()
            ->keyBy('product_id');

        $catalog = [];
        foreach ($products as $p) {
            $tp = $tierPrices[$p->id] ?? null;
            $inv = $supplierInventories[$p->id] ?? null;

            $wholesalePrice = $tp ? (float) $tp->wholesale_price : (float) $p->base_price;
            $moq = $tp ? (int) $tp->moq : 1;
            $supplierStock = $inv ? (int) $inv->stock : 0;

            $catalog[] = [
                'product' => $p,
                'wholesale_price' => $wholesalePrice,
                'retail_base_price' => (float) $p->base_price,
                'margin_per_unit' => (float) ($p->base_price - $wholesalePrice),
                'moq' => $moq,
                'supplier_stock' => $supplierStock,
                'is_available' => $supplierStock >= $moq,
            ];
        }

        return $catalog;
    }

    /**
     * Buat pesanan kulakan B2B berjenjang.
     *
     * @param Merchant $buyer Mitra yang melakukan kulakan (misal Sub-Agen)
     * @param Merchant $supplier Mitra penyedia pasokan (misal Agen)
     * @param array $requestedItems Format: [ ['product_id' => int, 'quantity' => int] ]
     */
    public function createProcurementOrder(Merchant $buyer, Merchant $supplier, array $requestedItems): array
    {
        if (empty($requestedItems)) {
            return ['success' => false, 'message' => 'Tidak ada produk yang dipilih untuk kulakan.'];
        }

        $tierPrices = TierPrice::where('buyer_tier', $buyer->tier)
            ->get()
            ->keyBy('product_id');

        $validatedItems = [];
        $subtotal = 0;

        foreach ($requestedItems as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if (!$product) {
                continue;
            }

            $tp = $tierPrices[$product->id] ?? null;
            $moq = $tp ? (int) $tp->moq : 1;
            $price = $tp ? (float) $tp->wholesale_price : (float) $product->base_price;

            if ($qty < $moq) {
                return [
                    'success' => false,
                    'message' => "Jumlah kulakan {$product->name} kurang dari batas minimal grosir (MOQ: {$moq} {$product->unit_name}).",
                ];
            }

            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $validatedItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $lineTotal,
            ];
        }

        if (empty($validatedItems)) {
            return ['success' => false, 'message' => 'Jumlah pesanan kulakan belum valid.'];
        }

        $orderNumber = 'KULAK-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $order = DB::transaction(function () use ($orderNumber, $buyer, $supplier, $subtotal, $validatedItems) {
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $buyer->user_id, // Pembeli adalah user pemilik merchant
                'merchant_id' => $supplier->id, // Penjual adalah merchant supplier
                'order_type' => 'procurement',
                'status' => 'processing',
                'subtotal' => $subtotal,
                'shipping_fee' => 0, // Kulakan diambil/diantar armada koperasi
                'platform_fee' => 0,
                'total_amount' => $subtotal,
                'payment_method' => 'va_bri',
                'payment_status' => 'paid',
                'notes' => 'Pesanan kulakan B2B dari ' . $buyer->store_name,
                'shipping_address' => $buyer->address,
            ]);

            foreach ($validatedItems as $v) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $v['product']->id,
                    'product_name' => $v['product']->name,
                    'unit_name' => $v['product']->unit_name,
                    'quantity' => $v['quantity'],
                    'price' => $v['price'],
                    'subtotal' => $v['subtotal'],
                ]);

                // Kurangi stok dari supplier
                $supplierInv = MerchantInventory::where('merchant_id', $supplier->id)
                    ->where('product_id', $v['product']->id)
                    ->first();

                if ($supplierInv) {
                    $supplierInv->decrement('stock', $v['quantity']);
                }
            }

            return $order;
        });

        return [
            'success' => true,
            'order' => $order,
            'message' => "Order kulakan {$orderNumber} berhasil dibuat senilai Rp " . number_format($subtotal, 0, ',', '.') . " ke {$supplier->store_name}.",
        ];
    }

    /**
     * Konfirmasi penerimaan barang kulakan dan tambahkan stok fisik pembeli secara otomatis.
     */
    public function receiveProcurementOrder(Order $procurementOrder, Merchant $buyer): array
    {
        if ($procurementOrder->order_type !== 'procurement') {
            return ['success' => false, 'message' => 'Bukan pesanan kulakan.'];
        }

        if ($procurementOrder->status === 'completed' || $procurementOrder->status === 'delivered') {
            return ['success' => false, 'message' => 'Pesanan ini sudah pernah dikonfirmasi sebelumnya.'];
        }

        DB::transaction(function () use ($procurementOrder, $buyer) {
            $procurementOrder->update([
                'status' => 'completed',
            ]);

            // Tambah stok ke inventori pembeli
            foreach ($procurementOrder->items as $item) {
                $buyerInventory = MerchantInventory::firstOrCreate(
                    [
                        'merchant_id' => $buyer->id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'stock' => 0,
                        'custom_price' => null,
                        'is_available' => true,
                    ]
                );

                $buyerInventory->increment('stock', $item->quantity);
            }
        });

        return [
            'success' => true,
            'message' => "Barang kulakan {$procurementOrder->order_number} telah diterima! Stok toko Anda otomatis bertambah.",
        ];
    }
}
