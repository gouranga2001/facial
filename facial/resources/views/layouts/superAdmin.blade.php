<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · facial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Manrope', system-ui, sans-serif; }
        @media (prefers-reduced-motion: no-preference) {
            .rise { animation: rise .5s cubic-bezier(.2,.7,.2,1) both; }
            @keyframes rise { from { opacity: 0; transform: translateY(10px); } }
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-neutral-50 text-black antialiased">

    @include('partials.superAdmin.sidebar')

    <div class="lg:pl-64">
        @include('partials.superAdmin.navbar')

        <main class="rise mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    {{-- Overlays live outside <main> so they always stack above the navbar --}}
    @stack('overlays')

    <script>
        (() => {
            const sidebar = document.getElementById('sidebar');
            const scrim   = document.getElementById('sidebar-scrim');
            const toggle  = (open) => {
                sidebar.classList.toggle('-translate-x-full', !open);
                scrim.classList.toggle('hidden', !open);
            };

            document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) =>
                btn.addEventListener('click', () => toggle(sidebar.classList.contains('-translate-x-full'))));
            scrim.addEventListener('click', () => toggle(false));
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggle(false); });
        })();
    </script>
    @stack('scripts')
</body>
</html>