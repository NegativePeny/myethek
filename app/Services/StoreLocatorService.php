<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Territory;

class StoreLocatorService
{
    /**
     * Resolusi toko utama untuk customer.
     * - Jika Pilot Mode aktif (PRD & SOT v1.0: BR-001, BR-011), selalu arahkan ke
     *   1 Koperasi Utama Kota Blitar tanpa auto-cascading fallback ke toko lain.
     * - Jika Pilot Mode nonaktif (Roadmap P2), jalankan Cascading Fallback 4-Tier:
     *   Desa (Sub-Agen) -> Kecamatan (Agen) -> Kabupaten (Distributor).
     */
    public function resolveMerchantForCustomer(?Territory $customerTerritory, array $requiredItems = []): array
    {
        if (config('myethek.pilot_mode', true)) {
            $pilotSlug = config('myethek.pilot_merchant_slug', 'gudang-pusat-koperasi-kota-blitar');
            $pilotMerchant = Merchant::with(['inventories', 'territory'])
                ->where('slug', $pilotSlug)
                ->first() ?? Merchant::with(['inventories', 'territory'])->first();

            $hierarchy = $customerTerritory ? $this->resolveHierarchyTree($customerTerritory) : [];

            return [
                'merchant' => $pilotMerchant,
                'served_by_tier' => 'pilot_cooperative',
                'fallback_applied' => false,
                'fallback_level' => null,
                'reason' => 'Pilot Kota Blitar: Satu Koperasi • Satu Merchant Utama • Satu Gudang/Toko',
                'hierarchy' => $hierarchy,
            ];
        }

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
     * Cari mitra aktif dan evaluasi syarat ketersediaan pada teritori dan tier tertentu.
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
     * Periksa ketersediaan stok available merchant terhadap daftar item keranjang (BR-005, WH-007).
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
            if (!$inventory->is_available || $inventory->available_stock < $neededQty) {
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
