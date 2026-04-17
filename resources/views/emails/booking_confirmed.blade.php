@extends('emails.layout')

@section('title', 'Potwierdzenie treningu')

@section('content')
<h2>Cześć, {{ $client->name }}! 👋</h2>
<p>Twój trening został <strong>potwierdzony</strong>. Do zobaczenia!</p>

<div class="info-box">
  <p>📅 <strong>Data:</strong> {{ $date }}</p>
  <p>🕐 <strong>Godzina:</strong> {{ $booking->time }}</p>
  <p>⏱ <strong>Czas trwania:</strong> {{ $booking->duration ?? 60 }} min</p>
  @if($booking->note)
  <p>📝 <strong>Notatka:</strong> {{ $booking->note }}</p>
  @endif
</div>

<p>Pamiętaj o:</p>
<ul>
  <li>Wygodnym stroju sportowym</li>
  <li>Butelce wody</li>
  <li>Przybyciu 5 minut wcześniej</li>
</ul>

<p>W razie pytań skontaktuj się z trenerem. Do zobaczenia! 💪</p>
@endsection
