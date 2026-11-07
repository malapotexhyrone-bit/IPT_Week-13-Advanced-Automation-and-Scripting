<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\SecureExchangeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveSecureExchangeRequest;
use App\Models\Clearance;
use App\Services\SecureClearanceExchange;
use App\Services\SecureExchangeSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecureExchangeController extends Controller
{
    public function send(Request $request, SecureExchangeSender $sender): JsonResponse
    {
        $validated = $request->validate([
            'clearance_id' => ['required', 'integer', 'exists:clearances,id'],
        ]);
        $clearance = Clearance::query()->findOrFail($validated['clearance_id']);

        try {
            $result = $sender->send(
                $clearance,
                $request->attributes->get('correlation_id'),
            );
        } catch (SecureExchangeException $exception) {
            return $this->error($request, $exception);
        }

        return response()->json([
            'data' => $result,
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ], 202);
    }

    public function receive(
        ReceiveSecureExchangeRequest $request,
        SecureClearanceExchange $exchange,
    ): JsonResponse {
        try {
            $result = $exchange->receive(
                $request->validated(),
                $request->attributes->get('correlation_id'),
            );
        } catch (SecureExchangeException $exception) {
            return $this->error($request, $exception);
        }

        return response()->json([
            'data' => $result,
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ], 202);
    }

    private function error(Request $request, SecureExchangeException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'correlation_id' => $request->attributes->get('correlation_id'),
                'fields' => (object) [],
            ],
        ], $exception->status());
    }
}
