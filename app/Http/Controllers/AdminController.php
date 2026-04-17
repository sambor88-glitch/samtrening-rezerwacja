<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
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
        // Ostatnie 6 miesięcy
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        $trainers = Trainer::all(['id', 'name', 'color']);

        // Przychody per trener per miesiąc
        $revenuePerTrainer = [];
        foreach ($trainers as $trainer) {
            $monthly = [];
            foreach ($months as $month) {
                $start = $month . '-01';
                $end   = Carbon::parse($start)->endOfMonth()->toDateString();
                $monthly[] = Payment::where('trainer_id', $trainer->id)
                    ->where('status', 'confirmed')
                    ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->sum('amount');
            }
            $revenuePerTrainer[] = [
                'name'   => $trainer->name,
                'color'  => $trainer->color ?? '#e91e8c',
                'data'   => $monthly,
                'total'  => array_sum($monthly),
            ];
        }

        // Treningi per trener per miesiąc
        $trainingsPerTrainer = [];
        foreach ($trainers as $trainer) {
            $monthly = [];
            foreach ($months as $month) {
                $start = $month . '-01';
                $end   = Carbon::parse($start)->endOfMonth()->toDateString();
                $monthly[] = Booking::where('trainer_id', $trainer->id)
                    ->where('completed', true)
                    ->whereBetween('date', [$start, $end])
                    ->count();
            }
            $trainingsPerTrainer[] = [
                'name'  => $trainer->name,
                'color' => $trainer->color ?? '#e91e8c',
                'data'  => $monthly,
                'total' => array_sum($monthly),
            ];
        }

        return response()->json([
            'overview' => [
                'trainers'  => Trainer::count(),
                'clients'   => Client::count(),
                'bookings'  => Booking::count(),
                'completed' => Booking::where('completed', true)->count(),
                'packages'  => Package::count(),
                'revenue'   => Payment::where('status', 'confirmed')->sum('amount'),
            ],
            'months'             => $months,
            'revenuePerTrainer'  => $revenuePerTrainer,
            'trainingsPerTrainer'=> $trainingsPerTrainer,
        ]);
    }

    // GET /api/admin/export?type=bookings|payments|clients
    public function exportCsv(Request $request)
    {
        $type = $request->query('type', 'bookings');

        if ($type === 'payments') {
            $rows = Payment::with(['trainer:id,name', 'client:id,name'])->get();
            $headers = ['ID', 'Trener', 'Klient', 'Kwota', 'Status', 'Data'];
            $data = $rows->map(fn($r) => [
                $r->id,
                $r->trainer->name ?? '-',
                $r->client->name ?? '-',
                $r->amount,
                $r->status,
                $r->created_at->format('Y-m-d'),
            ]);
        } elseif ($type === 'clients') {
            $rows = Client::with('trainer:id,name')->get();
            $headers = ['ID', 'Imię', 'Trener', 'Email', 'Telefon', 'Status'];
            $data = $rows->map(fn($r) => [
                $r->id,
                $r->name,
                $r->trainer->name ?? '-',
                $r->email ?? '-',
                $r->phone ?? '-',
                $r->status,
            ]);
        } else {
            // bookings (default)
            $rows = Booking::with(['trainer:id,name', 'client:id,name'])->get();
            $headers = ['ID', 'Trener', 'Klient', 'Data', 'Godzina', 'Status', 'Ukończony'];
            $data = $rows->map(fn($r) => [
                $r->id,
                $r->trainer->name ?? '-',
                $r->client->name ?? '-',
                $r->date,
                $r->time ?? '-',
                $r->status,
                $r->completed ? 'Tak' : 'Nie',
            ]);
        }

        $csv = implode(';', $headers) . "\n";
        foreach ($data as $row) {
            $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        $filename = "samtrening_{$type}_" . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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
