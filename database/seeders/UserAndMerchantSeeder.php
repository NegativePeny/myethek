<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantInventory;
use App\Models\Product;
use App\Models\Territory;
use App\Models\User;
use App\Models\UserAddress;
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

        // 1. Admin Koperasi / Superadmin
        $admin = User::create([
            'name' => 'Admin Induk Koperasi Jatim',
            'email' => 'admin@myethek.id',
            'phone' => '08110000001',
            'password' => $defaultPassword,
            'role' => 'admin',
            'territory_id' => $kotaBlitar->id,
            'address' => 'Jl. Merdeka No. 45, Kota Blitar',
        ]);
        Wallet::create(['user_id' => $admin->id, 'balance_available' => 100000000]);

        // 1b. Superadmin / Pengurus Koperasi Blitar (SOT Bab 4)
        $superAdmin = User::create([
            'name' => 'Pengurus Superadmin Mak Ethek',
            'email' => 'superadmin@myethek.id',
            'phone' => '08110000002',
            'password' => $defaultPassword,
            'role' => 'superadmin',
            'territory_id' => $kotaBlitar->id,
            'address' => 'Kantor Pengurus Koperasi Mak Ethek, Kota Blitar',
        ]);
        Wallet::create(['user_id' => $superAdmin->id, 'balance_available' => 0]);

        // 1c. Finance / Rekonsiliasi DOKU (SOT Bab 4)
        $financeUser = User::create([
            'name' => 'Staf Finance & Settlement DOKU',
            'email' => 'finance@myethek.id',
            'phone' => '08110000003',
            'password' => $defaultPassword,
            'role' => 'finance',
            'territory_id' => $kotaBlitar->id,
            'address' => 'Divisi Keuangan Koperasi Mak Ethek, Kota Blitar',
        ]);
        Wallet::create(['user_id' => $financeUser->id, 'balance_available' => 0]);

        // 1d. Admin Gudang / Petugas Picking & Packing (SOT Bab 4)
        $warehouseUser = User::create([
            'name' => 'Budi Petugas Gudang Blitar',
            'email' => 'gudang@myethek.id',
            'phone' => '08110000004',
            'password' => $defaultPassword,
            'role' => 'warehouse',
            'territory_id' => $kotaBlitar->id,
            'address' => 'Gudang Utama Koperasi Mak Ethek, Jl. Merdeka No. 45, Kota Blitar',
        ]);
        Wallet::create(['user_id' => $warehouseUser->id, 'balance_available' => 0]);

        // 2. Merchant Utama Pilot Kota Blitar (Satu Koperasi • Satu Merchant Utama • Satu Gudang/Toko)
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
            'min_order_amount' => 15000,
            'self_delivery_active' => true,
            'delivery_radius_km' => 25.0,
            'opening_time' => '06:00:00',
            'closing_time' => '20:00:00',
        ]);

        // 3. Agen (Tingkat Kecamatan Sananwetan - untuk Roadmap P2)
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

        // 4. Sub-Agen (Tingkat Desa/Kelurahan Gedog - untuk Roadmap P2)
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

        // 5. Driver KurirQ Mak Ethek
        $driverUser = User::create([
            'name' => 'Agus Driver KurirQ Blitar',
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

        // 6. Customer B2C Warga Gedog
        $customerGedog = User::create([
            'name' => 'Siti Khadijah',
            'email' => 'customer.gedog@myethek.id',
            'phone' => '08160000001',
            'password' => $defaultPassword,
            'role' => 'customer',
            'customer_type' => 'b2c',
            'territory_id' => $gedog->id,
            'address' => 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog',
            'latitude' => -8.109300,
            'longitude' => 112.189400,
        ]);
        Wallet::create(['user_id' => $customerGedog->id, 'balance_available' => 500000]);

        // Multi-alamat untuk Siti Khadijah (Rumah Sendiri & Rumah Orang Tua di Blitar - CUS-003, CUS-005)
        UserAddress::create([
            'user_id' => $customerGedog->id,
            'label' => 'Rumah Utama',
            'recipient_name' => 'Siti Khadijah',
            'recipient_phone' => '08160000001',
            'territory_id' => $gedog->id,
            'full_address' => 'Jl. Kawi No. 12 RT 01 RW 02, Kel. Gedog, Kota Blitar',
            'latitude' => -8.109300,
            'longitude' => 112.189400,
            'notes' => 'Pagar hijau depan musholla',
            'is_default' => true,
        ]);

        UserAddress::create([
            'user_id' => $customerGedog->id,
            'label' => 'Rumah Orang Tua (Ibu Aminah)',
            'recipient_name' => 'Ibu Hajah Aminah',
            'recipient_phone' => '08160000099',
            'territory_id' => $bendogerit->id,
            'full_address' => 'Jl. Ir. Soekarno No. 40, Kel. Bendogerit, Kec. Sananwetan, Kota Blitar',
            'latitude' => -8.098421,
            'longitude' => 112.179831,
            'notes' => 'Titip ke Ibu Aminah (kiriman anak dari luar kota)',
            'is_default' => false,
        ]);

        // 7. Customer B2C Warga Bendogerit
        $customerBendogerit = User::create([
            'name' => 'Rahmat Hidayat',
            'email' => 'customer.bendogerit@myethek.id',
            'phone' => '08170000001',
            'password' => $defaultPassword,
            'role' => 'customer',
            'customer_type' => 'b2c',
            'territory_id' => $bendogerit->id,
            'address' => 'Jl. Ir Soekarno No. 25, Kel. Bendogerit',
            'latitude' => -8.098421,
            'longitude' => 112.179831,
        ]);
        Wallet::create(['user_id' => $customerBendogerit->id, 'balance_available' => 500000]);

        // 8. Customer B2B Warung Kelontong Terverifikasi (B2B-001..005, Min Order Rp1.000.000)
        $b2bCustomer = User::create([
            'name' => 'H. Sugeng - Toko Kelontong Barokah (B2B)',
            'email' => 'warung.b2b@myethek.id',
            'phone' => '08180000001',
            'password' => $defaultPassword,
            'role' => 'customer',
            'customer_type' => 'b2b',
            'b2b_verified_at' => now(),
            'b2b_store_name' => 'Toko Kelontong Barokah Blitar',
            'territory_id' => $gedog->id,
            'address' => 'Jl. Tanjung No. 17, Kota Blitar',
            'latitude' => -8.102100,
            'longitude' => 112.165400,
        ]);
        Wallet::create(['user_id' => $b2bCustomer->id, 'balance_available' => 5000000]);

        UserAddress::create([
            'user_id' => $b2bCustomer->id,
            'label' => 'Toko Kelontong Barokah',
            'recipient_name' => 'H. Sugeng',
            'recipient_phone' => '08180000001',
            'territory_id' => $gedog->id,
            'full_address' => 'Jl. Tanjung No. 17, Kota Blitar',
            'latitude' => -8.102100,
            'longitude' => 112.165400,
            'notes' => 'Bongkar muat grosir di pintu samping toko',
            'is_default' => true,
        ]);

        // 9. Seeding Inventori Stok
        $products = Product::all();
        $racks = ['RAK-A1', 'RAK-A2', 'RAK-B1', 'RAK-B2', 'RAK-C1', 'RAK-C2', 'RAK-D1'];

        // Stok Gudang Utama Koperasi Kota Blitar: Kapasitas besar
        foreach ($products as $idx => $p) {
            MerchantInventory::create([
                'merchant_id' => $distributorMerchant->id,
                'product_id' => $p->id,
                'stock' => 500,
                'stock_reserved' => 0,
                'reorder_level' => 25,
                'rack_location' => $racks[$idx % count($racks)],
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }

        // Stok Agen: Kapasitas sedang
        foreach ($products as $idx => $p) {
            MerchantInventory::create([
                'merchant_id' => $agenMerchant->id,
                'product_id' => $p->id,
                'stock' => 100,
                'stock_reserved' => 0,
                'reorder_level' => 15,
                'rack_location' => $racks[$idx % count($racks)],
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }

        // Stok Sub-Agen Gedog: Kapasitas retail eceran
        foreach ($products as $idx => $p) {
            MerchantInventory::create([
                'merchant_id' => $subAgenMerchant->id,
                'product_id' => $p->id,
                'stock' => 25,
                'stock_reserved' => 0,
                'reorder_level' => 5,
                'rack_location' => $racks[$idx % count($racks)],
                'custom_price' => $p->base_price,
                'is_available' => true,
            ]);
        }
    }
}
