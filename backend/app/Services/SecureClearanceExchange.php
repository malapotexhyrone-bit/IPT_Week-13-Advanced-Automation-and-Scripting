<?php

namespace App\Services;

use App\Exceptions\SecureExchangeException;
use App\Models\Clearance;
use App\Models\SecureExchangeReceipt;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class SecureClearanceExchange
{
    private const CIPHER = 'aes-256-gcm';

    public function createEnvelope(Clearance $clearance): array
    {
        $clearance->loadMissing([
            'academicTerm',
            'officeTasks.office',
            'officeTasks.deficiencies',
        ]);

        $issuedAt = CarbonImmutable::now('UTC');
        $metadata = [
            'version' => 1,
            'message_id' => (string) Str::uuid(),
            'issued_at' => $issuedAt->toIso8601ZuluString(),
            'expires_at' => $issuedAt
                ->addSeconds(max(30, (int) config('secure_exchange.message_ttl_seconds', 300)))
                ->toIso8601ZuluString(),
            'sender' => (string) config('secure_exchange.sender'),
            'recipient' => (string) config('secure_exchange.recipient'),
            'purpose' => (string) config('secure_exchange.purpose'),
        ];
        $payload = $this->minimizedPayload($clearance);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $this->encode($payload),
            self::CIPHER,
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $this->encode($metadata),
            16,
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new SecureExchangeException(
                'ENCRYPTION_FAILED',
                'The protected message could not be created.',
                500,
            );
        }

        return $metadata + [
            'nonce' => base64_encode($nonce),
            'ciphertext' => base64_encode($ciphertext),
            'tag' => base64_encode($tag),
        ];
    }

    public function receive(array $envelope, ?string $correlationId): array
    {
        $metadata = array_intersect_key($envelope, array_flip([
            'version',
            'message_id',
            'issued_at',
            'expires_at',
            'sender',
            'recipient',
            'purpose',
        ]));
        $nonce = $this->decodeBinary((string) ($envelope['nonce'] ?? ''), 12);
        $ciphertext = $this->decodeBinary((string) ($envelope['ciphertext'] ?? ''), null);
        $tag = $this->decodeBinary((string) ($envelope['tag'] ?? ''), 16);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $this->encode($metadata),
        );

        if ($plaintext === false) {
            throw new SecureExchangeException(
                'ALTERED_OR_WRONG_KEY',
                'The protected message failed integrity verification.',
                422,
            );
        }

        $this->assertMetadata($metadata);

        try {
            $payload = json_decode($plaintext, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new SecureExchangeException(
                'PROTECTED_PAYLOAD_INVALID',
                'The protected payload is not valid JSON.',
                422,
            );
        }

        if (! is_array($payload)
            || ! isset($payload['clearance_reference'], $payload['overall_status'], $payload['offices'])
            || ! is_array($payload['offices'])) {
            throw new SecureExchangeException(
                'PROTECTED_PAYLOAD_INVALID',
                'The protected payload does not match the approved schema.',
                422,
            );
        }

        try {
            DB::transaction(function () use ($metadata, $payload, $envelope): void {
                SecureExchangeReceipt::query()->create([
                    'message_id' => $metadata['message_id'],
                    'envelope_hash' => hash('sha256', $this->encode($envelope)),
                    'sender' => $metadata['sender'],
                    'recipient' => $metadata['recipient'],
                    'clearance_reference' => $payload['clearance_reference'],
                    'overall_status' => $payload['overall_status'],
                    'issued_at' => $metadata['issued_at'],
                    'expires_at' => $metadata['expires_at'],
                    'received_at' => now(),
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicate($exception)) {
                throw new SecureExchangeException(
                    'REPLAYED_MESSAGE',
                    'This protected message was already processed.',
                    409,
                );
            }

            throw $exception;
        }

        Log::info('secure_exchange.message_accepted', [
            'event' => 'secure_exchange.message_accepted',
            'message_id' => $metadata['message_id'],
            'clearance_reference' => $payload['clearance_reference'],
            'overall_status' => $payload['overall_status'],
            'office_count' => count($payload['offices']),
            'correlation_id' => $correlationId,
        ]);

        return [
            'message_id' => $metadata['message_id'],
            'clearance_reference' => $payload['clearance_reference'],
            'overall_status' => $payload['overall_status'],
            'office_count' => count($payload['offices']),
            'received_at' => now()->toIso8601String(),
        ];
    }

    private function minimizedPayload(Clearance $clearance): array
    {
        return [
            'schema' => 'student-clearance.secure-status.v1',
            'clearance_reference' => sprintf('CLR-%06d', $clearance->id),
            'term_code' => $clearance->academicTerm->code,
            'overall_status' => $clearance->status,
            'initiated_at' => $clearance->initiated_at?->toIso8601String(),
            'completed_at' => $clearance->completed_at?->toIso8601String(),
            'offices' => $clearance->officeTasks->map(fn ($task): array => [
                'office_code' => $task->office->code,
                'decision_status' => $task->status,
                'approved_at' => $task->approved_at?->toIso8601String(),
                'deficiencies' => $task->deficiencies->map(fn ($deficiency): array => [
                    'status' => $deficiency->status,
                    'description' => $deficiency->description,
                    'resolution_note' => $deficiency->resolution_note,
                    'resolved_at' => $deficiency->resolved_at?->toIso8601String(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function assertMetadata(array $metadata): void
    {
        if (($metadata['version'] ?? null) !== 1
            || ! hash_equals((string) config('secure_exchange.sender'), (string) ($metadata['sender'] ?? ''))
            || ! hash_equals((string) config('secure_exchange.recipient'), (string) ($metadata['recipient'] ?? ''))
            || ! hash_equals((string) config('secure_exchange.purpose'), (string) ($metadata['purpose'] ?? ''))) {
            throw new SecureExchangeException(
                'ENVELOPE_CONTEXT_INVALID',
                'The protected message context is not accepted.',
                422,
            );
        }

        try {
            $issuedAt = CarbonImmutable::parse((string) $metadata['issued_at']);
            $expiresAt = CarbonImmutable::parse((string) $metadata['expires_at']);
        } catch (Throwable) {
            throw new SecureExchangeException(
                'ENVELOPE_TIME_INVALID',
                'The protected message time window is invalid.',
                422,
            );
        }

        if ($expiresAt->lessThanOrEqualTo($issuedAt)) {
            throw new SecureExchangeException(
                'ENVELOPE_TIME_INVALID',
                'The protected message time window is invalid.',
                422,
            );
        }

        if ($issuedAt->greaterThan(CarbonImmutable::now('UTC')->addSeconds(60))) {
            throw new SecureExchangeException(
                'MESSAGE_NOT_YET_VALID',
                'The protected message is not yet valid.',
                422,
            );
        }

        if ($expiresAt->isPast()) {
            throw new SecureExchangeException(
                'EXPIRED_MESSAGE',
                'The protected message has expired.',
                410,
            );
        }
    }

    private function encryptionKey(): string
    {
        $encoded = (string) config('secure_exchange.key_base64');
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32) {
            throw new SecureExchangeException(
                'SECURE_EXCHANGE_NOT_CONFIGURED',
                'The protected exchange key is unavailable.',
                503,
            );
        }

        return $key;
    }

    private function decodeBinary(string $encoded, ?int $expectedLength): string
    {
        $value = base64_decode($encoded, true);
        if ($value === false || ($expectedLength !== null && strlen($value) !== $expectedLength)) {
            throw new SecureExchangeException(
                'ENVELOPE_ENCODING_INVALID',
                'The protected message encoding is invalid.',
                422,
            );
        }

        return $value;
    }

    private function encode(array $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new SecureExchangeException(
                'MESSAGE_ENCODING_FAILED',
                'The protected message could not be encoded.',
                500,
            );
        }
    }

    private function isDuplicate(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
