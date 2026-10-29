<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestedId = strtoupper((string) $request->header('X-Correlation-ID'));
        $correlationId = Str::isUlid($requestedId)
            ? $requestedId
            : (string) Str::ulid();

        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
