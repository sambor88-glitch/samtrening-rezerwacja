@extends('emails.layout')

@section('content')
<h2 style="color:#e91e8c;margin:0 0 16px;">📅 Nowa rezerwacja</h2>
<p style="margin:0 0 16px;">Cześć <strong>{{ $trainer->name }}</strong>,</p>
<p style="margin:0 0 20px;">
  Klient <strong>{{ $client?->name ?? 'nieznany' }}</strong> właśnie zarezerwował trening.
</p>

<div style="background:#fff3f9;border-left:4px solid #e91e8c;border-radius:8px;padding:16px;margin:0 0 20px;">
  <table style="width:100%;font-size:14px;">
    <tr><td style="color:#888;padding:4px 0;">Data:</td><td style="font-weight:600;">{{ $date }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Godzina:</td><td style="font-weight:600;">{{ $booking->time ?? '—' }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Klient:</td><td style="font-weight:600;">{{ $client?->name ?? '—' }}</td></tr>
    <tr><td style="color:#888;padding:4px 0;">Status:</td><td><span style="background:#fff3cd;color:#856404;padding:2px 10px;border-radius:10px;font-size:12px;">Oczekuje potwierdzenia</span></td></tr>
  </table>
</div>

<p style="margin:0 0 20px;font-size:13px;color:#666;">
  Zaloguj się do panelu trenera, aby potwierdzić lub odrzucić rezerwację.
</p>

<div style="text-align:center;margin:24px 0;">
  <a href="{{ config('app.url') }}/trainer" style="background:#e91e8c;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:15px;">
    Otwórz panel trenera →
  </a>
</div>
@endsection
