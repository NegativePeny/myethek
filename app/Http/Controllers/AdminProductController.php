<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Product;
use App\Models\TierPrice;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index()
    {
        $products = Product::with(['category', 'tierPrices'])
            ->latest()
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $suggestedSku = 'PRD-' . strtoupper(Str::random(6));

        return view('admin.products.create', compact('categories', 'suggestedSku'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'unit_name' => 'required|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'base_price' => 'required|numeric|min:100',
            'b2b_price' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active' => 'nullable|boolean',

            'price_sub_agent' => 'required|numeric|min:0',
            'moq_sub_agent' => 'required|integer|min:1',
            'price_agent' => 'required|numeric|min:0',
            'moq_agent' => 'required|integer|min:1',
            'price_distributor' => 'required|numeric|min:0',
            'moq_distributor' => 'required|integer|min:1',
        ]);

        $sku = !empty($validated['sku']) ? strtoupper($validated['sku']) : 'PRD-' . strtoupper(Str::random(6));
        $slug = Str::slug($validated['name']);

        $baseSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = '/uploads/products/' . $filename;
        }

        $b2bPrice = $validated['b2b_price'] ?? $validated['price_sub_agent'];
        $costPrice = $validated['cost_price'] ?? $validated['price_distributor'];

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'sku' => $sku,
            'name' => $validated['name'],
            'slug' => $slug,
            'unit_name' => $validated['unit_name'],
            'cost_price' => $costPrice,
            'base_price' => $validated['base_price'],
            'b2b_price' => $b2bPrice,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $tiers = [
            ['buyer_tier' => 'sub_agent', 'wholesale_price' => $validated['price_sub_agent'], 'moq' => $validated['moq_sub_agent']],
            ['buyer_tier' => 'agent', 'wholesale_price' => $validated['price_agent'], 'moq' => $validated['moq_agent']],
            ['buyer_tier' => 'distributor', 'wholesale_price' => $validated['price_distributor'], 'moq' => $validated['moq_distributor']],
        ];

        foreach ($tiers as $t) {
            TierPrice::create([
                'product_id' => $product->id,
                'buyer_tier' => $t['buyer_tier'],
                'wholesale_price' => $t['wholesale_price'],
                'moq' => $t['moq'],
            ]);
        }

        // Buat inventori otomatis di Gudang Koperasi Kota Blitar
        $pilotMerchant = Merchant::where('slug', config('myethek.pilot_merchant_slug', 'gudang-pusat-koperasi-kota-blitar'))->first();
        if ($pilotMerchant) {
            MerchantInventory::firstOrCreate(
                ['merchant_id' => $pilotMerchant->id, 'product_id' => $product->id],
                [
                    'stock' => (int) ($validated['initial_stock'] ?? 100),
                    'stock_reserved' => 0,
                    'reorder_level' => 15,
                    'rack_location' => 'RAK-A1',
                    'custom_price' => $product->base_price,
                    'is_available' => true,
                ]
            );
        }

        $this->auditService->log(
            action: 'product_created',
            entity: $product,
            entityId: $product->id,
            beforeState: null,
            afterState: ['sku' => $product->sku, 'base_price' => $product->base_price, 'b2b_price' => $product->b2b_price],
            reason: 'Tambah SKU produk baru ke katalog koperasi'
        );

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$product->name}' ({$product->sku}) berhasil ditambahkan ke katalog koperasi.");
    }

    public function edit(int $id)
    {
        $product = Product::with('tierPrices')->findOrFail($id);
        $categories = Category::orderBy('name')->get();

        $tierMap = $product->tierPrices->keyBy('buyer_tier');

        return view('admin.products.edit', compact('product', 'categories', 'tierMap'));
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => "required|string|max:50|unique:products,sku,{$product->id}",
            'unit_name' => 'required|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'base_price' => 'required|numeric|min:100',
            'b2b_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active' => 'nullable|boolean',

            'price_sub_agent' => 'required|numeric|min:0',
            'moq_sub_agent' => 'required|integer|min:1',
            'price_agent' => 'required|numeric|min:0',
            'moq_agent' => 'required|integer|min:1',
            'price_distributor' => 'required|numeric|min:0',
            'moq_distributor' => 'required|integer|min:1',
        ]);

        $beforeState = [
            'base_price' => $product->base_price,
            'b2b_price' => $product->b2b_price,
            'cost_price' => $product->cost_price,
            'is_active' => $product->is_active,
        ];

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = '/uploads/products/' . $filename;
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'sku' => strtoupper($validated['sku']),
            'name' => $validated['name'],
            'unit_name' => $validated['unit_name'],
            'cost_price' => $validated['cost_price'] ?? $validated['price_distributor'],
            'base_price' => $validated['base_price'],
            'b2b_price' => $validated['b2b_price'] ?? $validated['price_sub_agent'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        $tiers = [
            'sub_agent' => ['price' => $validated['price_sub_agent'], 'moq' => $validated['moq_sub_agent']],
            'agent' => ['price' => $validated['price_agent'], 'moq' => $validated['moq_agent']],
            'distributor' => ['price' => $validated['price_distributor'], 'moq' => $validated['moq_distributor']],
        ];

        foreach ($tiers as $tierName => $data) {
            TierPrice::updateOrCreate(
                ['product_id' => $product->id, 'buyer_tier' => $tierName],
                ['wholesale_price' => $data['price'], 'moq' => $data['moq']]
            );
        }

        $this->auditService->log(
            action: 'product_price_updated',
            entity: $product,
            entityId: $product->id,
            beforeState: $beforeState,
            afterState: [
                'base_price' => $product->base_price,
                'b2b_price' => $product->b2b_price,
                'cost_price' => $product->cost_price,
                'is_active' => $product->is_active,
            ],
            reason: 'Pembaruan harga/data master produk'
        );

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$product->name}' berhasil diperbarui dan tercatat di Audit Log.");
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $sku = $product->sku;
        $product->delete();

        $this->auditService->log(
            action: 'product_deleted',
            entity: 'Product',
            entityId: $id,
            beforeState: ['name' => $name, 'sku' => $sku],
            afterState: null,
            reason: 'Hapus produk dari katalog'
        );

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$name}' telah dihapus dari katalog.");
    }
}
