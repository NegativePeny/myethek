<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'myethek_cart';

    /**
     * Ambil data mentah keranjang dari session.
     * Struktur: [
     *   'merchant_id' => int,
     *   'items' => [
     *     productId => ['product_id' => int, 'name' => string, 'unit_name' => string, 'price' => float, 'quantity' => int]
     *   ]
     * ]
     */
    public function getCart(): array
    {
        return Session::get(self::SESSION_KEY, [
            'merchant_id' => null,
            'items' => [],
        ]);
    }

    /**
     * Tambah produk ke keranjang belanja dengan Single-Merchant Enforcement.
     * Jika merchant_id berbeda dari toko yang sudah ada di cart, reset cart dan isi dengan merchant baru.
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

        // Ambil harga efektif dari inventori toko
        $inventory = MerchantInventory::where('merchant_id', $merchant->id)
            ->where('product_id', $product->id)
            ->first();

        $effectivePrice = $inventory ? $inventory->effective_price : (float) $product->base_price;
        $stock = $inventory ? $inventory->stock : 0;

        $currentQty = $cart['items'][$product->id]['quantity'] ?? 0;
        $newQty = $currentQty + $quantity;

        // Batasi kuantitas tidak melebihi stok yang ada
        if ($newQty > $stock) {
            $newQty = $stock;
        }

        if ($newQty > 0) {
            $cart['items'][$product->id] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'unit_name' => $product->unit_name,
                'price' => $effectivePrice,
                'quantity' => $newQty,
                'subtotal' => $effectivePrice * $newQty,
            ];
        }

        Session::put(self::SESSION_KEY, $cart);

        return [
            'success' => true,
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
            // Cek batasan stok aktual merchant
            $merchantId = $cart['merchant_id'];
            $inventory = MerchantInventory::where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->first();

            $maxStock = $inventory ? $inventory->stock : $quantity;
            $appliedQty = min($quantity, $maxStock);

            $cart['items'][$productId]['quantity'] = $appliedQty;
            $cart['items'][$productId]['subtotal'] = $cart['items'][$productId]['price'] * $appliedQty;
        }

        if (empty($cart['items'])) {
            $cart['merchant_id'] = null;
        }

        Session::put(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Hapus satu item dari keranjang.
     */
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

    /**
     * Kosongkan seluruh keranjang belanja.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Ambil daftar item belanja.
     */
    public function getItems(): array
    {
        return $this->getCart()['items'] ?? [];
    }

    /**
     * Hitung total nilai belanja produk (subtotal).
     */
    public function getSubtotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return (float) $total;
    }

    /**
     * Ambil jumlah total kuantitas barang di keranjang.
     */
    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->getItems() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    /**
     * Ambil merchant yang sedang melayani keranjang ini.
     */
    public function getMerchant(): ?Merchant
    {
        $merchantId = $this->getCart()['merchant_id'] ?? null;
        if (!$merchantId) {
            return null;
        }
        return Merchant::with('territory')->find($merchantId);
    }

    /**
     * Periksa apakah subtotal belanja memenuhi minimal order merchant.
     */
    public function meetsMinimumOrder(?Merchant $merchant = null): bool
    {
        $targetMerchant = $merchant ?? $this->getMerchant();
        if (!$targetMerchant) {
            return true;
        }
        return $this->getSubtotal() >= (float) $targetMerchant->min_order_amount;
    }
}
