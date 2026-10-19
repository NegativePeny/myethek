<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TerritorySeeder::class);
        $this->seed(CategoryAndProductSeeder::class);
        $this->seed(UserAndMerchantSeeder::class);

        $this->customer = User::where('phone', '08160000001')->firstOrFail();
        $this->merchant = Merchant::firstOrFail();
    }

    /**
     * Tamu diarahkan ke login saat mengakses riwayat pesanan.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/user/orders');
        $response->assertRedirect('/login');
    }

    /**
     * Warga dapat melihat daftar riwayat belanja miliknya.
     */
    public function test_customer_can_view_order_history(): void
    {
        // Buat dummy order untuk customer
        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'pending',
            'subtotal' => 65000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 71000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Jl. Kawi No. 12, Gedog',
        ]);

        $product = Product::first();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => 'kg',
            'quantity' => 2,
            'price' => 32500,
            'subtotal' => 65000,
        ]);

        $response = $this->actingAs($this->customer)->get('/user/orders');

        $response->assertStatus(200);
        $response->assertSee('ORD-TEST-001');
        $response->assertSee('Menunggu Konfirmasi');
        $response->assertSee('Rp 71.000');
    }

    /**
     * Warga dapat melihat halaman detail pesanan lengkap dengan rincian biaya.
     */
    public function test_customer_can_view_order_detail(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DETAIL-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'delivered',
            'subtotal' => 100000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 106000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Kawi No. 12, Gedog',
        ]);

        $product = Product::first();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => 'sak',
            'quantity' => 1,
            'price' => 100000,
            'subtotal' => 100000,
        ]);

        $response = $this->actingAs($this->customer)->get('/user/orders/ORD-DETAIL-001');

        $response->assertStatus(200);
        $response->assertSee('ORD-DETAIL-001');
        $response->assertSee('Pesanan Selesai');
        $response->assertSee('Rp 106.000');
        $response->assertSee($product->name);
    }

    /**
     * Warga tidak dapat melihat pesanan milik warga lain.
     */
    public function test_customer_cannot_view_other_customer_order(): void
    {
        $otherCustomer = User::create([
            'name' => 'Warga Lain',
            'phone' => '08999999999',
            'email' => 'other@myethek.id',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-OTHER-001',
            'customer_id' => $otherCustomer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'pending',
            'subtotal' => 50000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 56000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Alamat Lain',
        ]);

        $response = $this->actingAs($this->customer)->get('/user/orders/ORD-OTHER-001');
        $response->assertStatus(404);
    }

    /**
     * Fitur Beli Lagi (Reorder) berhasil menambahkan barang dari pesanan ke keranjang.
     */
    public function test_customer_can_reorder_past_order(): void
    {
        $product = Product::first();

        $order = Order::create([
            'order_number' => 'ORD-REORDER-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'delivered',
            'subtotal' => 50000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 56000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'shipping_address' => 'Jl. Kawi No. 12',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => 'kg',
            'quantity' => 3,
            'price' => 15000,
            'subtotal' => 45000,
        ]);

        $response = $this->actingAs($this->customer)->post('/user/orders/ORD-REORDER-001/reorder');

        $response->assertRedirect(route('storefront.cart'));
        $response->assertSessionHas('success');

        $cart = session('myethek_cart');
        $this->assertNotEmpty($cart['items']);
        $this->assertArrayHasKey($product->id, $cart['items']);
        $this->assertEquals(3, $cart['items'][$product->id]['quantity']);
    }

    /**
     * Warga dapat melihat dan memperbarui profil dan alamat domisilinya.
     */
    public function test_customer_can_update_profile(): void
    {
        $newVillage = Territory::where('code', '35.72.03.1002')->firstOrFail();

        $response = $this->actingAs($this->customer)->post('/user/profile', [
            'name' => 'Siti Khadijah Updated',
            'phone' => '08160000001',
            'email' => 'siti.new@myethek.id',
            'territory_id' => $newVillage->id,
            'address' => 'Jl. Baru No. 99 RT 03 RW 04, Bendogerit',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'name' => 'Siti Khadijah Updated',
            'email' => 'siti.new@myethek.id',
            'territory_id' => $newVillage->id,
            'address' => 'Jl. Baru No. 99 RT 03 RW 04, Bendogerit',
        ]);

        $this->assertEquals($newVillage->id, session('active_village_id'));
    }

    /**
     * Warga dapat membatalkan pesanan yang masih 'pending' dan stok otomatis dikembalikan ke toko.
     */
    public function test_customer_can_cancel_pending_order_and_stock_is_restored(): void
    {
        $product = Product::first();
        $inventory = \App\Models\MerchantInventory::where('merchant_id', $this->merchant->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $initialStock = $inventory->stock;

        $order = Order::create([
            'order_number' => 'ORD-CANCEL-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'pending',
            'subtotal' => 50000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 56000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Jl. Kawi No. 12',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_name' => 'kg',
            'quantity' => 4,
            'price' => 12500,
            'subtotal' => 50000,
        ]);

        $escrow = \App\Models\EscrowTransaction::create([
            'order_id' => $order->id,
            'gross_amount' => 56000,
            'merchant_portion' => 50000,
            'driver_portion' => 5000,
            'platform_portion' => 1000,
            'status' => 'held',
        ]);

        $response = $this->actingAs($this->customer)->post('/user/orders/ORD-CANCEL-001/cancel', [
            'cancel_reason' => 'Salah memasukkan alamat pengantaran',
        ]);

        $response->assertRedirect(route('user.orders.show', 'ORD-CANCEL-001'));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertStringContainsString('Salah memasukkan alamat pengantaran', $order->notes);

        // Verifikasi status escrow menjadi refunded
        $escrow->refresh();
        $this->assertEquals('refunded', $escrow->status);

        // Verifikasi stok toko bertambah kembali 4 unit
        $inventory->refresh();
        $this->assertEquals($initialStock + 4, $inventory->stock);
    }

    /**
     * Warga tidak dapat membatalkan pesanan jika toko sudah mulai memproses barang.
     */
    public function test_customer_cannot_cancel_order_if_already_processing(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-FAIL-001',
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'order_type' => 'retail',
            'status' => 'processing',
            'subtotal' => 50000,
            'shipping_fee' => 5000,
            'platform_fee' => 1000,
            'total_amount' => 56000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Jl. Kawi No. 12',
        ]);

        $response = $this->actingAs($this->customer)->post('/user/orders/ORD-CANCEL-FAIL-001/cancel', [
            'cancel_reason' => 'Ingin batal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }
}
