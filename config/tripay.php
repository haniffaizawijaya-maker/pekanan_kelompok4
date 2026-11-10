<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tripay QRIS dinamis
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk Fase 4 (TripayService memakai Http facade,
    | tanpa package SDK). Jangan pernah menulis kunci asli di file
    | ini atau di commit. Kunci hanya di file .env lokal.
    |
    | Channel QRIS dinamis yang dipakai adalah QRIS2. Channel bernama
    | QRIS di Tripay adalah QRIS by ShopeePay, jadi jangan tertukar.
    |
    */

    'mode' => env('TRIPAY_MODE', 'sandbox'),

    'merchant_code' => env('TRIPAY_MERCHANT_CODE', ''),

    'api_key' => env('TRIPAY_API_KEY', ''),

    'private_key' => env('TRIPAY_PRIVATE_KEY', ''),

    'qris_channel' => env('TRIPAY_QRIS_CHANNEL', 'QRIS2'),

    'expired_hours' => (int) env('TRIPAY_EXPIRED_HOURS', 24),

    'base_url' => env('TRIPAY_MODE', 'sandbox') === 'production'
        ? 'https://tripay.co.id/api/'
        : 'https://tripay.co.id/api-sandbox/',
];
