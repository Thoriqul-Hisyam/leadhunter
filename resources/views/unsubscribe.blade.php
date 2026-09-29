@php($c = $brand['colors'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Berhenti Berlangganan — {{ $brand['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="{{ \App\Mail\EmailBrand::FONT_URL }}" rel="stylesheet">
    <style>
        body { margin: 0; font-family: {!! $brand['font'] !!}; background: #fbfbfe radial-gradient(#0000000a 1px, transparent 1px) 0 0 / 28px 28px; color: {{ $c['text'] }}; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; padding: 24px 16px; box-sizing: border-box; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        .brand, .card { position: relative; z-index: 1; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; font-size: 24px; font-weight: 900; letter-spacing: -0.6px; color: {{ $c['ink'] }}; }
        .brand img { height: 36px; width: auto; }
        .mark { width: 34px; height: 34px; border-radius: 50%; background: {{ $c['purple'] }}; border: 3px solid {{ $c['lime'] }}; color: #fff; font-size: 16px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
        /* Kartu kaca beku + orb kaca 3D beranimasi, sama seperti lefateach.com */
        .card { max-width: 460px; width: 100%; border-radius: 28px; padding: 36px 32px; text-align: center; box-sizing: border-box; background: linear-gradient(135deg, #ffffffa6 0%, #ffffff4d 100%); border: 1px solid #ffffffb3; box-shadow: 0 24px 48px -15px #00000014, inset 0 2px 4px #ffffffe6, inset 0 -2px 4px #0000000d; -webkit-backdrop-filter: blur(20px) saturate(180%); backdrop-filter: blur(20px) saturate(180%); }
        .orb { position: fixed; z-index: 0; pointer-events: none; width: 300px; height: 300px; }
        .orb.lime { top: 8%; left: max(-60px, calc(50% - 420px)); opacity: .8; }
        .orb.purple { bottom: 6%; right: max(-60px, calc(50% - 420px)); opacity: .75; }
        .orb .glow { position: absolute; inset: 0; border-radius: 9999px; filter: blur(40px); opacity: .4; }
        .orb .shape { position: relative; width: 100%; height: 100%; overflow: hidden; }
        .orb.lime .glow { background: {{ $c['lime'] }}; }
        .orb.purple .glow { background: {{ $c['purple'] }}; }
        .orb.lime .shape { background: linear-gradient(135deg, #c8f82859 0%, #c8f8280f 100%); border: 1px solid #c8f82880; box-shadow: 0 20px 40px -10px #c8f8284d, inset 0 2px 4px #ffffffb3, inset 0 -2px 4px #0000001a; animation: morph3d 18s ease-in-out infinite; }
        .orb.purple .shape { background: linear-gradient(135deg, #5433ff61 0%, #5433ff14 100%); border: 1px solid #5433ff80; box-shadow: 0 20px 40px -10px #5433ff59, inset 0 2px 4px #fff9, inset 0 -2px 4px #0003; animation: morph3dReverse 15s ease-in-out infinite; }
        .orb .hl1 { position: absolute; top: 8px; left: 16px; width: 50%; height: 33%; background: linear-gradient(to bottom, #ffffffcc, #ffffff4d, transparent); border-radius: 9999px; filter: blur(2px); transform: rotate(-12deg); }
        .orb .hl2 { position: absolute; inset: 0; background: linear-gradient(to top right, transparent, #ffffff26, transparent); transform: rotate(45deg); }
        .orb .hl3 { position: absolute; bottom: 12px; right: 20px; width: 25%; height: 25%; background: #ffffff33; border-radius: 9999px; filter: blur(4px); }
        @keyframes morph3d { 0%, 100% { border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%; transform: translate(0) rotate(0) scale(1); } 25% { border-radius: 58% 42% 38% 62% / 52% 64% 36% 48%; transform: translate3d(12px, -15px, 20px) rotate(90deg) scale(1.05); } 50% { border-radius: 34% 66% 61% 39% / 67% 33%; transform: translate3d(-10px, 12px, -10px) rotate(180deg) scale(.98); } 75% { border-radius: 68% 32% 45% 55% / 37% 55% 45% 63%; transform: translate3d(15px, -8px, 15px) rotate(270deg) scale(1.04); } }
        @keyframes morph3dReverse { 0%, 100% { border-radius: 65% 35% 38% 62% / 48% 60% 40% 52%; transform: translate(0) rotate(360deg) scale(1); } 33% { border-radius: 32% 68% 59% 41% / 61% 38% 62% 39%; transform: translate3d(-15px, -10px, 15px) rotate(240deg) scale(1.06); } 66% { border-radius: 54% 46% 30% 70% / 35% 55% 45% 65%; transform: translate3d(10px, 15px, -12px) rotate(120deg) scale(.96); } }
        @media (prefers-reduced-motion: reduce) { .orb .shape { animation: none; } }
        h1 { font-size: 26px; font-weight: 800; letter-spacing: -0.6px; line-height: 1.25; margin: 0 0 14px; color: {{ $c['ink'] }}; }
        h1 span { background: {{ $c['lime'] }}; border-radius: 12px; padding: 0 8px; }
        p { font-size: 15px; line-height: 1.65; margin: 0; }
        .email { font-weight: 700; color: {{ $c['purple'] }}; word-break: break-all; }
        .note { margin-top: 14px; color: {{ $c['muted'] }}; font-size: 13px; }
    </style>
</head>
<body>
    @foreach(['lime', 'purple'] as $orb)
        <div class="orb {{ $orb }}" aria-hidden="true"><div class="glow"></div><div class="shape"><div class="hl1"></div><div class="hl2"></div><div class="hl3"></div></div></div>
    @endforeach
    <div class="brand">
        @if($brand['logo'])
            <img src="{{ $brand['logo'] }}" alt="">
        @else
            <span class="mark">{{ $brand['initial'] }}</span>
        @endif
        <span>{{ $brand['name'] }}</span>
    </div>
    <div class="card">
        <h1>Anda sudah <span>berhenti</span> berlangganan</h1>
        @if($email)
            <p>Alamat <span class="email">{{ $email }}</span> tidak akan menerima email penawaran dari {{ $brand['name'] }} lagi.</p>
        @else
            <p>Anda tidak akan menerima email penawaran dari {{ $brand['name'] }} lagi.</p>
        @endif
        <p class="note">Mohon maaf atas ketidaknyamanannya.</p>
    </div>
</body>
</html>
