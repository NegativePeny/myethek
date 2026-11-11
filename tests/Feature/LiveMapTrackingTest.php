<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveMapTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
    protected User $driver;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TerritorySeeder::class);
        $this->seed(CategoryAndProductSeeder::class);
        $this->seed(UserAndMerchantSeeder::class);

        $this->customer = User::where('phone', '08160000001')->firstOrFail();
        $this->driver = User::where('role', 'driver')->firstOrFail();
        $this->merchant = Merchant::firstOrFail();

        // Buat customer kedua untuk uji isolasi hak akses
        $this->otherCustomer = User::create([
            'name' => 'Warga Luar',
            'email' => 'warga.luar@myethek.test',
            'phone' => '08169999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'territory_id' => $this->customer->territory_id,
        ]);
    }

    /**
     * Halaman detail pesanan menampilkan komponen peta Leaflet OSM gratis.
     */
    public function test_customer_can_view_order_detail_with_live_map_tracking_ui(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-MAP-TEST-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 45000,
            'shipping_fee' => 6000,
            'platform_fee' => 1000,
            'total_amount' => 52000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Merdeka No. 10, Kepanjenkidul, Blitar',
            'shipping_latitude' => -8.109300,
            'shipping_longitude' => 112.189400,
        ]);

        $order->delivery()->create([
            'delivery_type' => 'delivery',
            'distance_km' => 2.5,
            'fee' => 6000,
            'driver_id' => $this->driver->id,
            'completion_otp' => '1234',
            'status' => 'on_delivery',
            'current_latitude' => -8.102000,
            'current_longitude' => 112.175000,
            'last_location_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->get("/user/orders/{$order->order_number}");

        $response->assertStatus(200);
        $response->assertSee('liveTrackingMap');
        $response->assertSee('Live Tracking KurirQ');
        $response->assertSee('leaflet@1.9.4/dist/leaflet.js', false);
        $response->assertSee('leaflet@1.9.4/dist/leaflet.css', false);
        $response->assertSee('tile.openstreetmap.org', false);
    }

    /**
     * Endpoint API live-tracking mengembalikan JSON koordinat Toko, Kurir, dan Pelanggan secara akurat.
     */
    public function test_customer_can_fetch_live_tracking_api_data(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-MAP-TEST-002',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 60000,
            'shipping_fee' => 7000,
            'platform_fee' => 1000,
            'total_amount' => 68000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Dr. Wahidin No. 22, Sentul, Blitar',
            'shipping_latitude' => -8.109300,
            'shipping_longitude' => 112.189400,
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'delivery',
            'distance_km' => 3.2,
            'fee' => 7000,
            'driver_id' => $this->driver->id,
            'completion_otp' => '5678',
            'status' => 'on_delivery',
            'current_latitude' => -8.103000,
            'current_longitude' => 112.176000,
            'last_location_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson("/user/orders/{$order->order_number}/live-tracking");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order_number' => 'ORD-MAP-TEST-002',
            'delivery_status' => 'on_delivery',
            'is_self_pickup' => false,
            'origin' => [
                'name' => $this->merchant->store_name,
            ],
            'destination' => [
                'address' => 'Jl. Dr. Wahidin No. 22, Sentul, Blitar',
                'latitude' => -8.109300,
                'longitude' => 112.189400,
            ],
            'driver' => [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'latitude' => -8.103000,
                'longitude' => 112.176000,
            ],
        ]);

        $this->assertGreaterThan(0, $response->json('distance_remaining_km'));
        $this->assertGreaterThan(0, $response->json('estimated_minutes'));
    }

    /**
     * User lain atau unauthenticated tidak dapat melihat live tracking order milik orang lain.
     */
    public function test_unauthenticated_or_other_customer_cannot_access_live_tracking(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-MAP-TEST-003',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 30000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 36000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_address' => 'Jl. Kenari Blitar',
        ]);

        $order->delivery()->create([
            'delivery_type' => 'delivery',
            'distance_km' => 1.5,
            'fee' => 5000,
            'status' => 'on_delivery',
        ]);

        // Tamu unauthenticated diarahkan ke login
        $guestResponse = $this->getJson("/user/orders/{$order->order_number}/live-tracking");
        $guestResponse->assertStatus(401);

        // Customer lain mendapat 404 (firstOrFail filter customer_id)
        $otherResponse = $this->actingAs($this->otherCustomer)
            ->getJson("/user/orders/{$order->order_number}/live-tracking");
        $otherResponse->assertStatus(404);
    }

    /**
     * Kurir dapat mem-POST pembaruan titik koordinat GPS ke server.
     */
    public function test_driver_can_update_live_gps_coordinates(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-MAP-TEST-004',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'on_delivery',
            'subtotal' => 50000,
            'shipping_fee' => 6000,
            'platform_fee' => 1000,
            'total_amount' => 57000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Veteran No. 1, Blitar',
        ]);

        $delivery = $order->delivery()->create([
            'delivery_type' => 'delivery',
            'distance_km' => 2.0,
            'fee' => 6000,
            'driver_id' => $this->driver->id,
            'completion_otp' => '9988',
            'status' => 'on_delivery',
        ]);

        $this->session(['driver_portal_id' => $this->driver->id]);

        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'latitude' => -8.104500,
            'longitude' => 112.179500,
            'delivery_id' => $delivery->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'latitude' => -8.104500,
                'longitude' => 112.179500,
                'delivery_id' => $delivery->id,
            ],
        ]);

        // Verifikasi tabel deliveries dan users terupdate
        $delivery->refresh();
        $this->assertEquals(-8.104500, $delivery->current_latitude);
        $this->assertEquals(112.179500, $delivery->current_longitude);
        $this->assertNotNull($delivery->last_location_at);

        $this->driver->refresh();
        $this->assertEquals(-8.104500, $this->driver->latitude);
        $this->assertEquals(112.179500, $this->driver->longitude);
    }

    /**
     * Endpoint update lokasi memvalidasi batas latitude dan longitude (-90 s/d 90, -180 s/d 180).
     */
    public function test_driver_location_update_validates_coordinates(): void
    {
        $this->session(['driver_portal_id' => $this->driver->id]);

        $response = $this->actingAs($this->driver)->postJson('/driver/location', [
            'latitude' => 150.0, // Invalid latitude (> 90)
            'longitude' => 112.179500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude']);
    }

    /**
     * Pesanan self_pickup menampilkan informasi toko dan petunjuk arah pengambilan.
     */
    public function test_live_tracking_for_self_pickup_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-MAP-TEST-005',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'ready_for_pickup',
            'subtotal' => 75000,
            'shipping_fee' => 0,
            'platform_fee' => 1000,
            'total_amount' => 76000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'shipping_address' => 'Ambil Sendiri di Toko',
        ]);

        $order->delivery()->create([
            'delivery_type' => 'self_pickup',
            'distance_km' => 0,
            'fee' => 0,
            'status' => 'ready_for_pickup',
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson("/user/orders/{$order->order_number}/live-tracking");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_self_pickup' => true,
        ]);

        $viewResponse = $this->actingAs($this->customer)->get("/user/orders/{$order->order_number}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Titik Pengambilan di Toko');
        $viewResponse->assertSee('Buka Rute');
    }
}

