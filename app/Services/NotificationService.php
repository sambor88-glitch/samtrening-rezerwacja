<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Trainer;

class NotificationService
{
    // ─── Core email sender ───────────────────────────────────────────────────

    public function sendEmail(string $to, string $subject, string $view, array $data = []): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning("Email not sent: invalid address $to");
            return false;
        }

        try {
            Mail::send($view, $data, function ($mail) use ($to, $subject) {
                $mail->to($to)
                     ->subject($subject)
                     ->from(
                         config('mail.from.address', 'noreply@samtrening.pl'),
                         config('mail.from.name', 'SAMtrening')
                     );
            });
            Log::info("Email sent to $to: $subject");
            return true;
        } catch (\Exception $e) {
            Log::error('Email error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Event: rezerwacja potwierdzona ──────────────────────────────────────

    public function bookingConfirmed(Booking $booking): void
    {
        $client = Client::find($booking->client_id);
        if (!$client?->email) return;

        $date = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');

        $this->sendEmail(
            $client->email,
            "Potwierdzenie treningu – {$date} godz. {$booking->time}",
            'emails.booking_confirmed',
            ['client' => $client, 'booking' => $booking, 'date' => $date]
        );
    }

    // ─── Event: trening odwołany ──────────────────────────────────────────────

    public function bookingCancelled(Booking $booking): void
    {
        $client = Client::find($booking->client_id);
        if (!$client?->email) return;

        $date = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');

        $this->sendEmail(
            $client->email,
            "Odwołanie treningu – {$date} godz. {$booking->time}",
            'emails.booking_cancelled',
            ['client' => $client, 'booking' => $booking, 'date' => $date]
        );
    }

    // ─── Event: przypomnienie 24h przed ──────────────────────────────────────

    public function bookingReminder(Booking $booking): void
    {
        $client = Client::find($booking->client_id);
        if (!$client?->email) return;

        $date = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');

        $this->sendEmail(
            $client->email,
            "Przypomnienie: trening jutro o godz. {$booking->time}",
            'emails.booking_reminder',
            ['client' => $client, 'booking' => $booking, 'date' => $date]
        );
    }

    // ─── Powiadomienie do trenera: nowa rezerwacja od klienta ────────────────

    public function trainerNewBooking(Booking $booking): void
    {
        $trainer = Trainer::find($booking->trainer_id);
        if (!$trainer?->email) return;

        $client = Client::find($booking->client_id);
        $date   = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');

        $this->sendEmail(
            $trainer->email,
            "Nowa rezerwacja od {$client?->name} – {$date} godz. {$booking->time}",
            'emails.trainer_new_booking',
            ['trainer' => $trainer, 'client' => $client, 'booking' => $booking, 'date' => $date]
        );
    }

    // ─── Powiadomienie do trenera: klient odwołał trening ────────────────────

    public function trainerBookingCancelled(Booking $booking): void
    {
        $trainer = Trainer::find($booking->trainer_id);
        if (!$trainer?->email) return;

        $client = Client::find($booking->client_id);
        $date   = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');

        $this->sendEmail(
            $trainer->email,
            "Odwołanie treningu przez {$client?->name} – {$date} godz. {$booking->time}",
            'emails.trainer_booking_cancelled',
            ['trainer' => $trainer, 'client' => $client, 'booking' => $booking, 'date' => $date]
        );
    }

    // ─── Event: płatność potwierdzona ────────────────────────────────────────

    public function paymentConfirmed(Client $client, Package $package): void
    {
        if (!$client->email) return;

        $price = number_format($package->price, 2, ',', ' ');

        $this->sendEmail(
            $client->email,
            "Potwierdzenie płatności – pakiet {$package->total_sessions} treningów",
            'emails.payment_confirmed',
            ['client' => $client, 'package' => $package, 'price' => $price]
        );
    }
}
