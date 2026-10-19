<?php

namespace App\Services;

use App\Models\Merchant;

class DeliveryService
{
    public const BASE_OJOL_FEE = 6000;
    public const PER_KM_OJOL_FEE = 2000;
    public const WARUNG_COURIER_FEE = 5000;
    public const PLATFORM_FEE = 1000;

    /**
     * Hitung jarak Haversine (km) antara koordinat toko dan pelanggan dengan faktor koreksi darat pedesaan (1.3x).
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

        // Faktor koreksi rute darat pedesaan Jawa Timur (1.3x)
        $routeDistance = $straightDistance * 1.3;

        return round(max(0.5, $routeDistance), 2);
    }

    /**
     * Hitung tarif ongkos kirim berdasarkan jenis pengiriman dan jarak.
     */
    public function calculateShippingFee(string $deliveryType, float $distanceKm): float
    {
        switch ($deliveryType) {
            case 'ojol':
                // Rumus PRD: Rp6.000 + (max(1, ceil(jarak)) - 1) * Rp2.000
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
    public function calculateTotal(float $subtotal, string $deliveryType, float $distanceKm): array
    {
        $shippingFee = $this->calculateShippingFee($deliveryType, $distanceKm);
        $platformFee = (float) self::PLATFORM_FEE;
        $grandTotal = $subtotal + $shippingFee + $platformFee;

        return [
            'subtotal' => $subtotal,
            'delivery_type' => $deliveryType,
            'distance_km' => $distanceKm,
            'shipping_fee' => $shippingFee,
            'platform_fee' => $platformFee,
            'total_amount' => $grandTotal,
        ];
    }
}
