<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\TierPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Tampilkan daftar master produk katalog Koperasi Jawa Timur.
     */
    public function index()
    {
        $products = Product::with(['category', 'tierPrices'])
            ->latest()
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Form tambah master produk baru.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $suggestedSku = 'PRD-' . strtoupper(Str::random(6));

        return view('admin.products.create', compact('categories', 'suggestedSku'));
    }

    /**
     * Simpan master produk baru beserta harga grosir berjenjang (Tier Prices).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'unit_name' => 'required|string|max:50',
            'base_price' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active' => 'nullable|boolean',

            // Harga grosir berjenjang (Tier Prices)
            'price_sub_agent' => 'required|numeric|min:0',
            'moq_sub_agent' => 'required|integer|min:1',
            'price_agent' => 'required|numeric|min:0',
            'moq_agent' => 'required|integer|min:1',
            'price_distributor' => 'required|numeric|min:0',
            'moq_distributor' => 'required|integer|min:1',
        ]);

        $sku = !empty($validated['sku']) ? strtoupper($validated['sku']) : 'PRD-' . strtoupper(Str::random(6));
        $slug = Str::slug($validated['name']);

        // Pastikan slug unik
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

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'sku' => $sku,
            'name' => $validated['name'],
            'slug' => $slug,
            'unit_name' => $validated['unit_name'],
            'base_price' => $validated['base_price'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Simpan harga grosir berjenjang
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

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$product->name}' ({$product->sku}) berhasil ditambahkan ke katalog koperasi.");
    }

    /**
     * Form edit master produk dan harga tier.
     */
    public function edit(int $id)
    {
        $product = Product::with('tierPrices')->findOrFail($id);
        $categories = Category::orderBy('name')->get();

        $tierMap = $product->tierPrices->keyBy('buyer_tier');

        return view('admin.products.edit', compact('product', 'categories', 'tierMap'));
    }

    /**
     * Perbarui master produk dan harga tier.
     */
    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => "required|string|max:50|unique:products,sku,{$product->id}",
            'unit_name' => 'required|string|max:50',
            'base_price' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active' => 'nullable|boolean',

            // Harga grosir berjenjang
            'price_sub_agent' => 'required|numeric|min:0',
            'moq_sub_agent' => 'required|integer|min:1',
            'price_agent' => 'required|numeric|min:0',
            'moq_agent' => 'required|integer|min:1',
            'price_distributor' => 'required|numeric|min:0',
            'moq_distributor' => 'required|integer|min:1',
        ]);

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
            'base_price' => $validated['base_price'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Update harga berjenjang
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

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$product->name}' berhasil diperbarui.");
    }

    /**
     * Hapus master produk dari katalog.
     */
    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Master produk '{$name}' telah dihapus dari katalog.");
    }
}
