<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Territory;
use Illuminate\Support\Collection;

class StoreLocatorService
{
    /**
     * Resolusi toko utama untuk customer dengan logika Cascading Fallback:
     * Desa (Sub-Agen) -> Kecamatan (Agen) -> Kabupaten (Distributor).
     *
     * @param Territory $customerTerritory Wilayah aktif customer (idealnya level 'village')
     * @param array $requiredItems Format: [['product_id' => int, 'quantity' => int]]
     * @return array Metadata hasil pencarian toko dan tier yang melayani
     */
    public function resolveMerchantForCustomer(?Territory $customerTerritory, array $requiredItems = []): array
    {
        if (!$customerTerritory) {
            return [
                'merchant' => null,
                'served_by_tier' => null,
                'fallback_applied' => false,
                'fallback_level' => null,
                'reason' => 'Wilayah customer belum ditentukan.',
                'hierarchy' => [],
            ];
        }

        $hierarchy = $this->resolveHierarchyTree($customerTerritory);

        // 1. Coba cari Sub-Agen di tingkat Desa/Kelurahan
        if (isset($hierarchy['village'])) {
            $village = $hierarchy['village'];
            $villageMerchant = $this->findEligibleMerchantInTerritory($village->id, 'sub_agent', $requiredItems);

            if ($villageMerchant) {
                return [
                    'merchant' => $villageMerchant,
                    'served_by_tier' => 'sub_agent',
                    'fallback_applied' => false,
                    'fallback_level' => null,
                    'reason' => 'Toko Sub-Agen desa tersedia dan aktif',
                    'hierarchy' => $hierarchy,
                ];
            }
        }

        // 2. Cascading Fallback Tingkat 1: Agen di Kecamatan induk
        if (isset($hierarchy['district'])) {
            $district = $hierarchy['district'];
            $districtMerchant = $this->findEligibleMerchantInTerritory($district->id, 'agent', $requiredItems);

            if ($districtMerchant) {
                return [
                    'merchant' => $districtMerchant,
                    'served_by_tier' => 'agent',
                    'fallback_applied' => true,
                    'fallback_level' => 'district',
                    'reason' => 'Sub-Agen desa tidak tersedia/tutup. Dialihkan otomatis ke Agen Kecamatan (' . $district->name . ')',
                    'hierarchy' => $hierarchy,
                ];
            }
        }

        // 3. Cascading Fallback Tingkat 2: Distributor di Kabupaten/Kota induk
        if (isset($hierarchy['regency'])) {
            $regency = $hierarchy['regency'];
            $regencyMerchant = $this->findEligibleMerchantInTerritory($regency->id, 'distributor', $requiredItems);

            if ($regencyMerchant) {
                return [
                    'merchant' => $regencyMerchant,
                    'served_by_tier' => 'distributor',
                    'fallback_applied' => true,
                    'fallback_level' => 'regency',
                    'reason' => 'Sub-Agen desa dan Agen kecamatan tidak tersedia. Dialihkan ke Distributor Kabupaten (' . $regency->name . ')',
                    'hierarchy' => $hierarchy,
                ];
            }
        }

        // 4. Tidak ada penjual aktif di seluruh tingkatan
        return [
            'merchant' => null,
            'served_by_tier' => null,
            'fallback_applied' => false,
            'fallback_level' => null,
            'reason' => 'Belum ada mitra toko aktif di wilayah ini.',
            'hierarchy' => $hierarchy,
        ];
    }

    /**
     * Cari mitra aktif dan evaluasi 5 syarat ketersediaan pada teritori dan tier tertentu.
     */
    public function findEligibleMerchantInTerritory(int $territoryId, string $tier, array $requiredItems = []): ?Merchant
    {
        $merchants = Merchant::query()
            ->with(['inventories', 'territory'])
            ->where('territory_id', $territoryId)
            ->where('tier', $tier)
            ->where('status', 'active')
            ->where('is_open', true)
            ->get();

        foreach ($merchants as $merchant) {
            if ($this->checkInventoryAvailability($merchant, $requiredItems)) {
                return $merchant;
            }
        }

        return null;
    }

    /**
     * Periksa ketersediaan stok merchant terhadap daftar item keranjang.
     */
    protected function checkInventoryAvailability(Merchant $merchant, array $requiredItems): bool
    {
        if (empty($requiredItems)) {
            return true;
        }

        $inventories = $merchant->inventories->keyBy('product_id');

        foreach ($requiredItems as $item) {
            $productId = $item['product_id'];
            $neededQty = $item['quantity'] ?? 1;

            if (!isset($inventories[$productId])) {
                return false;
            }

            $inventory = $inventories[$productId];
            if (!$inventory->is_available || $inventory->stock < $neededQty) {
                return false;
            }
        }

        return true;
    }

    /**
     * Membangun peta struktur hirarki dari level terendah hingga provinsi.
     */
    public function resolveHierarchyTree(Territory $territory): array
    {
        $tree = [];
        $current = $territory;

        while ($current) {
            $tree[$current->level] = $current;
            $current = $current->parent;
        }

        return $tree;
    }
}
