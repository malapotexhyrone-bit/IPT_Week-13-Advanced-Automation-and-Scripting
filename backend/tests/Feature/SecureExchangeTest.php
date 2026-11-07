<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Clearance;
use App\Models\User;
use App\Services\ClearanceInitiationService;
use App\Services\SecureClearanceExchange;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecureExchangeTest extends TestCase
{
    use RefreshDatabase;

    private string $serviceSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceSecret = str_repeat('s', 48);
        config([
            'secure_exchange.key_base64' => base64_encode(str_repeat('k', 32)),
            'secure_exchange.auth_secret' => $this->serviceSecret,
            'secure_exchange.client_id' => 'student-clearance-sender',
            'secure_exchange.expected_client_id' => 'student-clearance-sender',
            'secure_exchange.sender' => 'student-clearance-system',
            'secure_exchange.recipient' => 'registrar-clearance-receiver',
            'secure_exchange.receiver_url' => 'https://localhost:8443/api/v1/secure-exchange/receive',
            'secure_exchange.ca_certificate' => __FILE__,
            'secure_exchange.request_skew_seconds' => 60,
            'secure_exchange.message_ttl_seconds' => 300,
        ]);
    }

    public function test_authenticated_encrypted_message_is_accepted_without_storing_plaintext(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());

        $this->postEnvelope($envelope)
            ->assertAccepted()
            ->assertJsonPath('data.clearance_reference', 'CLR-000001')
            ->assertJsonPath('data.office_count', 4);

        $this->assertDatabaseHas('secure_exchange_receipts', [
            'message_id' => $envelope['message_id'],
            'clearance_reference' => 'CLR-000001',
        ]);
        $this->assertDatabaseMissing('secure_exchange_receipts', [
            'envelope_hash' => $envelope['ciphertext'],
        ]);
    }

    public function test_altered_ciphertext_is_rejected_even_with_valid_service_signature(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());
        $ciphertext = base64_decode($envelope['ciphertext'], true);
        $ciphertext[0] = chr(ord($ciphertext[0]) ^ 1);
        $envelope['ciphertext'] = base64_encode($ciphertext);

        $this->postEnvelope($envelope)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ALTERED_OR_WRONG_KEY');

        $this->assertDatabaseCount('secure_exchange_receipts', 0);
    }

    public function test_expired_encrypted_message_is_rejected(): void
    {
        CarbonImmutable::setTestNow('2026-11-03T01:00:00Z');
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());
        CarbonImmutable::setTestNow('2026-11-03T01:10:00Z');

        $this->postEnvelope($envelope)
            ->assertStatus(410)
            ->assertJsonPath('error.code', 'EXPIRED_MESSAGE');

        CarbonImmutable::setTestNow();
    }

    public function test_replayed_encrypted_message_is_rejected(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());

        $this->postEnvelope($envelope)->assertAccepted();
        $this->postEnvelope($envelope)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'REPLAYED_MESSAGE');
        $this->assertDatabaseCount('secure_exchange_receipts', 1);
    }

    public function test_invalid_service_signature_is_rejected_before_decryption(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());

        $this->postEnvelope($envelope, str_repeat('x', 48))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'SERVICE_SIGNATURE_INVALID');
        $this->assertDatabaseCount('secure_exchange_receipts', 0);
    }

    public function test_expired_service_request_timestamp_is_rejected(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());

        $this->postEnvelope($envelope, $this->serviceSecret, now()->subMinutes(2)->timestamp)
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'SERVICE_REQUEST_EXPIRED');
    }

    public function test_wrong_encryption_key_is_rejected(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());
        config(['secure_exchange.key_base64' => base64_encode(str_repeat('z', 32))]);

        $this->postEnvelope($envelope)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ALTERED_OR_WRONG_KEY');
    }

    public function test_unavailable_encryption_key_is_rejected_without_a_receipt(): void
    {
        $envelope = app(SecureClearanceExchange::class)
            ->createEnvelope($this->clearance());
        config(['secure_exchange.key_base64' => null]);

        $this->postEnvelope($envelope)
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'SECURE_EXCHANGE_NOT_CONFIGURED');

        $this->assertDatabaseCount('secure_exchange_receipts', 0);
    }

    public function test_malformed_envelope_is_rejected_before_processing(): void
    {
        $this->postEnvelope(['version' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'message_id',
                'issued_at',
                'expires_at',
                'sender',
                'recipient',
                'purpose',
                'nonce',
                'ciphertext',
                'tag',
            ]);

        $this->assertDatabaseCount('secure_exchange_receipts', 0);
    }

    public function test_sender_refuses_a_missing_certificate_trust_file(): void
    {
        $clearance = $this->clearance();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        Sanctum::actingAs($admin);
        config(['secure_exchange.ca_certificate' => storage_path('https/missing-ca.crt')]);

        $this->postJson('/api/v1/secure-exchange/send', [
            'clearance_id' => $clearance->id,
        ])->assertStatus(503)
            ->assertJsonPath('error.code', 'SECURE_EXCHANGE_NOT_CONFIGURED');
    }

    public function test_send_route_requires_an_authenticated_administrator(): void
    {
        $clearance = $this->clearance();
        $this->postJson('/api/v1/secure-exchange/send', [
            'clearance_id' => $clearance->id,
        ])->assertUnauthorized();

        $student = User::query()->where('role', User::ROLE_STUDENT)->firstOrFail();
        Sanctum::actingAs($student);
        $this->postJson('/api/v1/secure-exchange/send', [
            'clearance_id' => $clearance->id,
        ])->assertForbidden()->assertJsonPath('error.code', 'ROLE_FORBIDDEN');
    }

    public function test_administrator_sender_uses_https_ca_validation_and_service_authentication(): void
    {
        $clearance = $this->clearance();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        Sanctum::actingAs($admin);
        Http::fake([
            'https://localhost:8443/*' => Http::response([
                'data' => [
                    'message_id' => (string) Str::uuid(),
                    'clearance_reference' => 'CLR-000001',
                    'overall_status' => $clearance->status,
                    'office_count' => 4,
                ],
            ], 202),
        ]);

        $this->postJson('/api/v1/secure-exchange/send', [
            'clearance_id' => $clearance->id,
        ])->assertAccepted()->assertJsonPath('data.clearance_reference', 'CLR-000001');

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://localhost:8443/')
                && $request->hasHeader('X-Exchange-Client', 'student-clearance-sender')
                && $request->hasHeader('X-Exchange-Timestamp')
                && $request->hasHeader('X-Exchange-Signature')
                && $request['ciphertext'] !== null
                && $request['tag'] !== null;
        });
    }

    private function clearance(): Clearance
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::query()->where('role', User::ROLE_STUDENT)->firstOrFail();
        $term = AcademicTerm::query()->where('is_active', true)->firstOrFail();

        return app(ClearanceInitiationService::class)->initiate(
            $student,
            $term->code,
            (string) Str::ulid(),
            null,
        );
    }

    private function postEnvelope(
        array $envelope,
        ?string $secret = null,
        ?int $timestamp = null,
    ) {
        $timestamp ??= now()->timestamp;
        $body = json_encode(
            $envelope,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        $signature = hash_hmac(
            'sha256',
            $timestamp."\n".$body,
            $secret ?? $this->serviceSecret,
        );

        return $this->call(
            'POST',
            '/api/v1/secure-exchange/receive',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_EXCHANGE_CLIENT' => 'student-clearance-sender',
                'HTTP_X_EXCHANGE_TIMESTAMP' => (string) $timestamp,
                'HTTP_X_EXCHANGE_SIGNATURE' => $signature,
            ],
            $body,
        );
    }
}
