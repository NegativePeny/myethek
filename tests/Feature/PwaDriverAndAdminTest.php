<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaDriverAndAdminTest extends TestCase
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
     * File manifest.json dan sw.js PWA terpasang dengan benar.
     */
    public function test_pwa_assets_and_manifest_configured(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifestContent = json_decode(file_get_contents($manifestPath), true);
        $this->assertEquals('Mak Ethek', $manifestContent['short_name']);
        $this->assertEquals('#16a34a', $manifestContent['theme_color']);

        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $this->assertStringContainsString('myethek-cache', file_get_contents($swPath));
    }

    /**
     * Kurir lokal dapat membuka portal dan mengklaim tugas pengantaran (pickup).
     */
    public function test_driver_can_view_portal_and_claim_delivery_job(): void
    {
        $driver = User::where('role', 'driver')->firstOrFail();
        $this->session(['driver_portal_id' => $driver->id]);

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $customer = User::where('role', 'customer')->firstOrFail();

        // Buat order dengan ojol siap jemput
        $order = Order::create([
            'order_number' => 'ORD-OJOL-001',
            'customer_id' => $customer->id,
            'merchant_id' => $subAgen->id,
            'order_type' => 'retail',
            'status' => 'processing',
            'subtotal' => 50000,
            'shipping_fee' => 6000,
            'platform_fee' => 1000,
            'total_amount' => 57000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Kawi Gedog',
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'ojol',
            'distance_km' => 1.0,
            'fee' => 6000,
            'driver_id' => null,
            'completion_otp' => '5566',
            'status' => 'pending',
        ]);

        // Kunjungi portal kurir
        $response = $this->get('/driver');
        $response->assertStatus(200);
        $response->assertSee('ORD-OJOL-001');

        // Kurir klaim tugas penjemputan
        $pickupResponse = $this->post("/driver/{$delivery->id}/pickup");
        $pickupResponse->assertRedirect();

        $delivery->refresh();
        $this->assertEquals($driver->id, $delivery->driver_id);
        $this->assertEquals('picked_up', $delivery->status);
        $this->assertEquals('on_delivery', $order->fresh()->status);
    }

    /**
     * Kurir menyelesaikan pengantaran dengan OTP dan ongkir masuk ke dompet driver.
     */
    public function test_driver_completes_delivery_with_otp_and_earns_fee(): void
    {
        $driver = User::where('role', 'driver')->firstOrFail();
        $this->session(['driver_portal_id' => $driver->id]);

        $driverWallet = Wallet::where('user_id', $driver->id)->firstOrFail();
        $initialDriverBalance = (float) $driverWallet->balance_available;

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $customer = User::where('role', 'customer')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-OJOL-002',
            'customer_id' => $customer->id,
            'merchant_id' => $subAgen->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 30000,
            'shipping_fee' => 8000,
            'platform_fee' => 1000,
            'total_amount' => 39000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Pahlawan Gedog',
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'ojol',
            'distance_km' => 2.0,
            'fee' => 8000,
            'driver_id' => $driver->id,
            'completion_otp' => '7788',
            'status' => 'picked_up',
        ]);

        $order->escrow()->create([
            'gross_amount' => 39000,
            'merchant_portion' => 30000,
            'driver_portion' => 8000,
            'platform_portion' => 1000,
            'status' => 'held',
        ]);

        // Kurir input OTP yang benar
        $response = $this->post("/driver/{$delivery->id}/complete", [
            'otp' => '7788',
        ]);

        $response->assertRedirect();

        $delivery->refresh();
        $this->assertEquals('delivered', $delivery->status);

        $driverWallet->refresh();
        $this->assertEquals($initialDriverBalance + 8000, (float) $driverWallet->balance_available);

        // Mutasi dompet kurir tercatat
        $this->assertDatabaseHas('wallet_mutations', [
            'wallet_id' => $driverWallet->id,
            'amount' => 8000,
            'reference_type' => 'delivery_fee',
        ]);
    }

    /**
     * Driver dapat memperbarui OTP yang kedaluwarsa dan menyelesaikan pengantaran.
     */
    public function test_driver_can_renew_expired_otp_and_complete_delivery(): void
    {
        $driver = User::where('role', 'driver')->firstOrFail();
        $this->session(['driver_portal_id' => $driver->id]);

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $customer = User::where('role', 'customer')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-EXP-OTP-001',
            'customer_id' => $customer->id,
            'merchant_id' => $subAgen->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 25000,
            'shipping_fee' => 8000,
            'total_amount' => 33000,
            'payment_status' => 'paid',
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'ojol',
            'distance_km' => 1.5,
            'fee' => 8000,
            'driver_id' => $driver->id,
            'completion_otp' => '1122',
            'otp_expires_at' => now()->subHours(2), // Kedaluwarsa sejak 2 jam lalu
            'status' => 'picked_up',
        ]);

        $order->escrow()->create([
            'gross_amount' => 33000,
            'merchant_portion' => 25000,
            'driver_portion' => 8000,
            'status' => 'held',
        ]);

        // Coba input OTP lama yang kedaluwarsa -> harus gagal
        $failResponse = $this->post("/driver/{$delivery->id}/complete", [
            'otp' => '1122',
        ]);
        $failResponse->assertSessionHas('error');
        $this->assertEquals('picked_up', $delivery->fresh()->status);

        // Driver memperbarui OTP via route renew-otp
        $renewResponse = $this->post("/driver/{$delivery->id}/renew-otp");
        $renewResponse->assertRedirect();
        $renewResponse->assertSessionHas('success');

        $delivery->refresh();
        $newOtp = $delivery->completion_otp;
        $this->assertNotEmpty($newOtp);
        $this->assertTrue(now()->lessThan($delivery->otp_expires_at));

        // Sekarang masukkan OTP baru -> berhasil
        $successResponse = $this->post("/driver/{$delivery->id}/complete", [
            'otp' => $newOtp,
        ]);
        $successResponse->assertRedirect();
        $this->assertEquals('delivered', $delivery->fresh()->status);
    }

    /**
     * Pelanggan dapat memperbarui OTP pesanan mereka jika kedaluwarsa.
     */
    public function test_customer_can_renew_order_otp(): void
    {
        $customer = User::where('role', 'customer')->firstOrFail();
        $this->actingAs($customer);

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $order = Order::create([
            'order_number' => 'ORD-CUST-OTP-001',
            'customer_id' => $customer->id,
            'merchant_id' => $subAgen->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 20000,
            'shipping_fee' => 8000,
            'total_amount' => 28000,
            'payment_status' => 'paid',
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'ojol',
            'distance_km' => 1.0,
            'fee' => 8000,
            'completion_otp' => '3344',
            'otp_expires_at' => now()->subHour(),
            'status' => 'picked_up',
        ]);

        $response = $this->post("/user/orders/{$order->order_number}/renew-otp");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $delivery->refresh();
        $this->assertNotEquals('3344', $delivery->completion_otp);
        $this->assertTrue(now()->lessThan($delivery->otp_expires_at));
    }

    /**
     * Panel admin holding menampilkan rekapitulasi finansial dan audit ledger.
     */
    public function test_admin_overview_displays_correct_financial_metrics(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('PERSATUAN KOPERASI JAWA TIMUR');
        $response->assertSee('Financial Holding & Territory Ledger', false);
        $response->assertSee('GMV RETAIL DESA');
        $response->assertSee('KULAKAN GROSIR B2B');
        $response->assertSee('Audit Trail Mutasi Dompet');
    }
}
