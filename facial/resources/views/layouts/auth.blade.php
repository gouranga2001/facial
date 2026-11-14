<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · facial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Manrope', system-ui, sans-serif; }
        .glow { background:
            radial-gradient(60% 50% at 20% 100%, rgba(220,38,38,.55), transparent 70%),
            radial-gradient(40% 35% at 90% 0%, rgba(220,38,38,.25), transparent 70%); }
        .grid-lines { background-image:
            linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(70% 70% at 50% 40%, #000, transparent); }
        @media (prefers-reduced-motion: no-preference) {
            .rise { animation: rise .6s cubic-bezier(.2,.7,.2,1) both; }
            @keyframes rise { from { opacity: 0; transform: translateY(12px); } }
        }
    </style>
</head>

<body class="min-h-screen bg-white text-black antialiased">
<div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">

    {{-- Brand panel --}}
    <aside class="relative hidden overflow-hidden bg-black text-white lg:flex lg:flex-col lg:justify-between p-12">
        <div class="glow absolute inset-0"></div>
        <div class="grid-lines absolute inset-0"></div>

        <a href="/" class="relative flex items-center gap-3 text-xl font-extrabold tracking-tight">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-600">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/>
                    <circle cx="12" cy="10" r="2.5"/><path d="M7.5 17c.8-2 2.5-3 4.5-3s3.700 1 4.500 3"/>
                </svg>
            </span>
            facial
        </a>

        <div class="relative max-w-md">
            <h2 class="text-5xl font-extrabold leading-[1.05] tracking-tight">
                @yield('headline')
            </h2>
            <p class="mt-5 text-lg leading-relaxed text-white/60">
                @yield('subline')
            </p>
        </div>

        <p class="relative text-sm text-white/40">&copy; {{ date('Y') }} facial</p>
    </aside>

    {{-- Form panel --}}
    <main class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="rise w-full max-w-md">
            <a href="/" class="mb-10 flex items-center gap-2 text-lg font-extrabold tracking-tight lg:hidden">
                <span class="h-7 w-7 rounded-lg bg-red-600"></span> facial
            </a>
            @yield('content')
        </div>
    </main>
</div>

<script>
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });
</script>
</body>
</html>


{{-- Illuminate\Database\QueryException
vendor/laravel/framework/src/Illuminate/Database/Connection.php:857

SQLSTATE[HY000]: General error: 1364 Field 'uuid' doesn't have a default value (Connection: mariadb, Host: 127.0.0.1, Port: 3306, Database: facial, SQL: insert into `organisations` (`name`, `id`, `updated_at`, `created_at`) values (test orga, 01a1080b-4964-710c-ace4-8a9ca4c6d2b7, 2026-10-04 17:51:59, 2026-10-04 17:51:59))  --}}