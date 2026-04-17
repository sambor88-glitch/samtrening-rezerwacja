<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClientAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('client')) {
            return $next($request);
        }

        return response()->json(['error' => 'Nieautoryzowany'], 401);
    }
}
