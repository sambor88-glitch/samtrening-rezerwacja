<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Trainer;
use App\Models\Client;
use App\Models\Booking;
use App\Models\Availability;
use App\Models\BlockedSlot;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Message;
use App\Models\Setting;
use App\Models\ClientPrice;
use App\Models\ClientNote;
use App\Models\Measurement;
use App\Models\ChatNotification;
use App\Services\NotificationService;
use Carbon\Carbon;

class TrainerController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    private function trainerId(Request $request): string
    {
        return $request->session()->get('trainer.id');
    }

    // GET /api/trainers
    public function index()
    {
        return response()->json(
            Trainer::all(['id', 'name', 'name_short', 'color', 'gradient'])
        );
    }

    // GET /api/trainer/sync  - bulk data for localStorage
    public function sync(Request $request)
    {
        $tid = $this->trainerId($request);

        $bookings     = Booking::where('trainer_id', $tid)->get();
        $clients      = Client::where('trainer_id', $tid)->get();
        $packages     = Package::where('trainer_id', $tid)->get();
        $payments     = Payment::where('trainer_id', $tid)->get();
        $availability = Availability::where('trainer_id', $tid)->get();
        $blocked      = BlockedSlot::where('trainer_id', $tid)->get();
        $messages     = Message::where('trainer_id', $tid)->get();
        $measurements = Measurement::where('trainer_id', $tid)->get();

        $setting = Setting::where('trainer_id', $tid)->first();
        $settings = $setting ? $setting->data : (object)[];

        $prices = ClientPrice::where('trainer_id', $tid)
            ->get()->keyBy('client_id')->map->price;

        $notes = ClientNote::where('trainer_id', $tid)
            ->get()->keyBy('client_id')->map->note;

        return response()->json(compact(
            'bookings', 'clients', 'packages', 'payments',
            'availability', 'blocked', 'messages', 'measurements',
            'settings', 'prices', 'notes'
        ) + ['trainerId' => $tid]);
    }

    // GET /api/trainer/clients
    public function clients(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(Client::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/clients
    public function addClient(Request $request)
    {
        $tid = $this->trainerId($request);
        $data = $request->validate([
            'name'  => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $id = 'c_' . uniqid();
        $client = Client::create([
            'id'         => $id,
            'trainer_id' => $tid,
            'name'       => $data['name'],
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'password_hash' => $request->input('password')
                ? Hash::make($request->input('password'))
                : null,
            'status' => 'active',
        ]);

        return response()->json($client, 201);
    }

    // PUT /api/trainer/clients/{id}
    public function updateClient(Request $request, string $id)
    {
        $tid    = $this->trainerId($request);
        $client = Client::where('id', $id)->where('trainer_id', $tid)->firstOrFail();

        $client->update($request->only(['name', 'email', 'phone', 'status']));

        if ($request->filled('password')) {
            $client->update(['password_hash' => Hash::make($request->input('password'))]);
        }

        return response()->json($client);
    }

    // DELETE /api/trainer/clients/{id}
    public function deleteClient(Request $request, string $id)
    {
        $tid = $this->trainerId($request);
        Client::where('id', $id)->where('trainer_id', $tid)->delete();
        return response()->json(['success' => true]);
    }

    // GET /api/trainer/bookings
    public function bookings(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(Booking::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/bookings
    public function addBooking(Request $request)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['id'] = $data['id'] ?? ('b_' . uniqid());

        $booking = Booking::create($data);

        // Wyślij email z potwierdzeniem do klienta
        if ($booking->client_id && ($data['status'] ?? '') === 'confirmed') {
            $this->notifications->bookingConfirmed($booking);
        }

        return response()->json($booking, 201);
    }

    // PUT /api/trainer/bookings/{id}
    public function updateBooking(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $booking = Booking::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $booking->update($request->all());
        return response()->json($booking);
    }

    // DELETE /api/trainer/bookings/{id}
    public function deleteBooking(Request $request, string $id)
    {
        $tid = $this->trainerId($request);
        Booking::where('id', $id)->where('trainer_id', $tid)->delete();
        return response()->json(['success' => true]);
    }

    // POST /api/trainer/bookings/{id}/complete
    public function completeBooking(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $booking = Booking::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $booking->update(['completed' => true, 'status' => 'completed']);

        // Increment used_sessions on package if linked
        if ($booking->package_id) {
            Package::where('id', $booking->package_id)
                ->increment('used_sessions');
        }

        return response()->json($booking);
    }

    // POST /api/trainer/bookings/{id}/note
    public function saveBookingNote(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $booking = Booking::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $booking->update(['trainer_note' => $request->input('note', '')]);
        return response()->json($booking);
    }

    // POST /api/trainer/bookings/{id}/cancel
    public function cancelBooking(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $booking = Booking::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $booking->update(['status' => 'cancelled']);

        // Wyślij email o odwołaniu do klienta
        if ($booking->client_id) {
            $this->notifications->bookingCancelled($booking);
        }

        return response()->json($booking);
    }

    // GET /api/trainer/availability
    public function availability(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(Availability::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/availability
    public function saveAvailability(Request $request)
    {
        $tid  = $this->trainerId($request);
        $days = $request->input('days', []);

        // Replace all availability for this trainer
        Availability::where('trainer_id', $tid)->delete();

        foreach ($days as $day) {
            Availability::create([
                'trainer_id'  => $tid,
                'day_of_week' => $day['day'],
                'start_time'  => $day['start'],
                'end_time'    => $day['end'],
                'active'      => $day['active'] ?? true,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // GET /api/trainer/blocked
    public function blocked(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(BlockedSlot::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/blocked
    public function addBlocked(Request $request)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['id'] = $data['id'] ?? ('bl_' . uniqid());

        $slot = BlockedSlot::create($data);
        return response()->json($slot, 201);
    }

    // DELETE /api/trainer/blocked/{id}
    public function deleteBlocked(Request $request, string $id)
    {
        $tid = $this->trainerId($request);
        BlockedSlot::where('id', $id)->where('trainer_id', $tid)->delete();
        return response()->json(['success' => true]);
    }

    // GET /api/trainer/packages
    public function packages(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(Package::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/packages
    public function addPackage(Request $request)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['id'] = $data['id'] ?? ('pkg_' . uniqid());

        $package = Package::create($data);
        return response()->json($package, 201);
    }

    // PUT /api/trainer/packages/{id}
    public function updatePackage(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $package = Package::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $package->update($request->all());
        return response()->json($package);
    }

    // GET /api/trainer/payments
    public function payments(Request $request)
    {
        $tid = $this->trainerId($request);
        return response()->json(Payment::where('trainer_id', $tid)->get());
    }

    // POST /api/trainer/payments
    public function addPayment(Request $request)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['id'] = $data['id'] ?? ('pay_' . uniqid());

        $payment = Payment::create($data);

        // Mark package as paid if requested
        if (!empty($data['package_id']) && !empty($data['markPackagePaid'])) {
            Package::where('id', $data['package_id'])->update(['paid' => true]);
        }

        return response()->json($payment, 201);
    }

    // PUT /api/trainer/payments/{id}/confirm
    public function confirmPayment(Request $request, string $id)
    {
        $tid     = $this->trainerId($request);
        $payment = Payment::where('id', $id)->where('trainer_id', $tid)->firstOrFail();
        $payment->update(['status' => 'confirmed']);

        // Wyślij email z potwierdzeniem płatności do klienta
        if ($payment->client_id && $payment->package_id) {
            $client  = Client::find($payment->client_id);
            $package = Package::find($payment->package_id);
            if ($client && $package) {
                $this->notifications->paymentConfirmed($client, $package);
            }
        }

        return response()->json($payment);
    }

    // GET /api/trainer/messages/{clientId}
    public function getMessages(Request $request, string $clientId)
    {
        $tid = $this->trainerId($request);
        $messages = Message::where('trainer_id', $tid)
            ->where('client_id', $clientId)
            ->orderBy('created_at')
            ->get();

        // Mark client messages as read
        Message::where('trainer_id', $tid)
            ->where('client_id', $clientId)
            ->where('sender', 'client')
            ->update(['read' => true]);

        ChatNotification::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $clientId],
            ['unread_trainer' => 0]
        );

        return response()->json($messages);
    }

    // POST /api/trainer/messages/{clientId}
    public function sendMessage(Request $request, string $clientId)
    {
        $tid  = $this->trainerId($request);
        $text = $request->input('text');

        if (!$text) {
            return response()->json(['error' => 'Brak treści'], 400);
        }

        $message = Message::create([
            'id'         => 'msg_' . uniqid(),
            'trainer_id' => $tid,
            'client_id'  => $clientId,
            'sender'     => 'trainer',
            'text'       => $text,
            'read'       => false,
        ]);

        ChatNotification::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $clientId],
            ['unread_client' => \DB::raw('unread_client + 1')]
        );

        return response()->json($message, 201);
    }

    // GET /api/trainer/messages/unread
    public function unreadCounts(Request $request)
    {
        $tid = $this->trainerId($request);
        $counts = ChatNotification::where('trainer_id', $tid)
            ->get(['client_id', 'unread_trainer'])
            ->keyBy('client_id')
            ->map->unread_trainer;

        return response()->json($counts);
    }

    // GET /api/trainer/settings
    public function getSettings(Request $request)
    {
        $tid     = $this->trainerId($request);
        $setting = Setting::where('trainer_id', $tid)->first();
        return response()->json($setting ? $setting->data : (object)[]);
    }

    // POST /api/trainer/settings
    public function saveSettings(Request $request)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();

        Setting::updateOrCreate(
            ['trainer_id' => $tid],
            ['data' => $data]
        );

        return response()->json(['success' => true]);
    }

    // GET /api/trainer/clients/{id}/price
    public function getClientPrice(Request $request, string $id)
    {
        $tid   = $this->trainerId($request);
        $price = ClientPrice::where('trainer_id', $tid)->where('client_id', $id)->first();
        return response()->json(['price' => $price ? $price->price : null]);
    }

    // POST /api/trainer/clients/{id}/price
    public function saveClientPrice(Request $request, string $id)
    {
        $tid   = $this->trainerId($request);
        $price = $request->input('price');

        ClientPrice::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $id],
            ['price' => $price]
        );

        return response()->json(['success' => true]);
    }

    // GET /api/trainer/clients/{id}/note
    public function getClientNote(Request $request, string $id)
    {
        $tid  = $this->trainerId($request);
        $note = ClientNote::where('trainer_id', $tid)->where('client_id', $id)->first();
        return response()->json(['note' => $note ? $note->note : '']);
    }

    // POST /api/trainer/clients/{id}/note
    public function saveClientNote(Request $request, string $id)
    {
        $tid  = $this->trainerId($request);
        $note = $request->input('note', '');

        ClientNote::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $id],
            ['note' => $note]
        );

        return response()->json(['success' => true]);
    }

    // GET /api/trainer/clients/{id}/measurements
    public function getMeasurements(Request $request, string $id)
    {
        $tid = $this->trainerId($request);
        return response()->json(
            Measurement::where('trainer_id', $tid)->where('client_id', $id)->orderBy('date')->get()
        );
    }

    // POST /api/trainer/clients/{id}/measurements
    public function addMeasurement(Request $request, string $id)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['client_id']  = $id;
        $data['id'] = $data['id'] ?? ('m_' . uniqid());

        $measurement = Measurement::create($data);
        return response()->json($measurement, 201);
    }

    // GET /api/trainer/stats
    public function stats(Request $request)
    {
        $tid = $this->trainerId($request);

        // Ostatnie 12 miesięcy
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        $revenueByMonth = [];
        $trainingsByMonth = [];

        foreach ($months as $month) {
            $start = $month . '-01';
            $end   = Carbon::parse($start)->endOfMonth()->toDateString();

            $revenueByMonth[$month] = Payment::where('trainer_id', $tid)
                ->where('status', 'confirmed')
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->sum('amount');

            $trainingsByMonth[$month] = Booking::where('trainer_id', $tid)
                ->where('completed', true)
                ->whereBetween('date', [$start, $end])
                ->count();
        }

        // Top klienci wg liczby treningów
        $topClients = Booking::where('trainer_id', $tid)
            ->where('completed', true)
            ->whereNotNull('client_id')
            ->selectRaw('client_id, COUNT(*) as total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('client:id,name')
            ->get()
            ->map(fn($b) => ['name' => $b->client->name ?? '?', 'total' => $b->total]);

        // Podsumowanie ogólne
        $totalRevenue    = Payment::where('trainer_id', $tid)->where('status', 'confirmed')->sum('amount');
        $totalTrainings  = Booking::where('trainer_id', $tid)->where('completed', true)->count();
        $activeClients   = Client::where('trainer_id', $tid)->where('status', 'active')->count();
        $pendingPayments = Payment::where('trainer_id', $tid)->where('status', 'pending')->sum('amount');

        return response()->json([
            'months'           => $months,
            'revenueByMonth'   => array_values($revenueByMonth),
            'trainingsByMonth' => array_values($trainingsByMonth),
            'topClients'       => $topClients,
            'totalRevenue'     => $totalRevenue,
            'totalTrainings'   => $totalTrainings,
            'activeClients'    => $activeClients,
            'pendingPayments'  => $pendingPayments,
        ]);
    }

    // GET /api/trainer/export/report?month=YYYY-MM
    public function exportReport(Request $request)
    {
        $tid   = $this->trainerId($request);
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $start = $month . '-01';
        $end   = Carbon::parse($start)->endOfMonth()->toDateString();

        $trainer  = Trainer::find($tid);
        $bookings = Booking::where('trainer_id', $tid)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')->orderBy('time')
            ->with('client:id,name')
            ->get();

        $payments = Payment::where('trainer_id', $tid)
            ->where('status', 'confirmed')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->with('client:id,name')
            ->get();

        $totalRevenue   = $payments->sum('amount');
        $completedCount = $bookings->where('completed', true)->count();
        $cancelledCount = $bookings->where('status', 'cancelled')->count();

        $monthLabel = Carbon::parse($start)->locale('pl')->isoFormat('MMMM YYYY');

        $html = view('reports.trainer_monthly', compact(
            'trainer', 'bookings', 'payments',
            'totalRevenue', 'completedCount', 'cancelledCount', 'monthLabel', 'month'
        ))->render();

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    // GET /api/trainer/plans/{clientId}
    public function getPlans(Request $request, string $clientId)
    {
        $tid = $this->trainerId($request);
        return response()->json(
            \App\Models\TrainingPlan::where('trainer_id', $tid)->where('client_id', $clientId)->get()
        );
    }

    // POST /api/trainer/plans/{clientId}
    public function savePlan(Request $request, string $clientId)
    {
        $tid  = $this->trainerId($request);
        $data = $request->all();
        $data['trainer_id'] = $tid;
        $data['client_id']  = $clientId;
        $data['id'] = $data['id'] ?? ('plan_' . uniqid());

        $plan = \App\Models\TrainingPlan::updateOrCreate(
            ['id' => $data['id']],
            $data
        );

        return response()->json($plan, 201);
    }
}
