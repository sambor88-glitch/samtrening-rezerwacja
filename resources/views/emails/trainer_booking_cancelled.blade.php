@extends('emails.layout')

@section('content')
<h2 style="color:#dc2626;margin:0 0 16px;">❌ Trening odwołany przez klienta</h2>
<p style="margin:0 0 16px;">Cześć <strong>{{ $trainer->name }}</strong>,</p>
<p style="margin:0 0 20px;">
  Klient <strong>{{ $client?->name ?? 'nieznany' }}</strong> odwołał zaplanowany trening.
</p>

<div style="background:#fff5f5;border-left:4px solid #dc2626;border-radius:8px;padding:16px;margin:0 0 20px;">
  <table style="width:100%;font-size:14px;">
    <tr><td style="color:#888;padding:4px 0;">Data:</td><td style="font-weight:600;">{{ $date }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Godzina:</td><td style="font-weight:600;">{{ $booking->time ?? '—' }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Klient:</td><td style="font-weight:600;">{{ $client?->name ?? '—' }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Status:</td><td><span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:10px;font-size:12px;">Odwołany</span></td></tr>
  </table>
</div>

<p style="margin:0;font-size:13px;color:#666;">
  Ten termin jest teraz wolny. Możesz zaproponować go innemu klientowi.
</p>
@endsection
