<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('admin')) {
            return $next($request);
        }

        return response()->json(['error' => 'Nieautoryzowany'], 401);
    }
}
