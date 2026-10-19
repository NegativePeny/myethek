<?php

namespace Database\Seeders;

use App\Models\Territory;
use Illuminate\Database\Seeder;

class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Provinsi
        $jatim = Territory::create([
            'level' => 'province',
            'parent_id' => null,
            'code' => '35',
            'name' => 'Jawa Timur',
            'latitude' => -7.5360639,
            'longitude' => 112.2384017,
        ]);

        // 2. Kabupaten / Kota (Regency)
        $kotaBlitar = Territory::create([
            'level' => 'regency',
            'parent_id' => $jatim->id,
            'code' => '35.72',
            'name' => 'Kota Blitar',
            'latitude' => -8.098254,
            'longitude' => 112.168153,
        ]);

        $kabBlitar = Territory::create([
            'level' => 'regency',
            'parent_id' => $jatim->id,
            'code' => '35.05',
            'name' => 'Kabupaten Blitar',
            'latitude' => -8.130541,
            'longitude' => 112.195240,
        ]);

        // 3. Kecamatan (District) di Kota Blitar
        $sananwetan = Territory::create([
            'level' => 'district',
            'parent_id' => $kotaBlitar->id,
            'code' => '35.72.03',
            'name' => 'Kecamatan Sananwetan',
            'latitude' => -8.106883,
            'longitude' => 112.181297,
        ]);

        $kepanjenkidul = Territory::create([
            'level' => 'district',
            'parent_id' => $kotaBlitar->id,
            'code' => '35.72.01',
            'name' => 'Kecamatan Kepanjenkidul',
            'latitude' => -8.089400,
            'longitude' => 112.164300,
        ]);

        $sukorejo = Territory::create([
            'level' => 'district',
            'parent_id' => $kotaBlitar->id,
            'code' => '35.72.02',
            'name' => 'Kecamatan Sukorejo',
            'latitude' => -8.101200,
            'longitude' => 112.152100,
        ]);

        // 4. Kelurahan/Desa (Village) di Kecamatan Sananwetan
        $gedog = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1001',
            'name' => 'Kelurahan Gedog',
            'latitude' => -8.109156,
            'longitude' => 112.189544,
        ]);

        $bendogerit = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1002',
            'name' => 'Kelurahan Bendogerit',
            'latitude' => -8.098421,
            'longitude' => 112.179831,
        ]);

        $kelSananwetan = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1003',
            'name' => 'Kelurahan Sananwetan',
            'latitude' => -8.105120,
            'longitude' => 112.176410,
        ]);

        $plosokerep = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1004',
            'name' => 'Kelurahan Plosokerep',
            'latitude' => -8.115200,
            'longitude' => 112.185300,
        ]);

        $rembang = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1005',
            'name' => 'Kelurahan Rembang',
            'latitude' => -8.121300,
            'longitude' => 112.193200,
        ]);

        $klampok = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1006',
            'name' => 'Kelurahan Klampok',
            'latitude' => -8.128400,
            'longitude' => 112.178900,
        ]);

        $karangtengah = Territory::create([
            'level' => 'village',
            'parent_id' => $sananwetan->id,
            'code' => '35.72.03.1007',
            'name' => 'Kelurahan Karangtengah',
            'latitude' => -8.100500,
            'longitude' => 112.190100,
        ]);
    }
}
