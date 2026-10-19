<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\TierPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryAndProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sembako & Beras', 'slug' => 'sembako-beras', 'icon' => 'bi-basket', 'sort_order' => 1],
            ['name' => 'Sayuran & Bumbu Dapur', 'slug' => 'sayuran-bumbu', 'icon' => 'bi-flower1', 'sort_order' => 2],
            ['name' => 'Minyak & Gula', 'slug' => 'minyak-gula', 'icon' => 'bi-droplet', 'sort_order' => 3],
            ['name' => 'Daging & Telur', 'slug' => 'daging-telur', 'icon' => 'bi-egg', 'sort_order' => 4],
        ];

        $catModels = [];
        foreach ($categories as $cat) {
            $catModels[$cat['slug']] = Category::create($cat);
        }

        $products = [
            [
                'category_id' => $catModels['sembako-beras']->id,
                'sku' => 'PRD-BERAS-01',
                'name' => 'Beras Lahap Premium 5kg',
                'slug' => 'beras-lahap-premium-5kg',
                'unit_name' => 'sak (5 kg)',
                'base_price' => 74000,
                'description' => 'Beras pulen kualitas unggulan langsung dari lumbung petani binaan Koperasi Jatim.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 68500, 'moq' => 5],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 65000, 'moq' => 20],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 62000, 'moq' => 100],
                ],
            ],
            [
                'category_id' => $catModels['minyak-gula']->id,
                'sku' => 'PRD-MYK-01',
                'name' => 'Minyakita Bantal 1 Liter',
                'slug' => 'minyakita-bantal-1-liter',
                'unit_name' => '1 liter',
                'base_price' => 15700,
                'description' => 'Minyak goreng subsidi kemasan hemat untuk kebutuhan memasak harian keluarga.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 14200, 'moq' => 12],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 13500, 'moq' => 60],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 13000, 'moq' => 240],
                ],
            ],
            [
                'category_id' => $catModels['minyak-gula']->id,
                'sku' => 'PRD-GULA-01',
                'name' => 'Gula Pasir Kristal Putih 1kg',
                'slug' => 'gula-pasir-kristal-putih-1kg',
                'unit_name' => '1 kg',
                'base_price' => 17500,
                'description' => 'Gula tebu murni manis alami produksi pabrik gula Jawa Timur.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 15800, 'moq' => 10],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 15000, 'moq' => 50],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 14400, 'moq' => 200],
                ],
            ],
            [
                'category_id' => $catModels['daging-telur']->id,
                'sku' => 'PRD-TLR-01',
                'name' => 'Telur Ayam Ras Segar Blitar',
                'slug' => 'telur-ayam-ras-segar-blitar',
                'unit_name' => '1 kg',
                'base_price' => 27500,
                'description' => 'Telur segar pilihan langsung dari peternak ayam petelur lokal Blitar.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 25000, 'moq' => 10],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 24000, 'moq' => 50],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 23000, 'moq' => 200],
                ],
            ],
            [
                'category_id' => $catModels['sayuran-bumbu']->id,
                'sku' => 'PRD-BMR-01',
                'name' => 'Bawang Merah Lokal Super',
                'slug' => 'bawang-merah-lokal-super',
                'unit_name' => '500 gram',
                'base_price' => 19000,
                'description' => 'Bawang merah segar kering, aroma tajam pas untuk bumbu tumis dan goreng.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 17000, 'moq' => 5],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 15500, 'moq' => 20],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 14000, 'moq' => 50],
                ],
            ],
            [
                'category_id' => $catModels['sayuran-bumbu']->id,
                'sku' => 'PRD-CB-01',
                'name' => 'Cabai Rawit Merah Segar',
                'slug' => 'cabai-rawit-merah-segar',
                'unit_name' => '250 gram',
                'base_price' => 12500,
                'description' => 'Cabai rawit segar pedas mantap dipetik langsung dari kebun rakyat.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 11000, 'moq' => 4],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 10000, 'moq' => 20],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 9000, 'moq' => 50],
                ],
            ],
            [
                'category_id' => $catModels['sayuran-bumbu']->id,
                'sku' => 'PRD-KGK-01',
                'name' => 'Sayur Kangkung Segar',
                'slug' => 'sayur-kangkung-segar',
                'unit_name' => 'ikat',
                'base_price' => 3000,
                'description' => 'Kangkung hidroponik segar bebas pestisida berlebih.',
                'image' => null,
                'tiers' => [
                    ['buyer_tier' => 'sub_agent', 'wholesale_price' => 2400, 'moq' => 10],
                    ['buyer_tier' => 'agent', 'wholesale_price' => 2000, 'moq' => 50],
                    ['buyer_tier' => 'distributor', 'wholesale_price' => 1700, 'moq' => 100],
                ],
            ],
        ];

        foreach ($products as $pData) {
            $tiers = $pData['tiers'];
            unset($pData['tiers']);

            $product = Product::create($pData);

            foreach ($tiers as $t) {
                TierPrice::create([
                    'product_id' => $product->id,
                    'buyer_tier' => $t['buyer_tier'],
                    'wholesale_price' => $t['wholesale_price'],
                    'moq' => $t['moq'],
                ]);
            }
        }
    }
}
