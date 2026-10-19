<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Territory;
use App\Services\StoreLocatorService;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLocatorFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected StoreLocatorService $locatorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TerritorySeeder::class);
        $this->seed(CategoryAndProductSeeder::class);
        $this->seed(UserAndMerchantSeeder::class);

        $this->locatorService = new StoreLocatorService();
    }

    /**
     * Skenario 1: Customer di Kelurahan Gedog langsung dilayani oleh Sub-Agen di Kelurahan Gedog.
     */
    public function test_customer_in_gedog_finds_sub_agent_in_gedog(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();

        $result = $this->locatorService->resolveMerchantForCustomer($gedog);

        $this->assertNotNull($result['merchant']);
        $this->assertEquals('sub_agent', $result['served_by_tier']);
        $this->assertFalse($result['fallback_applied']);
        $this->assertEquals('Warung Berkah Mak Ethek Gedog', $result['merchant']->store_name);
    }

    /**
     * Skenario 2: Customer di Kelurahan Bendogerit (tidak ada Sub-Agen) otomatis fallback ke Agen Kecamatan Sananwetan.
     */
    public function test_customer_in_bendogerit_automatically_falls_back_to_agent_in_sananwetan(): void
    {
        $bendogerit = Territory::where('code', '35.72.03.1002')->firstOrFail();

        $result = $this->locatorService->resolveMerchantForCustomer($bendogerit);

        $this->assertNotNull($result['merchant']);
        $this->assertEquals('agent', $result['served_by_tier']);
        $this->assertTrue($result['fallback_applied']);
        $this->assertEquals('district', $result['fallback_level']);
        $this->assertEquals('Koperasi Sananwetan Berkah', $result['merchant']->store_name);
    }

    /**
     * Skenario 3: Bila Sub-Agen Gedog sedang tutup (is_open = false), customer Gedog otomatis dialihkan ke Agen Kecamatan.
     */
    public function test_customer_falls_back_to_agent_when_sub_agent_is_closed(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();

        // Sub-Agen tutup toko sementara
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $subAgen->update(['is_open' => false]);

        $result = $this->locatorService->resolveMerchantForCustomer($gedog);

        $this->assertNotNull($result['merchant']);
        $this->assertEquals('agent', $result['served_by_tier']);
        $this->assertTrue($result['fallback_applied']);
        $this->assertEquals('district', $result['fallback_level']);
        $this->assertEquals('Koperasi Sananwetan Berkah', $result['merchant']->store_name);
    }

    /**
     * Skenario 4: Bila Sub-Agen dan Agen tutup, fallback dialihkan ke Distributor Kota Blitar.
     */
    public function test_customer_falls_back_to_distributor_when_agent_is_also_closed(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();

        // Tutup Sub-Agen dan Agen
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $subAgen->update(['is_open' => false]);

        $agen = Merchant::where('slug', 'koperasi-sananwetan-berkah')->firstOrFail();
        $agen->update(['is_open' => false]);

        $result = $this->locatorService->resolveMerchantForCustomer($gedog);

        $this->assertNotNull($result['merchant']);
        $this->assertEquals('distributor', $result['served_by_tier']);
        $this->assertTrue($result['fallback_applied']);
        $this->assertEquals('regency', $result['fallback_level']);
        $this->assertEquals('Gudang Pusat Koperasi Kota Blitar', $result['merchant']->store_name);
    }
}
