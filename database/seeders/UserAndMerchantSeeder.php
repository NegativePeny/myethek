<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndMerchantSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        // Ambil wilayah referensi
        $jatim = Territory::where('code', '35')->first();
        $kotaBlitar = Territory::where('code', '35.72')->first();
        $sananwetan = Territory::where('code', '35.72.03')->first();
        $gedog = Territory::where('code', '35.72.03.1001')->first();
        $bendogerit = Territory::where('code', '35.72.03.1002')->first();

        // 1. Admin Koperasi Jatim
        $admin = User::create([
            'name' => 'Admin Induk Koperasi Jatim',
            'email' => 'admin@myethek.id',
            'phone' => '08110000001',
            'password' => $defaultPassword,
            'role' => 'admin',
            'territory_id' => $jatim->id,
            'address' => 'Gedung Pusat Koperasi Jawa Timur, Surabaya',
        ]);
        Wallet::create(['user_id' => $admin->id, 'balance_available' => 100000000]);

        // 2. Distributor (Tingkat Kota Blitar)
        $distributorUser = User::create([
            'name' => 'Koperasi Distribusi Blitar',
            'email' => 'distributor.blitar@myethek.id',
            'phone' => '08120000001',
            'password' => $defaultPassword,
            'role' => 'distributor',
            'territory_id' => $kotaBlitar->id,
            'address' => 'Jl. Merdeka No. 45, Kota Blitar',
            'latitude' => -8.098254,
            'longitude' => 112.168153,
        ]);
        Wallet::create(['user_id' => $distributorUser->id, 'balance_available' => 25000000]);

        $distributorMerchant = Merchant::create([
            'user_id' => $distributorUser->id,
            'territory_id' => $kotaBlitar->id,
            'tier' => 'distributor',
            'store_name' => 'Gudang Pusat Koperasi Kota Blitar',
            'slug' => 'gudang-pusat-koperasi-kota-blitar',
            'phone' => '08120000001',
            'address' => 'Jl. Merdeka No. 45, Kota Blitar',
            'latitude' => -8.098254,
            'longitude' => 112.168153,
            'status' => 'active',
            'is_open' => true,
            'min_order_amount' => 500000,
            'self_delivery_active' => true,
            'delivery_radius_km' => 25.0,
            'opening_time' => '06:00:00',
            'closing_time' => '20:00:00',
        ]);

        // 3. Agen (Tingkat Kecamatan Sananwetan)
        $agenUser = User::create([
            'name' => 'Koperasi Sananwetan Berkah',
            'email' => 'agen.sananwetan@myethek.id',
            'phone' => '08130000001',
            'password' => $defaultPassword,
            'role' => 'agent',
            'territory_id' => $sananwetan->id,
            'address' => 'Jl. Kalimantan No. 88, Sananwetan, Kota Blitar',
            'latitude' => -8.106883,
            'longitude' => 112.181297,
        ]);
        Wallet::create(['user_id' => $agenUser->id, 'balance_available' => 10000000]);

        $agenMerchant = Merchant::create([
            'user_id' => $agenUser->id,
            'territory_id' => $sananwetan->id,
            'tier' => 'agent',
            'store_name' => 'Koperasi Sananwetan Berkah',
            'slug' => 'koperasi-sananwetan-berkah',
            'phone' => '08130000001',
            'address' => 'Jl. Kalimantan No. 88, Sananwetan, Kota Blitar',
            'latitude' => -8.106883,
            'longitude' => 112.181297,
            'status' => 'active',
            'is_open' => true,
            'min_order_amount' => 50000,
            'self_delivery_active' => true,
            'delivery_radius_km' => 12.0,
            'opening_time' => '06:00:00',
            'closing_time' => '21:00:00',
        ]);

        // 4. Sub-Agen (Tingkat Desa/Kelurahan Gedog)
        $subAgenUser = User::create([
            'name' => 'Bu Slamet - Warung Gedog',
            'email' => 'warung.gedog@myethek.id',
            'phone' => '08140000001',
            'password' => $defaultPassword,
            'role' => 'sub_agent',
            'territory_id' => $gedog->id,
            'address' => 'Jl. Pahlawan Gedog RT 02 RW 03, Kel. Gedog',
            'latitude' => -8.109156,
            'longitude' => 112.189544,
        ]);
        Wallet::create(['user_id' => $subAgenUser->id, 'balance_available' => 2500000]);

        $subAgenMerchant = Merchant::create([
            'user_id' => $subAgenUser->id,
            'territory_id' => $gedog->id,
            'tier' => 'sub_agent',
            'store_name' => 'Warung Berkah Mak Ethek Gedog',
            'slug' => 'warung-berkah-mak-ethek-gedog',
            'phone' => '08140000001',
            'address' => 'Jl. Pahlawan Gedog RT 02 RW 03, Kel. Gedog',
            'latitude' => -8.109156,
            'longitude' => 112.189544,
            'status' => 'active',
            'is_open' => true,
            'min_order_amount' => 15000,
            'self_delivery_active' => true,
            'delivery_radius_km' => 5.0,
            'opening_time' => '06:00:00',
            'closing_time' => '21:30:00',
        ]);

        // 5. Driver Ojol Desa Gedog
        $driverUser = User::create([
            'name' => 'Agus Driver Gedog',
            'email' => 'driver.gedog@myethek.id',
            'phone' => '08150000001',
            'password' => $defaultPassword,
            'role' => 'driver',
            'territory_id' => $gedog->id,
            'address' => 'Gedog Tengah Gang 1, Kel. Gedog',
            'latitude' => -8.109500,
            'longitude' => 112.189200,
        ]);
        Wallet::create(['user_id' => $driverUser->id, 'balance_available' => 350000]);

        // 6. Customer Warga Gedog
        $customerGedog = User::create([
            'name' => 'Siti Khadijah',
            'email' => 'customer.gedog@myethek.id',
            'phone' => '08160000001',
            'password' => $defaultPassword,
            'role' => 'customer',
            'territory_id' => $gedog->id,
            'address' => 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog',
            'latitude' => -8.109300,
            'longitude' => 112.189400,
        ]);
        Wallet::create(['user_id' => $customerGedog->id, 'balance_available' => 500000]);

        // 7. Customer Warga Bendogerit (Desa tanpa Sub-Agen untuk uji coba otomatis fallback ke Agen Kecamatan)
        $customerBendogerit = User::create([
            'name' => 'Rahmat Hidayat',
            'email' => 'customer.bendogerit@myethek.id',
            'phone' => '08170000001',
            'password' => $defaultPassword,
            'role' => 'customer',
            'territory_id' => $bendogerit->id,
            'address' => 'Jl. Ir Soekarno No. 25, Kel. Bendogerit',
            'latitude' => -8.098421,
            'longitude' => 112.179831,
        ]);
        Wallet::create(['user_id' => $customerBendogerit->id, 'balance_available' => 500000]);

        // 8. Seeding Inventori Stok
        $products = Product::all();

        // Stok Distributor: Kapasitas besar
        foreach ($products as $p) {
            MerchantInventory::create([
                'merchant_id' => $distributorMerchant->id,
                'product_id' => $p->id,
                'stock' => 500,
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }

        // Stok Agen: Kapasitas sedang
        foreach ($products as $p) {
            MerchantInventory::create([
                'merchant_id' => $agenMerchant->id,
                'product_id' => $p->id,
                'stock' => 100,
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }

        // Stok Sub-Agen Gedog: Kapasitas retail eceran
        foreach ($products as $p) {
            MerchantInventory::create([
                'merchant_id' => $subAgenMerchant->id,
                'product_id' => $p->id,
                'stock' => 25,
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }
    }
}
