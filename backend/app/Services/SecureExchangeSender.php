<?php

namespace App\Services;

use App\Exceptions\SecureExchangeException;
use App\Models\Clearance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class SecureExchangeSender
{
    public function __construct(private readonly SecureClearanceExchange $exchange) {}

    public function send(Clearance $clearance, ?string $correlationId): array
    {
        $url = (string) config('secure_exchange.receiver_url');
        $caCertificate = (string) config('secure_exchange.ca_certificate');
        $secret = (string) config('secure_exchange.auth_secret');
        if (! str_starts_with($url, 'https://')) {
            throw new SecureExchangeException(
                'HTTPS_REQUIRED',
                'The secure receiver must use HTTPS.',
                503,
            );
        }
        if (! is_file($caCertificate) || $secret === '' || strlen($secret) < 32) {
            throw new SecureExchangeException(
                'SECURE_EXCHANGE_NOT_CONFIGURED',
                'The receiver trust or service credential is unavailable.',
                503,
            );
        }

        $envelope = $this->exchange->createEnvelope($clearance);
        try {
            $body = json_encode(
                $envelope,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException) {
            throw new SecureExchangeException(
                'MESSAGE_ENCODING_FAILED',
                'The protected message could not be encoded.',
                500,
            );
        }
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp."\n".$body, $secret);

        try {
            $response = Http::withOptions(['verify' => $caCertificate])
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Exchange-Client' => (string) config('secure_exchange.client_id'),
                    'X-Exchange-Timestamp' => $timestamp,
                    'X-Exchange-Signature' => $signature,
                    'X-Correlation-ID' => $correlationId,
                ])
                ->withBody($body, 'application/json')
                ->timeout(5)
                ->post($url);
        } catch (ConnectionException) {
            throw new SecureExchangeException(
                'SECURE_RECEIVER_UNAVAILABLE',
                'The secure receiver is temporarily unavailable.',
                503,
            );
        }

        if (! $response->successful()) {
            $code = $response->json('error.code', 'SECURE_RECEIVER_REJECTED');
            throw new SecureExchangeException(
                (string) $code,
                'The secure receiver rejected the protected message.',
                $response->status() >= 400 && $response->status() < 600
                    ? $response->status()
                    : 502,
            );
        }

        Log::info('secure_exchange.message_sent', [
            'event' => 'secure_exchange.message_sent',
            'message_id' => $envelope['message_id'],
            'clearance_reference' => $response->json('data.clearance_reference'),
            'receiver_status' => $response->status(),
            'correlation_id' => $correlationId,
        ]);

        return (array) $response->json('data', []);
    }
}
