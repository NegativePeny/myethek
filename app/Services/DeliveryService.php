<?php

namespace App\Services;

use App\Models\Territory;

class DeliveryService
{
    public const BASE_OJOL_FEE = 6000;
    public const PER_KM_OJOL_FEE = 2000;
    public const WARUNG_COURIER_FEE = 5000;
    public const PLATFORM_FEE = 1000;

    /**
     * Validasi apakah wilayah berada di dalam area layanan Kota Blitar (CUS-004).
     */
    public function isWithinBlitarServiceArea(?Territory $territory): bool
    {
        if (!$territory) {
            return false;
        }

        $cityCode = config('myethek.service_city_code', '35.72');
        return str_starts_with((string) $territory->code, $cityCode);
    }

    /**
     * Hitung jarak Haversine (km) antara koordinat toko dan pelanggan dengan faktor koreksi darat (1.3x).
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $straightDistance = $earthRadiusKm * $c;

        $routeDistance = $straightDistance * 1.3;

        return round(max(0.5, $routeDistance), 2);
    }

    /**
     * Hitung tarif ongkos kirim berdasarkan jenis pengiriman dan mode Pilot KurirQ (BR-013).
     */
    public function calculateShippingFee(string $deliveryType, float $distanceKm): float
    {
        if ($deliveryType === 'kurirq' || (config('myethek.pilot_mode', false) && $deliveryType === 'ojol')) {
            return (float) config('myethek.shipping.flat_rate_blitar', 8000);
        }

        switch ($deliveryType) {
            case 'ojol':
                $billedKm = max(1, (int) ceil($distanceKm));
                return (float) (self::BASE_OJOL_FEE + (($billedKm - 1) * self::PER_KM_OJOL_FEE));

            case 'merchant_courier':
                return (float) self::WARUNG_COURIER_FEE;

            case 'self_pickup':
            default:
                return 0.0;
        }
    }

    /**
     * Hitung seluruh komponen biaya pesanan (subtotal, shipping, platform fee, grand total).
     */
    public function calculateTotal(float $subtotal, string $deliveryType, float $distanceKm, float $discount = 0): array
    {
        $shippingFee = $this->calculateShippingFee($deliveryType, $distanceKm);
        $platformFee = config('myethek.pilot_mode', false)
            ? (float) config('myethek.shipping.platform_fee', 0)
            : (float) self::PLATFORM_FEE;

        $grandTotal = max(0, $subtotal - $discount) + $shippingFee + $platformFee;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'delivery_type' => $deliveryType,
            'distance_km' => $distanceKm,
            'shipping_fee' => $shippingFee,
            'platform_fee' => $platformFee,
            'total_amount' => $grandTotal,
        ];
    }
}
