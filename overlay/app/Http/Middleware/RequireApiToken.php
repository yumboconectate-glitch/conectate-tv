<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) env('CONNECTATE_API_TOKEN', ''));

        if ($expected === '') {
            return response()->json([
                'ok' => false,
                'error' => 'api_token_not_configured',
            ], 503);
        }

        $provided = trim((string) $request->bearerToken());

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401, ['Cache-Control' => 'no-store']);
        }

        return $next($request);
    }
}
