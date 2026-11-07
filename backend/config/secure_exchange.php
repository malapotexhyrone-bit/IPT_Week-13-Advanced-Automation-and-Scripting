<?php

return [
    'key_base64' => env('SECURE_EXCHANGE_KEY_BASE64'),
    'auth_secret' => env('SECURE_EXCHANGE_AUTH_SECRET'),
    'client_id' => env('SECURE_EXCHANGE_CLIENT_ID', 'student-clearance-sender'),
    'expected_client_id' => env('SECURE_EXCHANGE_EXPECTED_CLIENT_ID', 'student-clearance-sender'),
    'sender' => env('SECURE_EXCHANGE_SENDER', 'student-clearance-system'),
    'recipient' => env('SECURE_EXCHANGE_RECIPIENT', 'registrar-clearance-receiver'),
    'purpose' => 'clearance_status_exchange',
    'receiver_url' => env(
        'SECURE_EXCHANGE_RECEIVER_URL',
        'https://localhost:8443/api/v1/secure-exchange/receive',
    ),
    'ca_certificate' => env(
        'SECURE_EXCHANGE_CA_CERTIFICATE',
        storage_path('https/ca.crt'),
    ),
    'request_skew_seconds' => (int) env('SECURE_EXCHANGE_REQUEST_SKEW_SECONDS', 60),
    'message_ttl_seconds' => (int) env('SECURE_EXCHANGE_MESSAGE_TTL_SECONDS', 300),
];
