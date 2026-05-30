<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('services.internal_api.key');
        $providedKey = $request->header('X-Internal-Api-Key');

        if (blank($expectedKey) || blank($providedKey) || !hash_equals((string) $expectedKey, (string) $providedKey)) {
            return response()->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
