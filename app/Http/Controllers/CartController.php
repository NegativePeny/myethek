<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Territory;
use App\Services\CartService;
use App\Services\StoreLocatorService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;
    protected StoreLocatorService $locatorService;

    public function __construct(CartService $cartService, StoreLocatorService $locatorService)
    {
        $this->cartService = $cartService;
        $this->locatorService = $locatorService;
    }

    /**
     * Tampilkan Halaman Keranjang Belanja Mobile
     */
    public function index()
    {
        $cartItems = $this->cartService->getItems();
        $subtotal = $this->cartService->getSubtotal();
        $itemCount = $this->cartService->getItemCount();
        $merchant = $this->cartService->getMerchant();
        $meetsMinimum = $this->cartService->meetsMinimumOrder($merchant);

        // Ambil wilayah aktif
        $activeVillageId = session('active_village_id');
        $currentTerritory = Territory::with('parent')->find($activeVillageId);

        return view('storefront.cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'merchant' => $merchant,
            'meetsMinimum' => $meetsMinimum,
            'currentTerritory' => $currentTerritory,
        ]);
    }

    /**
     * Tambahkan Produk ke Keranjang
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $product = Product::findOrFail($validated['product_id']);

        // Tentukan toko aktif yang melayani customer saat ini
        $activeVillageId = session('active_village_id');
        $currentTerritory = Territory::with('parent')->find($activeVillageId)
            ?? Territory::where('code', '35.72.03.1001')->first();

        $resolution = $this->locatorService->resolveMerchantForCustomer($currentTerritory);
        $merchant = $resolution['merchant'];

        if (!$merchant) {
            return redirect()->back()->with('error', 'Tidak ada mitra toko yang melayani wilayah ini saat ini.');
        }

        $result = $this->cartService->add($product, $quantity, $merchant);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        $message = "{$product->name} berhasil ditambahkan ke keranjang.";
        if ($result['merchant_changed']) {
            $message = "Keranjang disesuaikan dengan toko baru: {$merchant->store_name}.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Perbarui Kuantitas Item di Keranjang
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateQuantity((int) $validated['product_id'], (int) $validated['quantity']);

        return redirect()->route('storefront.cart')->with('success', 'Keranjang berhasil diperbarui.');
    }

    /**
     * Hapus Satu Item dari Keranjang
     */
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $this->cartService->removeItem((int) $validated['product_id']);

        return redirect()->route('storefront.cart')->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Kosongkan Keranjang
     */
    public function clear()
    {
        $this->cartService->clear();

        return redirect()->route('storefront.cart')->with('success', 'Keranjang telah dikosongkan.');
    }
}
