<?php

namespace Tests\Feature;

use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ProcurementService;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantProcurementAndEscrowTest extends TestCase
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
     * Mitra dapat mengakses dashboard toko dan melihat saldo dompet koperasi.
     */
    public function test_sub_agent_can_view_merchant_dashboard(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $response = $this->get('/merchant');

        $response->assertStatus(200);
        $response->assertSee('Warung Berkah Mak Ethek Gedog');
        $response->assertSee('Saldo Dompet Mitra');
        $response->assertSee('Pesanan Retail Warga Masuk');
    }

    /**
     * Mitra dapat mengubah status buka/tutup toko.
     */
    public function test_merchant_can_toggle_open_status(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $initialStatus = $subAgen->is_open;

        $response = $this->post('/merchant/toggle-status');
        $response->assertRedirect();

        $subAgen->refresh();
        $this->assertNotEquals($initialStatus, $subAgen->is_open);
    }

    /**
     * Aturan pasokan B2B terikat hierarki wilayah secara ketat.
     */
    public function test_procurement_supplier_is_strictly_locked_by_territory_hierarchy(): void
    {
        $procurementService = new ProcurementService();

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $agen = Merchant::where('slug', 'koperasi-sananwetan-berkah')->firstOrFail();
        $distributor = Merchant::where('slug', 'gudang-pusat-koperasi-kota-blitar')->firstOrFail();

        // 1. Pemasok Sub-Agen Gedog (Desa) HARUS Agen Sananwetan (Kecamatan)
        $supplierForSubAgen = $procurementService->resolveSupplierForMerchant($subAgen);
        $this->assertNotNull($supplierForSubAgen);
        $this->assertEquals($agen->id, $supplierForSubAgen->id);

        // 2. Pemasok Agen Sananwetan (Kecamatan) HARUS Distributor Blitar (Kabupaten/Kota)
        $supplierForAgen = $procurementService->resolveSupplierForMerchant($agen);
        $this->assertNotNull($supplierForAgen);
        $this->assertEquals($distributor->id, $supplierForAgen->id);
    }

    /**
     * Penyelesaian pesanan retail dengan OTP melepaskan dana escrow ke dompet toko secara akurat.
     */
    public function test_retail_order_completion_with_otp_releases_escrow_to_merchant_wallet(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $wallet = Wallet::where('user_id', $subAgen->user_id)->firstOrFail();
        $initialBalance = (float) $wallet->balance_available;

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();
        $customer = User::where('role', 'customer')->firstOrFail();

        // Buat Order Retail Demo
        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'merchant_id' => $subAgen->id,
            'order_type' => 'retail',
            'status' => 'ready_to_ship',
            'subtotal' => 74000,
            'shipping_fee' => 6000,
            'platform_fee' => 1000,
            'total_amount' => 81000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
        ]);

        $order->delivery()->create([
            'delivery_type' => 'ojol',
            'distance_km' => 1.0,
            'fee' => 6000,
            'completion_otp' => '4321',
            'status' => 'pending',
        ]);

        $order->escrow()->create([
            'gross_amount' => 81000,
            'merchant_portion' => 74000,
            'driver_portion' => 6000,
            'platform_portion' => 1000,
            'status' => 'held',
        ]);

        // Input OTP yang salah -> harus gagal
        $responseFail = $this->post("/merchant/order/{$order->id}/status", [
            'action' => 'complete',
            'otp' => '9999',
        ]);
        $responseFail->assertRedirect();
        $this->assertDatabaseHas('escrow_transactions', [
            'order_id' => $order->id,
            'status' => 'held',
        ]);

        // Input OTP yang benar -> sukses dan saldo cair
        $responseSuccess = $this->post("/merchant/order/{$order->id}/status", [
            'action' => 'complete',
            'otp' => '4321',
        ]);
        $responseSuccess->assertRedirect();

        $this->assertDatabaseHas('escrow_transactions', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);

        $wallet->refresh();
        $this->assertEquals($initialBalance + 74000, (float) $wallet->balance_available);

        // Verifikasi log mutasi double-entry
        $this->assertDatabaseHas('wallet_mutations', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 74000,
            'reference_type' => 'order',
        ]);
    }

    /**
     * Alur kulakan B2B: Sub-Agen memesan kulakan dan saat barang tiba stok toko otomatis bertambah.
     */
    public function test_sub_agent_can_procure_and_receive_stock(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        // Ambil stok awal Sub-Agen
        $inv = MerchantInventory::where('merchant_id', $subAgen->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $initialStock = $inv->stock;

        // Sub-Agen ajukan PO kulakan 10 sak (MOQ adalah 5 sak)
        $response = $this->post('/merchant/procurement/order', [
            'items' => [
                $product->id => 10,
            ],
        ]);

        $response->assertRedirect();

        // Verifikasi Order Kulakan B2B terbentuk
        $procurementOrder = Order::where('customer_id', $subAgen->user_id)
            ->where('order_type', 'procurement')
            ->latest()
            ->firstOrFail();

        $this->assertEquals(10 * 68500, (float) $procurementOrder->total_amount);

        // Konfirmasi penerimaan barang kulakan
        $responseReceive = $this->post("/merchant/procurement/{$procurementOrder->id}/receive");
        $responseReceive->assertRedirect();

        // Pastikan stok inventori toko bertambah 10 unit secara otomatis
        $inv->refresh();
        $this->assertEquals($initialStock + 10, $inv->stock);
        $this->assertEquals('completed', $procurementOrder->fresh()->status);
    }

    /**
     * Mitra dapat melihat daftar pesanan masuk dengan filter status.
     */
    public function test_sub_agent_can_view_merchant_orders(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $response = $this->get('/merchant/orders');

        $response->assertStatus(200);
        $response->assertSee('Daftar Pesanan Retail');
        $response->assertSee('Baru Masuk');
    }

    /**
     * Mitra dapat melihat laporan keuangan toko dan rekap omset.
     */
    public function test_sub_agent_can_view_merchant_finance(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $response = $this->get('/merchant/finance');

        $response->assertStatus(200);
        $response->assertSee('Laporan Keuangan Toko');
        $response->assertSee('Status Escrow Rekening Bersama');
        $response->assertSee('Riwayat Mutasi Dompet');
    }

    /**
     * Mitra dapat memperbarui jam operasional dan konfigurasi toko.
     */
    public function test_sub_agent_can_update_merchant_profile(): void
    {
        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->session(['merchant_portal_id' => $subAgen->id]);

        $response = $this->post('/merchant/profile', [
            'store_name' => 'Warung Berkah Mak Ethek Gedog Updated',
            'phone' => '08140000001',
            'address' => 'Jl. Pahlawan Gedog RT 02 RW 03 No. 88',
            'min_order_amount' => 20000,
            'delivery_radius_km' => 6.5,
            'opening_time' => '05:30',
            'closing_time' => '22:00',
            'self_delivery_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subAgen->refresh();
        $this->assertEquals('Warung Berkah Mak Ethek Gedog Updated', $subAgen->store_name);
        $this->assertEquals(20000, (float) $subAgen->min_order_amount);
        $this->assertEquals(6.5, (float) $subAgen->delivery_radius_km);
        $this->assertTrue($subAgen->self_delivery_active);
    }
}

