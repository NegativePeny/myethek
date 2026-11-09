<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'myethek_cart';

    public function getCart(): array
    {
        return Session::get(self::SESSION_KEY, [
            'merchant_id' => null,
            'items' => [],
        ]);
    }

    /**
     * Hitung harga efektif produk untuk user saat ini (B2C Retail vs B2B Grosir - B2B-002).
     */
    public function resolveEffectivePrice(Product $product, ?MerchantInventory $inventory = null): float
    {
        if (Auth::check() && Auth::user()->isB2BCustomer()) {
            return $product->b2b_effective_price;
        }

        return $inventory ? $inventory->effective_price : (float) $product->base_price;
    }

    /**
     * Tambah produk ke keranjang belanja dengan Single-Merchant Enforcement (CAT-005, BR-002).
     */
    public function add(Product $product, int $quantity, Merchant $merchant): array
    {
        $cart = $this->getCart();
        $merchantChanged = false;

        // Single-merchant enforcement
        if ($cart['merchant_id'] !== null && $cart['merchant_id'] !== $merchant->id) {
            $cart = [
                'merchant_id' => $merchant->id,
                'items' => [],
            ];
            $merchantChanged = true;
        }

        $cart['merchant_id'] = $merchant->id;

        $inventory = MerchantInventory::where('merchant_id', $merchant->id)
            ->where('product_id', $product->id)
            ->first();

        $effectivePrice = $this->resolveEffectivePrice($product, $inventory);
        $availableStock = $inventory ? $inventory->available_stock : 0;

        $currentQty = $cart['items'][$product->id]['quantity'] ?? 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $availableStock) {
            $newQty = $availableStock;
        }

        if ($newQty > 0) {
            $cart['items'][$product->id] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'unit_name' => $product->unit_name,
                'price' => $effectivePrice,
                'cost_price' => (float) ($product->cost_price ?? 0),
                'quantity' => $newQty,
                'subtotal' => $effectivePrice * $newQty,
                'pricing_tier' => (Auth::check() && Auth::user()->isB2BCustomer()) ? 'b2b' : 'b2c',
            ];
        }

        Session::put(self::SESSION_KEY, $cart);

        return [
            'success' => $newQty > 0,
            'merchant_changed' => $merchantChanged,
            'cart' => $cart,
            'item_count' => $this->getItemCount(),
            'subtotal' => $this->getSubtotal(),
        ];
    }

    /**
     * Perbarui kuantitas produk di keranjang.
     */
    public function updateQuantity(int $productId, int $quantity): bool
    {
        $cart = $this->getCart();

        if (!isset($cart['items'][$productId])) {
            return false;
        }

        if ($quantity <= 0) {
            unset($cart['items'][$productId]);
        } else {
            $merchantId = $cart['merchant_id'];
            $inventory = MerchantInventory::where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->first();

            $maxStock = $inventory ? $inventory->available_stock : $quantity;
            $appliedQty = min($quantity, $maxStock);

            if ($appliedQty <= 0) {
                unset($cart['items'][$productId]);
            } else {
                $cart['items'][$productId]['quantity'] = $appliedQty;
                $cart['items'][$productId]['subtotal'] = $cart['items'][$productId]['price'] * $appliedQty;
            }
        }

        if (empty($cart['items'])) {
            $cart['merchant_id'] = null;
        }

        Session::put(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Validasi ulang perubahan stok available & harga sebelum checkout/pembayaran (CAT-006, CAT-007, BR-008).
     */
    public function revalidateCart(): array
    {
        $cart = $this->getCart();
        $merchantId = $cart['merchant_id'] ?? null;
        $warnings = [];
        $outOfStockItems = [];

        if (!$merchantId || empty($cart['items'])) {
            return ['valid' => true, 'warnings' => [], 'out_of_stock' => []];
        }

        foreach ($cart['items'] as $productId => &$item) {
            $product = Product::find($productId);
            $inventory = MerchantInventory::where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->first();

            if (!$product || !$product->is_active || !$inventory || !$inventory->is_available || $inventory->available_stock <= 0) {
                $outOfStockItems[] = $item['name'];
                $warnings[] = "Produk '{$item['name']}' sedang habis dan tidak dapat dibayar.";
                continue;
            }

            if ($item['quantity'] > $inventory->available_stock) {
                $oldQty = $item['quantity'];
                $item['quantity'] = $inventory->available_stock;
                $warnings[] = "Stok '{$item['name']}' tersisa {$inventory->available_stock} {$item['unit_name']} (sebelumnya {$oldQty}).";
            }

            $latestPrice = $this->resolveEffectivePrice($product, $inventory);
            if (abs((float) $item['price'] - $latestPrice) > 0.01) {
                $oldPrice = number_format($item['price'], 0, ',', '.');
                $newPrice = number_format($latestPrice, 0, ',', '.');
                $item['price'] = $latestPrice;
                $warnings[] = "Harga '{$item['name']}' telah diperbarui dari Rp {$oldPrice} menjadi Rp {$newPrice}.";
            }

            $item['subtotal'] = $item['price'] * $item['quantity'];
        }
        unset($item);

        Session::put(self::SESSION_KEY, $cart);

        return [
            'valid' => empty($outOfStockItems),
            'warnings' => $warnings,
            'out_of_stock' => $outOfStockItems,
        ];
    }

    public function removeItem(int $productId): bool
    {
        $cart = $this->getCart();

        if (isset($cart['items'][$productId])) {
            unset($cart['items'][$productId]);

            if (empty($cart['items'])) {
                $cart['merchant_id'] = null;
            }

            Session::put(self::SESSION_KEY, $cart);
            return true;
        }

        return false;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function getItems(): array
    {
        return $this->getCart()['items'] ?? [];
    }

    public function getSubtotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return (float) $total;
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->getItems() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public function getMerchant(): ?Merchant
    {
        $merchantId = $this->getCart()['merchant_id'] ?? null;
        if (!$merchantId) {
            return null;
        }
        return Merchant::with('territory')->find($merchantId);
    }

    /**
     * Ambil batas minimum order yang berlaku (B2B Rp1.000.000 vs B2C Retail - B2B-003, BR-004).
     */
    public function getRequiredMinimumOrder(?Merchant $merchant = null): float
    {
        if (Auth::check() && Auth::user()->isB2BCustomer()) {
            return (float) config('myethek.b2b_min_order_amount', 1000000);
        }

        if (config('myethek.pilot_mode', true)) {
            return 15000.0;
        }

        $targetMerchant = $merchant ?? $this->getMerchant();
        return $targetMerchant ? (float) $targetMerchant->min_order_amount : 0.0;
    }

    /**
     * Periksa apakah subtotal nilai barang (sebelum ongkir & diskon) memenuhi minimal order (BR-004).
     */
    public function meetsMinimumOrder(?Merchant $merchant = null): bool
    {
        return $this->getSubtotal() >= $this->getRequiredMinimumOrder($merchant);
    }
}
