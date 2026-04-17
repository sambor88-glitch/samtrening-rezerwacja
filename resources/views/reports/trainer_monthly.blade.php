<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Raport miesięczny – {{ $trainer->name }} – {{ $monthLabel }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #222; padding: 30px; }
  h1 { font-size: 22px; color: #e91e8c; margin-bottom: 4px; }
  h2 { font-size: 15px; color: #555; margin-bottom: 20px; font-weight: normal; }
  h3 { font-size: 14px; color: #333; margin: 24px 0 10px; border-bottom: 2px solid #e91e8c; padding-bottom: 4px; }
  .summary { display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
  .card { background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 14px 20px; min-width: 130px; text-align: center; }
  .card .val { font-size: 26px; font-weight: bold; color: #e91e8c; }
  .card .lbl { font-size: 11px; color: #888; margin-top: 3px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #e91e8c; color: #fff; padding: 8px 10px; text-align: left; font-size: 12px; }
  td { padding: 7px 10px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) td { background: #fafafa; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
  .badge-completed { background:#d4edda; color:#155724; }
  .badge-cancelled { background:#f8d7da; color:#721c24; }
  .badge-confirmed { background:#cce5ff; color:#004085; }
  .badge-pending   { background:#fff3cd; color:#856404; }
  .footer { margin-top: 30px; text-align: center; color: #bbb; font-size: 11px; }
  @media print {
    body { padding: 15px; }
    .no-print { display: none; }
  }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:20px;">
  <button onclick="window.print()" style="background:#e91e8c;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;">
    🖨️ Drukuj / Zapisz jako PDF
  </button>
  <button onclick="window.close()" style="margin-left:10px;background:#eee;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;">
    Zamknij
  </button>
</div>

<h1>SAMtrening — Raport miesięczny</h1>
<h2>{{ $trainer->name }} &nbsp;|&nbsp; {{ $monthLabel }}</h2>

<div class="summary">
  <div class="card">
    <div class="val">{{ $completedCount }}</div>
    <div class="lbl">Treningów ukończonych</div>
  </div>
  <div class="card">
    <div class="val">{{ $cancelledCount }}</div>
    <div class="lbl">Odwołanych</div>
  </div>
  <div class="card">
    <div class="val">{{ number_format($totalRevenue, 0, ',', ' ') }} zł</div>
    <div class="lbl">Przychód (potwierdzone)</div>
  </div>
  <div class="card">
    <div class="val">{{ $bookings->count() }}</div>
    <div class="lbl">Wszystkich rezerwacji</div>
  </div>
</div>

<h3>Treningi w {{ $monthLabel }}</h3>
@if($bookings->isEmpty())
  <p style="color:#999;">Brak treningów w tym miesiącu.</p>
@else
<table>
  <thead>
    <tr>
      <th>Data</th>
      <th>Godzina</th>
      <th>Klient</th>
      <th>Czas (min)</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($bookings as $b)
    <tr>
      <td>{{ $b->date }}</td>
      <td>{{ $b->time ?? '—' }}</td>
      <td>{{ $b->client->name ?? 'Bez klienta' }}</td>
      <td>{{ $b->duration ?? '60' }}</td>
      <td>
        @if($b->completed)
          <span class="badge badge-completed">Ukończony</span>
        @elseif($b->status === 'cancelled')
          <span class="badge badge-cancelled">Odwołany</span>
        @elseif($b->status === 'confirmed')
          <span class="badge badge-confirmed">Potwierdzony</span>
        @else
          <span class="badge badge-pending">Oczekujący</span>
        @endif
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

<h3>Płatności potwierdzone w {{ $monthLabel }}</h3>
@if($payments->isEmpty())
  <p style="color:#999;">Brak potwierdzonych płatności w tym miesiącu.</p>
@else
<table>
  <thead>
    <tr>
      <th>Data</th>
      <th>Klient</th>
      <th>Kwota</th>
    </tr>
  </thead>
  <tbody>
    @foreach($payments as $p)
    <tr>
      <td>{{ $p->created_at->format('Y-m-d') }}</td>
      <td>{{ $p->client->name ?? '—' }}</td>
      <td>{{ number_format($p->amount, 2, ',', ' ') }} zł</td>
    </tr>
    @endforeach
    <tr style="font-weight:bold;background:#fff3f9;">
      <td colspan="2">SUMA</td>
      <td>{{ number_format($totalRevenue, 2, ',', ' ') }} zł</td>
    </tr>
  </tbody>
</table>
@endif

<div class="footer">
  Wygenerowano: {{ now()->format('Y-m-d H:i') }} &nbsp;|&nbsp; SAMtrening
</div>

</body>
</html>
