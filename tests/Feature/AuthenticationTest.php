<?php

namespace Tests\Feature;

use App\Models\Territory;
use App\Models\User;
use Database\Seeders\CategoryAndProductSeeder;
use Database\Seeders\TerritorySeeder;
use Database\Seeders\UserAndMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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
     * Halaman login dapat dimuat dengan baik.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('Nomor HP / Email');
    }

    /**
     * Halaman pendaftaran dapat dimuat dengan baik.
     */
    public function test_register_page_renders_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Belanja');
        $response->assertSee('Nomor WhatsApp / HP Aktif');
    }

    /**
     * Warga (customer) dapat login menggunakan nomor HP.
     */
    public function test_customer_can_login_using_phone_number(): void
    {
        $response = $this->post('/login', [
            'login' => '08160000001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('storefront.index'));
        $this->assertAuthenticated();
        $this->assertEquals('customer', auth()->user()->role);
    }

    /**
     * Warga (customer) dapat login menggunakan alamat email.
     */
    public function test_customer_can_login_using_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'customer.gedog@myethek.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('storefront.index'));
        $this->assertAuthenticated();
    }

    /**
     * Login gagal jika kata sandi salah.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'login' => '08160000001',
            'password' => 'passwordsalah',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Mitra warung (sub_agent) login otomatis dialihkan ke /merchant.
     */
    public function test_merchant_login_redirects_to_merchant_portal(): void
    {
        $response = $this->post('/login', [
            'login' => '08140000001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('merchant.index'));
        $this->assertAuthenticated();
        $this->assertEquals('sub_agent', auth()->user()->role);
        $this->assertNotNull(session('merchant_portal_id'));
    }

    /**
     * Kurir ojol login otomatis dialihkan ke /driver.
     */
    public function test_driver_login_redirects_to_driver_portal(): void
    {
        $response = $this->post('/login', [
            'login' => '08150000001',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('driver.index'));
        $this->assertAuthenticated();
        $this->assertEquals('driver', auth()->user()->role);
    }

    /**
     * Warga baru dapat mendaftar akun dan langsung terautentikasi.
     */
    public function test_new_citizen_can_register(): void
    {
        $village = Territory::where('code', '35.72.03.1001')->firstOrFail();

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'phone' => '081987654321',
            'email' => 'budi.santoso@warga.id',
            'territory_id' => $village->id,
            'address' => 'Jl. Pahlawan No. 5 RT 01 RW 02, Gedog',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('storefront.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'phone' => '081987654321',
            'email' => 'budi.santoso@warga.id',
            'role' => 'customer',
            'territory_id' => $village->id,
        ]);
    }

    /**
     * Pengguna dapat melakukan logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::where('phone', '08160000001')->firstOrFail();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect(route('storefront.index'));
        $this->assertGuest();
    }
}
