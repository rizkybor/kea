<?php

return [

    /*
    | Gateway aktif: "midtrans" (utama), "simulator" (lokal/staging tanpa kredensial).
    | Xendit disiapkan sebagai cadangan lewat kontrak PaymentGateway yang sama (ADR-08).
    */
    'default' => env('PAYMENT_GATEWAY', 'simulator'),

    'reservation_minutes' => 30,

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'va_bank' => env('MIDTRANS_VA_BANK', 'bca'),
    ],

    'simulator' => [
        'enabled' => (bool) env('PAYMENT_SIMULATOR_ENABLED', env('APP_ENV') !== 'production'),
    ],

];
