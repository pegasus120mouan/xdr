<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('xdr.integration_token');
        if (! is_string($expected) || $expected === '') {
            return response()->json([
                'error' => 'integration_not_configured',
                'message' => 'Définissez XDR_INTEGRATION_TOKEN dans .env pour activer l’API d’intégration.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $token = $request->bearerToken();
        if (! is_string($token) || ! hash_equals($expected, $token)) {
            return response()->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
