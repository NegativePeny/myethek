<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MerchantInventory;
use App\Models\Product;
use App\Models\Territory;
use App\Services\StoreLocatorService;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    protected StoreLocatorService $locatorService;

    public function __construct(StoreLocatorService $locatorService)
    {
        $this->locatorService = $locatorService;
    }

    /**
     * Dapatkan wilayah desa/kelurahan aktif dari session atau fallback ke desa default (Gedog).
     */
    protected function getActiveVillage(): ?Territory
    {
        $activeVillageId = session('active_village_id');

        if (!$activeVillageId) {
            $defaultVillage = Territory::where('code', '35.72.03.1001')->first() 
                ?? Territory::where('level', 'village')->first();
            $activeVillageId = $defaultVillage?->id;
            session(['active_village_id' => $activeVillageId]);
        }

        return Territory::with('parent.parent')->find($activeVillageId);
    }

    /**
     * Halaman Utama Toko Retail (Pola Groseri)
     */
    public function index(Request $request)
    {
        $currentTerritory = $this->getActiveVillage();

        // 2. Eksekusi Cascading Fallback Engine untuk mendapatkan merchant yang melayani
        $resolution = $this->locatorService->resolveMerchantForCustomer($currentTerritory);
        $merchant = $resolution['merchant'];

        // 3. Ambil Kategori & Produk
        $categories = Category::orderBy('sort_order')->get();
        $selectedCategorySlug = $request->query('category');
        $searchQuery = $request->query('q');

        $productsQuery = Product::with(['category', 'tierPrices'])
            ->where('is_active', true);

        if ($selectedCategorySlug) {
            $productsQuery->whereHas('category', function ($q) use ($selectedCategorySlug) {
                $q->where('slug', $selectedCategorySlug);
            });
        }

        if ($searchQuery) {
            $productsQuery->where('name', 'like', "%{$searchQuery}%");
        }

        $products = $productsQuery->get();

        // 4. Petakan inventori stok dan harga dari merchant terpilih
        $inventories = [];
        if ($merchant) {
            $inventories = MerchantInventory::where('merchant_id', $merchant->id)
                ->get()
                ->keyBy('product_id');
        }

        // 5. Daftar desa/kelurahan untuk Modal Pemilih Lokasi
        $availableVillages = Territory::where('level', 'village')
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('storefront.index', [
            'currentTerritory' => $currentTerritory,
            'resolution' => $resolution,
            'merchant' => $merchant,
            'categories' => $categories,
            'products' => $products,
            'inventories' => $inventories,
            'availableVillages' => $availableVillages,
            'selectedCategorySlug' => $selectedCategorySlug,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Mengubah wilayah desa/kelurahan customer saat belanja
     */
    public function setLocation(Request $request)
    {
        $validated = $request->validate([
            'village_id' => 'required|exists:territories,id',
        ]);

        $village = Territory::findOrFail($validated['village_id']);
        session(['active_village_id' => $village->id]);

        return redirect()->back()->with('location_updated', 'Lokasi belanja berhasil diubah ke ' . $village->name);
    }

    /**
     * Halaman Detail Produk
     */
    public function productDetail(string $slug)
    {
        $product = Product::with('category')->where('slug', $slug)->firstOrFail();

        $currentTerritory = $this->getActiveVillage();

        $resolution = $this->locatorService->resolveMerchantForCustomer($currentTerritory);
        $merchant = $resolution['merchant'];

        $inventory = null;
        if ($merchant) {
            $inventory = MerchantInventory::where('merchant_id', $merchant->id)
                ->where('product_id', $product->id)
                ->first();
        }

        return view('storefront.product', [
            'product' => $product,
            'currentTerritory' => $currentTerritory,
            'resolution' => $resolution,
            'merchant' => $merchant,
            'inventory' => $inventory,
        ]);
    }
}
