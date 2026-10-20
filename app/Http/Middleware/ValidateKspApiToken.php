<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateKspApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('task.api_token');

        if (! $expected) {
            abort(response()->json([
                'message' => 'KSP API token belum dikonfigurasi pada server.',
            ], 500));
        }

        $provided = $request->bearerToken();

        if (! $provided || ! hash_equals($expected, $provided)) {
            abort(response()->json([
                'message' => 'Unauthorized: token API tidak valid.',
            ], 401));
        }

        return $next($request);
    }
}
