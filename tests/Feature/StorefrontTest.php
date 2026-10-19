<?php

namespace Tests\Feature;

use App\Models\Territory;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TerritorySeeder::class);
        $this->seed(CategoryAndProductSeeder::class);
        $this->seed(UserAndMerchantSeeder::class);
    }

    /**
     * Storefront beranda berhasil dimuat dengan toko default Sub-Agen Gedog.
     */
    public function test_storefront_loads_successfully_with_default_village(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Warung Berkah Mak Ethek Gedog');
        $response->assertSee('Kelurahan Gedog');
        $response->assertSee('Sub-Agen Desa');
        $response->assertSee('Beras Lahap Premium 5kg');
        $response->assertSee('sak (5 kg)');
    }

    /**
     * Customer dapat mengganti lokasi ke Kelurahan Bendogerit dan memicu fallback otomatis ke Agen Kecamatan.
     */
    public function test_customer_can_switch_village_to_trigger_fallback(): void
    {
        $bendogerit = Territory::where('code', '35.72.03.1002')->firstOrFail();

        $response = $this->post('/set-location', [
            'village_id' => $bendogerit->id,
        ]);

        $response->assertRedirect();

        // Kunjungi kembali beranda setelah session wilayah tersimpan
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Kelurahan Bendogerit');
        $response->assertSee('Koperasi Sananwetan Berkah');
        $response->assertSee('Agen Kecamatan');
        $response->assertSee('Pengalihan Wilayah Aktif (Cascading Fallback)');
    }

    /**
     * Halaman detail produk memuat info produk, satuan tradisional, dan stok toko yang aktif.
     */
    public function test_product_detail_page_renders_with_correct_stock_and_price(): void
    {
        $response = $this->get('/product/beras-lahap-premium-5kg');

        $response->assertStatus(200);
        $response->assertSee('Beras Lahap Premium 5kg');
        $response->assertSee('sak (5 kg)');
        $response->assertSee('Rp 74.000');
        $response->assertSee('Warung Berkah Mak Ethek Gedog');
    }
}
