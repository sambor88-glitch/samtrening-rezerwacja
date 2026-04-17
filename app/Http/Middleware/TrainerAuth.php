<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Trainer;

class TrainerAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check session
        if ($request->session()->has('trainer')) {
            return $next($request);
        }

        // Check x-trainer-token header (backward compatibility)
        $token = $request->header('x-trainer-token');
        if ($token) {
            $trainer = $this->resolveToken($token);
            if ($trainer) {
                $request->session()->put('trainer', $trainer);
                return $next($request);
            }
        }

        return response()->json(['error' => 'Nieautoryzowany'], 401);
    }

    private function resolveToken(string $token): ?array
    {
        // Try to decode base64 token (format: id:timestamp)
        $decoded = base64_decode($token, true);
        if ($decoded && str_contains($decoded, ':')) {
            $parts = explode(':', $decoded);
            $trainerId = $parts[0] ?? null;
            if ($trainerId) {
                $trainer = Trainer::find($trainerId);
                if ($trainer) {
                    return [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'nameShort' => $trainer->name_short,
                        'role' => $trainer->role,
                    ];
                }
            }
        }
        return null;
    }
}
