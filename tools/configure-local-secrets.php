<?php

declare(strict_types=1);

$environmentPath = dirname(__DIR__).'/backend/.env';
$examplePath = dirname(__DIR__).'/backend/.env.example';

if (! is_file($environmentPath)) {
    if (! copy($examplePath, $environmentPath)) {
        throw new RuntimeException('The local environment file could not be created.');
    }
}

$contents = file_get_contents($environmentPath);
if ($contents === false) {
    throw new RuntimeException('The local environment file could not be read.');
}

$values = [
    'SECURE_EXCHANGE_KEY_BASE64' => base64_encode(random_bytes(32)),
    'SECURE_EXCHANGE_AUTH_SECRET' => bin2hex(random_bytes(32)),
];

$contents = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=false', $contents, 1);
$contents = preg_replace('/^DEMO_MODE_ENABLED=.*$/m', 'DEMO_MODE_ENABLED=false', $contents, 1);
$contents = preg_replace('/^LOG_LEVEL=.*$/m', 'LOG_LEVEL=info', $contents, 1);

foreach ($values as $name => $value) {
    $pattern = '/^'.preg_quote($name, '/').'=(.*)$/m';
    if (preg_match($pattern, $contents, $match) !== 1) {
        throw new RuntimeException("The $name placeholder is missing from .env.example.");
    }
    if (trim($match[1]) !== '') {
        continue;
    }
    $contents = preg_replace($pattern, $name.'='.$value, $contents, 1);
}

if (file_put_contents($environmentPath, $contents, LOCK_EX) === false) {
    throw new RuntimeException('The local secure-exchange settings could not be saved.');
}

fwrite(STDOUT, "Local secure-exchange secrets are configured without printing their values.\n");
