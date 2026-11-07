<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$TxdPgffTNE9dePyseeJareI0m06xwApO4J6A8RBk60qDlupU3dQxi';

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = User::query()->with('office')->where('email', $email)->first();
        $passwordMatches = Hash::check(
            $validated['password'],
            $user?->password ?? self::DUMMY_PASSWORD_HASH,
        );

        if ($user === null || ! $passwordMatches) {
            return $this->error($request, 'INVALID_CREDENTIALS', 'The email or password is incorrect.', 401);
        }

        $user->tokens()->where('name', 'flutter-web')->delete();
        $token = $user->createToken(
            'flutter-web',
            ['workflow'],
            now()->addHours(2),
        )->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->userData($user),
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
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
