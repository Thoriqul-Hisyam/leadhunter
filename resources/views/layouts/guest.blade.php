<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sandesa')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        })();
    </script>
    <style>
        body { background-color: #f8f6fc; }
        .glass-bg {
            background: radial-gradient(circle at 10% 10%, #e8e3f5 0%, transparent 40%),
                        radial-gradient(circle at 90% 90%, #f6e6ed 0%, transparent 40%);
            background-color: #f9f8fc;
            background-attachment: fixed;
        }
        [data-theme="dark"] body, [data-theme="dark"] .glass-bg { background: #0f111a; }
        .guest-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); }
        [data-theme="dark"] .guest-card { background: rgba(15, 23, 42, 0.75); }
        .guest-input {
            width: 100%; padding: 0.625rem 1rem; border-radius: 1rem; font-size: 0.875rem; font-weight: 500;
            background: #fff; border: 1px solid rgba(226, 232, 240, 0.8); outline: none;
        }
        .guest-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
        [data-theme="dark"] .guest-input { background: #0f172a; border-color: #1e293b; color: #e2e8f0; }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 dark:text-slate-200 glass-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <a href="{{ route('login') }}" class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Sandesa</a>
        </div>

        <div class="guest-card rounded-3xl p-8 border border-white/60 dark:border-slate-800 shadow-xl">
            @if (session('success') || session('status'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</body>
</html>
