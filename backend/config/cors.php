<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique([
        env('FRONTEND_URL', 'http://127.0.0.1:7357'),
        'http://127.0.0.1:7357',
        'http://localhost:7357',
    ])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-Correlation-ID'],
    'max_age' => 0,
    'supports_credentials' => false,
];
