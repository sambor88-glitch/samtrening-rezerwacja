<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainer;
use App\Models\Client;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // GET /api/admin/stats
    public function stats()
    {
        return response()->json([
            'trainers'  => Trainer::count(),
            'clients'   => Client::count(),
            'bookings'  => Booking::count(),
            'completed' => Booking::where('completed', true)->count(),
            'packages'  => Package::count(),
            'revenue'   => Payment::where('status', 'confirmed')->sum('amount'),
        ]);
    }

    // GET /api/admin/trainers
    public function trainers()
    {
        return response()->json(Trainer::all());
    }

    // POST /api/admin/trainers
    public function addTrainer(Request $request)
    {
        $data = $request->validate([
            'id'       => 'required|string|unique:trainers,id',
            'name'     => 'required|string',
            'password' => 'required|string|min:4',
        ]);

        $trainer = Trainer::create([
            'id'            => $data['id'],
            'name'          => $data['name'],
            'name_short'    => $request->input('nameShort', $data['name']),
            'password_hash' => Hash::make($data['password']),
            'role'          => $request->input('role', 'trainer'),
            'color'         => $request->input('color'),
            'gradient'      => $request->input('gradient'),
        ]);

        return response()->json($trainer, 201);
    }

    // PUT /api/admin/trainers/{id}
    public function updateTrainer(Request $request, string $id)
    {
        $trainer = Trainer::findOrFail($id);
        $trainer->update($request->only(['name', 'name_short', 'color', 'gradient', 'role']));

        if ($request->filled('password')) {
            $trainer->update(['password_hash' => Hash::make($request->input('password'))]);
        }

        return response()->json($trainer);
    }

    // DELETE /api/admin/trainers/{id}
    public function deleteTrainer(string $id)
    {
        Trainer::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // GET /api/admin/clients
    public function clients()
    {
        return response()->json(Client::with('trainer:id,name')->get());
    }

    // GET /api/admin/bookings
    public function bookings()
    {
        return response()->json(Booking::with(['trainer:id,name', 'client:id,name'])->get());
    }

    // GET /api/admin/payments
    public function payments()
    {
        return response()->json(Payment::with(['trainer:id,name', 'client:id,name'])->get());
    }
}
