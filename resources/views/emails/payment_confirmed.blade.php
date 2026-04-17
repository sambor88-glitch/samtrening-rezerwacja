@extends('emails.layout')

@section('title', 'Potwierdzenie płatności')

@section('content')
<h2>Cześć, {{ $client->name }}! ✅</h2>
<p>Twoja płatność za pakiet treningów została <strong>potwierdzona</strong>. Dziękujemy!</p>

<div class="info-box">
  <p>📦 <strong>Pakiet:</strong> {{ $package->total_sessions }} treningów</p>
  <p>💰 <strong>Kwota:</strong> {{ $price }} zł</p>
  <p>📊 <strong>Wykorzystano:</strong> {{ $package->used_sessions }} / {{ $package->total_sessions }}</p>
  @if($package->valid_to)
  <p>📅 <strong>Ważny do:</strong> {{ \Carbon\Carbon::parse($package->valid_to)->format('d.m.Y') }}</p>
  @endif
</div>

<p>Możesz teraz w pełni korzystać ze swojego pakietu. Trenuj ciężko! 💪</p>
@endsection
