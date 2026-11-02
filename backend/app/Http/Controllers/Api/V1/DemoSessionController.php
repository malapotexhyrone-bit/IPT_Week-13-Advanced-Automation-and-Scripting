<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemoSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([
                User::ROLE_STUDENT,
                User::ROLE_OFFICE,
                User::ROLE_ADMIN,
            ])],
            'office_code' => ['nullable', 'string', 'max:40'],
        ]);

        $query = User::query()->where('role', $validated['role']);

        if ($validated['role'] === User::ROLE_OFFICE) {
            $office = Office::query()
                ->where('code', $validated['office_code'] ?? '')
                ->where('is_active', true)
                ->first();

            if ($office === null) {
                return $this->error($request, 'DEMO_OFFICE_NOT_FOUND', 'Select an available clearing office.', 422);
            }

            $query->where('office_id', $office->id);
        }

        $user = $query->with('office')->first();

        if ($user === null) {
            return $this->error($request, 'DEMO_USER_UNAVAILABLE', 'The selected controlled demo user is unavailable.', 503);
        }

        $user->tokens()->where('name', 'flutter-demo')->delete();
        $token = $user->createToken('flutter-demo', ['*'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->userData($user),
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'office' => $user->office === null ? null : [
                'code' => $user->office->code,
                'name' => $user->office->name,
            ],
        ];
    }

    private function error(Request $request, string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'correlation_id' => $request->attributes->get('correlation_id'),
                'fields' => (object) [],
            ],
        ], $status);
    }
}
