<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Berhenti Berlangganan — {{ $companyName }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f4f7f9; color: #334155; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box; }
        .card { background: #fff; max-width: 440px; width: 100%; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0,0,0,.06); text-align: center; }
        h1 { font-size: 20px; margin: 0 0 12px; color: #0f172a; }
        p { font-size: 14px; line-height: 1.6; margin: 0; }
        .email { font-weight: 600; color: #4f46e5; word-break: break-all; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Anda sudah berhenti berlangganan</h1>
        @if($email)
            <p>Alamat <span class="email">{{ $email }}</span> tidak akan menerima email penawaran dari {{ $companyName }} lagi.</p>
        @else
            <p>Anda tidak akan menerima email penawaran dari {{ $companyName }} lagi.</p>
        @endif
        <p style="margin-top: 12px; color: #94a3b8; font-size: 12px;">Mohon maaf atas ketidaknyamanannya.</p>
    </div>
</body>
</html>
