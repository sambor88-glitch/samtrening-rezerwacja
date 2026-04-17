<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendBookingReminders extends Command
{
    protected $signature   = 'bookings:reminders';
    protected $description = 'Wysyła przypomnienia email 24h przed treningiem';

    public function handle(NotificationService $notifications): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $bookings = Booking::whereDate('date', $tomorrow)
            ->where('status', 'confirmed')
            ->where('completed', false)
            ->whereNotNull('client_id')
            ->get();

        $this->info("Znaleziono {$bookings->count()} treningów jutro.");

        foreach ($bookings as $booking) {
            $notifications->bookingReminder($booking);
            $this->line("→ Przypomnienie wysłane dla booking {$booking->id}");
        }

        $this->info('Gotowe!');
    }
}
