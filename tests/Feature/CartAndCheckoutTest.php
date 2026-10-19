<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndCheckoutTest extends TestCase
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
     * Pelanggan dapat menambahkan produk ke keranjang belanja.
     */
    public function test_user_can_add_product_to_cart(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();
        $this->session(['active_village_id' => $gedog->id]);

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        $response = $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('myethek_cart');

        $cart = session('myethek_cart');
        $this->assertCount(1, $cart['items']);
        $this->assertEquals(2, $cart['items'][$product->id]['quantity']);
    }

    /**
     * Single-Merchant Enforcement: Keranjang hanya menampung dari 1 toko.
     * Jika beralih toko, keranjang otomatis disesuaikan ke toko baru.
     */
    public function test_single_merchant_enforcement_resets_cart_when_switching_merchant(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();
        $this->session(['active_village_id' => $gedog->id]);

        $product1 = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        // Tambah dari Sub-Agen Gedog
        $this->post('/cart/add', [
            'product_id' => $product1->id,
            'quantity' => 1,
        ]);

        $subAgen = Merchant::where('slug', 'warung-berkah-mak-ethek-gedog')->firstOrFail();
        $this->assertEquals($subAgen->id, session('myethek_cart')['merchant_id']);

        // Ganti wilayah ke Bendogerit (yang dilayani oleh Agen Sananwetan)
        $bendogerit = Territory::where('code', '35.72.03.1002')->firstOrFail();
        $this->session(['active_village_id' => $bendogerit->id]);

        $product2 = Product::where('slug', 'minyakita-bantal-1-liter')->firstOrFail();

        // Tambah barang dari toko Agen Sananwetan
        $this->post('/cart/add', [
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $agen = Merchant::where('slug', 'koperasi-sananwetan-berkah')->firstOrFail();
        $cart = session('myethek_cart');

        // Pastikan merchant_id sekarang adalah Agen Sananwetan dan tidak bercampur dengan toko sebelumnya
        $this->assertEquals($agen->id, $cart['merchant_id']);
        $this->assertArrayNotHasKey($product1->id, $cart['items']);
        $this->assertArrayHasKey($product2->id, $cart['items']);
    }

    /**
     * Pelanggan dapat membuka halaman keranjang belanja dan melihat rincian item.
     */
    public function test_user_can_view_cart_page(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();
        $this->session(['active_village_id' => $gedog->id]);

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('Beras Lahap Premium 5kg');
        $response->assertSee('Warung Berkah Mak Ethek Gedog');
        $response->assertSee('Rp 74.000');
    }

    /**
     * Pelanggan dapat memperbarui dan menghapus item dari keranjang.
     */
    public function test_user_can_update_and_remove_item(): void
    {
        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();
        $this->session(['active_village_id' => $gedog->id]);

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Update kuantitas menjadi 3
        $this->post('/cart/update', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $cart = session('myethek_cart');
        $this->assertEquals(3, $cart['items'][$product->id]['quantity']);

        // Hapus item
        $this->post('/cart/remove', [
            'product_id' => $product->id,
        ]);

        $cartAfterRemove = session('myethek_cart');
        $this->assertEmpty($cartAfterRemove['items']);
    }

    /**
     * Formula tarif ongkir ojol lokal pedesaan (Rp6.000 + Rp2.000/km).
     */
    public function test_ojol_fee_calculation(): void
    {
        $deliveryService = new DeliveryService();

        // Jarak 0.8 km (dibulatkan ke 1 km) -> Rp6.000
        $fee1 = $deliveryService->calculateShippingFee('ojol', 0.8);
        $this->assertEquals(6000, $fee1);

        // Jarak 2.3 km (dibulatkan ke 3 km) -> 6000 + (3 - 1)*2000 = Rp10.000
        $fee2 = $deliveryService->calculateShippingFee('ojol', 2.3);
        $this->assertEquals(10000, $fee2);

        // Diantar warung -> flat Rp5.000
        $feeWarung = $deliveryService->calculateShippingFee('merchant_courier', 4.0);
        $this->assertEquals(5000, $feeWarung);

        // Ambil sendiri -> Rp0
        $feePickup = $deliveryService->calculateShippingFee('self_pickup', 10.0);
        $this->assertEquals(0, $feePickup);
    }

    /**
     * Tamu diarahkan ke login saat mengakses halaman checkout.
     */
    public function test_guest_is_redirected_to_login_when_accessing_checkout(): void
    {
        $response = $this->get('/checkout');
        $response->assertRedirect('/login');
    }

    /**
     * Alur checkout lengkap: membuat Order, OrderItem, Delivery, dan EscrowTransaction.
     */
    public function test_user_can_complete_checkout_and_create_order(): void
    {
        $customer = User::where('phone', '08160000001')->firstOrFail();
        $this->actingAs($customer);

        $gedog = Territory::where('code', '35.72.03.1001')->firstOrFail();
        $this->session(['active_village_id' => $gedog->id]);

        $product = Product::where('slug', 'beras-lahap-premium-5kg')->firstOrFail();

        // Tambah produk ke keranjang
        $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Kirim data checkout
        $response = $this->post('/checkout', [
            'recipient_name' => 'Siti Khadijah',
            'phone' => '08160000001',
            'shipping_address' => 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog',
            'delivery_type' => 'ojol',
            'payment_method' => 'qris',
            'notes' => 'Pesanan uji coba',
        ]);

        $response->assertRedirect();

        // Verifikasi data tersimpan di tabel database
        $this->assertDatabaseHas('orders', [
            'shipping_address' => 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog',
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('deliveries', [
            'delivery_type' => 'ojol',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('escrow_transactions', [
            'status' => 'held',
        ]);

        // Verifikasi keranjang telah dikosongkan setelah pesanan dibuat
        $this->assertEmpty(session('myethek_cart')['items'] ?? []);
    }
}
