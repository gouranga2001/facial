@php
    $me       = auth()->user();
    $meName   = trim($me->first_name . ' ' . $me->last_name);
    $meInit   = strtoupper(mb_substr($me->first_name, 0, 1) . mb_substr($me->last_name ?? '', 0, 1));
    $meRole   = $me->role?->name ?? 'Super admin';
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-black/10 bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" data-sidebar-toggle aria-label="Toggle navigation"
            class="-ml-1 grid h-10 w-10 place-items-center rounded-xl text-black/70 transition hover:bg-black/5 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/20 lg:hidden">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
    </button>

    <p class="min-w-0 truncate text-sm font-semibold">
        <span class="text-black/45">Super admin</span>
        <span class="mx-2 text-black/25">/</span>
        @yield('title')
    </p>

    <div class="ml-auto flex items-center gap-3">
        <div class="hidden text-right leading-tight sm:block">
            <p class="text-sm font-bold">{{ $meName }}</p>
            <p class="text-xs text-black/50">{{ $meRole }}</p>
        </div>

        <span class="grid h-9 w-9 place-items-center rounded-full bg-red-600 text-xs font-bold text-white" aria-hidden="true">{{ $meInit }}</span>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit"
                    class="rounded-xl border border-black/15 px-3.5 py-2 text-sm font-semibold transition hover:border-red-600 hover:text-red-600 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/20">
                Sign out
            </button>
        </form>
    </div>
</header>