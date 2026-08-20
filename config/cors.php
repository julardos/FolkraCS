<?php

return [
    'paths' => [
        'api/*',
        'login',
        'logout',
        'forgot-password',
        'reset-password/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\.)*folkra\.co$#i',
        '#^https?://([a-z0-9-]+\.)*folkra-cs\.test$#i',
        '#^https?://([a-z0-9-]+\.)*localhost(:\d+)?$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

