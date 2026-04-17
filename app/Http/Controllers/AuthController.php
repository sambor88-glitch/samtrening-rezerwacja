<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Trainer;
use App\Models\Client;

class AuthController extends Controller
{
    // POST /api/auth/trainer/login  OR  /api/trainer/login
    public function trainerLogin(Request $request)
    {
        $id = $request->input('id') ?? $request->input('trainerId');
        $password = $request->input('password');

        if (!$id || !$password) {
            return response()->json(['error' => 'Brak danych'], 400);
        }

        $trainer = Trainer::find($id);
        if (!$trainer) {
            return response()->json(['error' => 'Nieprawidłowe dane'], 401);
        }

        if (!Hash::check($password, $trainer->password_hash)) {
            return response()->json(['error' => 'Nieprawidłowe hasło'], 401);
        }

        $trainerData = [
            'id'        => $trainer->id,
            'name'      => $trainer->name,
            'nameShort' => $trainer->name_short,
            'role'      => $trainer->role,
        ];

        $request->session()->put('trainer', $trainerData);

        // Generate token for backward compatibility
        $token = base64_encode($trainer->id . ':' . time());

        return response()->json([
            'success' => true,
            'token'   => $token,
            'trainer' => $trainerData,
        ]);
    }

    // POST /api/auth/client/login
    public function clientLogin(Request $request)
    {
        $email    = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            return response()->json(['error' => 'Brak danych'], 400);
        }

        $client = Client::where('email', $email)->first();
        if (!$client) {
            return response()->json(['error' => 'Nieprawidłowe dane'], 401);
        }

        if (!Hash::check($password, $client->password_hash)) {
            return response()->json(['error' => 'Nieprawidłowe hasło'], 401);
        }

        $clientData = [
            'id'        => $client->id,
            'name'      => $client->name,
            'email'     => $client->email,
            'trainerId' => $client->trainer_id,
        ];

        $request->session()->put('client', $clientData);

        return response()->json(['success' => true, 'client' => $clientData]);
    }

    // POST /api/admin/login
    public function adminLogin(Request $request)
    {
        $password    = $request->input('password');
        $adminPass   = env('ADMIN_PASSWORD', 'samtrening2024');

        if ($password !== $adminPass) {
            return response()->json(['error' => 'Nieprawidłowe hasło'], 401);
        }

        $request->session()->put('admin', ['role' => 'admin']);
        $token = base64_encode('admin:' . time());

        return response()->json(['success' => true, 'token' => $token]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->session()->flush();
        return response()->json(['success' => true]);
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        if ($request->session()->has('trainer')) {
            return response()->json(['role' => 'trainer', 'user' => $request->session()->get('trainer')]);
        }
        if ($request->session()->has('client')) {
            return response()->json(['role' => 'client', 'user' => $request->session()->get('client')]);
        }
        if ($request->session()->has('admin')) {
            return response()->json(['role' => 'admin', 'user' => ['role' => 'admin']]);
        }
        return response()->json(['error' => 'Nie zalogowano'], 401);
    }
}
