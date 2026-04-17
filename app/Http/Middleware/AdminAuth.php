<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Sprawdź sesję
        if ($request->session()->has('admin')) {
            return $next($request);
        }

        // Sprawdź x-admin-token (używany przez frontend admina)
        $token = $request->header('x-admin-token');
        if ($token && $this->isValidToken($token)) {
            $request->session()->put('admin', ['role' => 'admin']);
            return $next($request);
        }

        return response()->json(['error' => 'Nieautoryzowany'], 401);
    }

    private function isValidToken(string $token): bool
    {
        // Token format: base64(admin:timestamp)
        $decoded = base64_decode($token, true);
        return $decoded && str_starts_with($decoded, 'admin:');
    }
}
