<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('office');

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'office' => $user->office === null ? null : [
                    'code' => $user->office->code,
                    'name' => $user->office->name,
                ],
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }
}
