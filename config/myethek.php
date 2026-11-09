<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Pilot Kota Blitar (PRD v1.0 & SOT v1.0)
    |--------------------------------------------------------------------------
    | Satu Koperasi • Satu Merchant Utama • Satu Gudang/Toko
    | Jika true, seluruh transaksi B2C & B2B dilayani langsung oleh Koperasi
    | Kota Blitar tanpa auto-cascading fallback ke warung/agen lain (BR-001, BR-011).
    */
    'pilot_mode' => (bool) env('MYETHEK_PILOT_MODE', true),

    'pilot_merchant_slug' => env('MYETHEK_PILOT_MERCHANT_SLUG', 'gudang-pusat-koperasi-kota-blitar'),

    'service_city_code' => env('MYETHEK_SERVICE_CITY_CODE', '35.72'), // Kota Blitar
    'service_city_name' => 'Kota Blitar',

    /*
    |--------------------------------------------------------------------------
    | Aturan B2B & Target Komposisi Order (BR-003, BR-004)
    |--------------------------------------------------------------------------
    */
    'b2b_min_order_amount' => (float) env('MYETHEK_B2B_MIN_ORDER', 1000000),

    'target_mix' => [
        'b2c_percent' => 90,
        'b2b_percent' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Pengiriman KurirQ (BR-013, SOT 8.2)
    |--------------------------------------------------------------------------
    */
    'shipping' => [
        'provider' => env('MYETHEK_COURIER_PROVIDER', 'KurirQ'),
        'flat_rate_blitar' => (float) env('MYETHEK_KURIRQ_FLAT_RATE', 8000),
        'max_radius_km' => (float) env('MYETHEK_MAX_RADIUS_KM', 15.0),
        'platform_fee' => (float) env('MYETHEK_PLATFORM_FEE', 0), // Pada pilot 1 koperasi, fee platform default 0 (configurable)
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Integrasi Pembayaran DOKU (PAY-001..006, BR-014, SOT 8.1)
    |--------------------------------------------------------------------------
    */
    'doku' => [
        'environment' => env('DOKU_ENV', 'sandbox'),
        'client_id' => env('DOKU_CLIENT_ID', 'BRN-PILOT-BLITAR-001'),
        'secret_key' => env('DOKU_SECRET_KEY', 'SK-MAK-ETHEK-PILOT-BLITAR-2026'),
        'expiry_minutes' => 60,
        'fees' => [
            'qris_percent' => (float) env('DOKU_FEE_QRIS_PERCENT', 0.7),
            'shopeepay_flat' => (float) env('DOKU_FEE_SHOPEEPAY', 1500),
            'va_flat' => (float) env('DOKU_FEE_VA', 4500),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Keamanan OTP Pengiriman (BR-010, SOT 10.1)
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'expiry_minutes' => (int) env('MYETHEK_OTP_EXPIRY_MINUTES', 180),
        'max_attempts' => (int) env('MYETHEK_OTP_MAX_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Parameter Model Finansial & Operasional (PRD Bab 12 & 13)
    |--------------------------------------------------------------------------
    */
    'finance' => [
        'fixed_monthly_opex' => (float) env('MYETHEK_MONTHLY_OPEX', 12000000),
        'handling_cost_per_order' => (float) env('MYETHEK_HANDLING_COST', 1000),
    ],
];

