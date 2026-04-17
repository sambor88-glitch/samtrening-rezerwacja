<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title')</title>
<style>
  body { margin:0; padding:0; background:#f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width:600px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,0.08); }
  .header { background: linear-gradient(135deg, #e91e8c, #ff6b9d); padding:32px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:24px; letter-spacing:1px; }
  .header p { color:rgba(255,255,255,0.85); margin:6px 0 0; font-size:14px; }
  .body { padding:36px 40px; color:#333; }
  .body h2 { color:#1a1a2e; margin-top:0; font-size:20px; }
  .info-box { background:#f8f9fc; border-left:4px solid #e91e8c; border-radius:6px; padding:16px 20px; margin:20px 0; }
  .info-box p { margin:6px 0; font-size:15px; }
  .info-box strong { color:#1a1a2e; }
  .btn { display:inline-block; background:linear-gradient(135deg,#e91e8c,#ff6b9d); color:#fff; text-decoration:none; padding:14px 32px; border-radius:8px; font-size:15px; font-weight:600; margin:20px 0; }
  .footer { background:#f4f6f9; padding:20px 40px; text-align:center; color:#999; font-size:12px; border-top:1px solid #eee; }
  .footer a { color:#e91e8c; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>💪 SAMtrening</h1>
    <p>Twój osobisty plan treningowy</p>
  </div>
  <div class="body">
    @yield('content')
  </div>
  <div class="footer">
    <p>© {{ date('Y') }} SAMtrening &nbsp;|&nbsp; <a href="#">samtrening.pl</a></p>
    <p>Wiadomość wysłana automatycznie – nie odpowiadaj na tego emaila.</p>
  </div>
</div>
</body>
</html>
