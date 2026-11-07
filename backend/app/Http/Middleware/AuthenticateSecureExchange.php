<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSecureExchange
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = (string) $request->header('X-Exchange-Client', '');
        $timestamp = (string) $request->header('X-Exchange-Timestamp', '');
        $signature = strtolower((string) $request->header('X-Exchange-Signature', ''));
        $expectedClient = (string) config('secure_exchange.expected_client_id');
        $secret = (string) config('secure_exchange.auth_secret');

        if ($secret === '' || strlen($secret) < 32) {
            return $this->reject($request, 'SECURE_EXCHANGE_NOT_CONFIGURED', 503);
        }

        if ($clientId === '' || ! hash_equals($expectedClient, $clientId)) {
            return $this->reject($request, 'SERVICE_CLIENT_UNAUTHORIZED', 401);
        }

        if (! ctype_digit($timestamp)) {
            return $this->reject($request, 'SERVICE_TIMESTAMP_INVALID', 401);
        }

        $allowedSkew = max(1, (int) config('secure_exchange.request_skew_seconds', 60));
        if (abs(now()->timestamp - (int) $timestamp) > $allowedSkew) {
            return $this->reject($request, 'SERVICE_REQUEST_EXPIRED', 401);
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp."\n".$request->getContent(),
            $secret,
        );

        if ($signature === '' || ! hash_equals($expectedSignature, $signature)) {
            return $this->reject($request, 'SERVICE_SIGNATURE_INVALID', 401);
        }

        $request->attributes->set('exchange_client_id', $clientId);

        return $next($request);
    }

    private function reject(Request $request, string $code, int $status): JsonResponse
    {
        Log::warning('secure_exchange.authentication_rejected', [
            'event' => 'secure_exchange.authentication_rejected',
            'error_code' => $code,
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]);

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => 'The secure exchange request could not be authenticated.',
                'correlation_id' => $request->attributes->get('correlation_id'),
                'fields' => (object) [],
            ],
        ], $status);
    }
}
