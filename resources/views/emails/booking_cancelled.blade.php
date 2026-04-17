@extends('emails.layout')

@section('title', 'Odwołanie treningu')

@section('content')
<h2>Cześć, {{ $client->name }}!</h2>
<p>Niestety Twój trening zaplanowany na <strong>{{ $date }}</strong> o godz. <strong>{{ $booking->time }}</strong> został <strong style="color:#e53935;">odwołany</strong>.</p>

<div class="info-box">
  <p>📅 <strong>Data:</strong> {{ $date }}</p>
  <p>🕐 <strong>Godzina:</strong> {{ $booking->time }}</p>
</div>

<p>Skontaktuj się ze swoim trenerem, aby umówić nowy termin.</p>
<p>Przepraszamy za niedogodności!</p>
@endsection
