<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // GANTI sesuai port asli frontend_bk & frontend_sekre kamu.
    // Cek di terminal pas jalanin `npm run dev` di masing-masing folder.
    'allowed_origins' => [
        'http://localhost:5173', // contoh: frontend_bk
        'http://localhost:5174', // contoh: frontend_sekre
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // WAJIB true - soalnya sisi sekre pakai session/cookie (bukan token),
    // dan cookie cross-origin cuma bisa jalan kalau ini true.
    'supports_credentials' => true,

];
