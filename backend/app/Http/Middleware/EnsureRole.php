<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            return new JsonResponse([
                'error' => [
                    'code' => 'ROLE_FORBIDDEN',
                    'message' => 'Your account is not permitted to perform this action.',
                    'correlation_id' => $request->attributes->get('correlation_id'),
                    'fields' => (object) [],
                ],
            ], 403);
        }

        return $next($request);
    }
}
