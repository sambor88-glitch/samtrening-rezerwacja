<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Message;
use App\Models\Client;
use App\Models\ChatNotification;

class ClientController extends Controller
{
    private function clientSession(Request $request): array
    {
        return $request->session()->get('client');
    }

    // GET /api/client/sync
    public function sync(Request $request)
    {
        $session  = $this->clientSession($request);
        $cid      = $session['id'];
        $tid      = $session['trainerId'];

        $bookings = Booking::where('client_id', $cid)->get();
        $packages = Package::where('client_id', $cid)->get();
        $payments = Payment::where('client_id', $cid)->get();
        $messages = Message::where('trainer_id', $tid)->where('client_id', $cid)->orderBy('created_at')->get();
        $client   = Client::find($cid);

        return response()->json(compact('bookings', 'packages', 'payments', 'messages', 'client'));
    }

    // POST /api/client/bookings
    public function addBooking(Request $request)
    {
        $session = $this->clientSession($request);
        $data    = $request->all();

        $data['id']         = $data['id'] ?? ('b_' . uniqid());
        $data['client_id']  = $session['id'];
        $data['trainer_id'] = $session['trainerId'];
        $data['status']     = 'pending';

        $booking = Booking::create($data);
        return response()->json($booking, 201);
    }

    // PUT /api/client/bookings/{id}/cancel
    public function cancelBooking(Request $request, string $id)
    {
        $session = $this->clientSession($request);
        $booking = Booking::where('id', $id)->where('client_id', $session['id'])->firstOrFail();
        $booking->update(['status' => 'cancelled']);
        return response()->json($booking);
    }

    // GET /api/client/messages
    public function getMessages(Request $request)
    {
        $session  = $this->clientSession($request);
        $cid      = $session['id'];
        $tid      = $session['trainerId'];

        $messages = Message::where('trainer_id', $tid)
            ->where('client_id', $cid)
            ->orderBy('created_at')
            ->get();

        // Mark trainer messages as read
        Message::where('trainer_id', $tid)
            ->where('client_id', $cid)
            ->where('sender', 'trainer')
            ->update(['read' => true]);

        ChatNotification::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $cid],
            ['unread_client' => 0]
        );

        return response()->json($messages);
    }

    // POST /api/client/messages
    public function sendMessage(Request $request)
    {
        $session = $this->clientSession($request);
        $cid     = $session['id'];
        $tid     = $session['trainerId'];
        $text    = $request->input('text');

        if (!$text) {
            return response()->json(['error' => 'Brak treści'], 400);
        }

        $message = Message::create([
            'id'         => 'msg_' . uniqid(),
            'trainer_id' => $tid,
            'client_id'  => $cid,
            'sender'     => 'client',
            'text'       => $text,
            'read'       => false,
        ]);

        ChatNotification::updateOrCreate(
            ['trainer_id' => $tid, 'client_id' => $cid],
            ['unread_trainer' => \DB::raw('unread_trainer + 1')]
        );

        return response()->json($message, 201);
    }

    // POST /api/client/packages/buy
    public function buyPackage(Request $request)
    {
        $session = $this->clientSession($request);
        $data    = $request->all();

        $data['id']         = $data['id'] ?? ('pkg_' . uniqid());
        $data['client_id']  = $session['id'];
        $data['trainer_id'] = $session['trainerId'];
        $data['status']     = 'pending';
        $data['paid']       = false;

        $package = Package::create($data);
        return response()->json($package, 201);
    }
}
