@extends('emails.layout')

@section('title', 'Przypomnienie o treningu')

@section('content')
<h2>Cześć, {{ $client->name }}! 🔔</h2>
<p>Przypominamy, że <strong>jutro masz trening!</strong></p>

<div class="info-box">
  <p>📅 <strong>Data:</strong> {{ $date }}</p>
  <p>🕐 <strong>Godzina:</strong> {{ $booking->time }}</p>
  <p>⏱ <strong>Czas trwania:</strong> {{ $booking->duration ?? 60 }} min</p>
</div>

<p>Przygotuj się dobrze:</p>
<ul>
  <li>Zadbaj o dobry sen tej nocy</li>
  <li>Zjedz lekki posiłek ok. 2h przed treningiem</li>
  <li>Przygotuj strój i butelkę wody</li>
</ul>

<p>Do zobaczenia jutro! 💪</p>
@endsection
