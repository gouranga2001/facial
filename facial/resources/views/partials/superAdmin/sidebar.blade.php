@php
    $has = fn (string $name) => \Illuminate\Support\Facades\Route::has($name);
    $home = $has('superadmin.users.index') ? route('superadmin.users.index') : url('/');

    // Items turn into live links automatically once their route name exists.
    $items = [
        [
            'label' => 'Users', 'route' => 'superadmin.users.index', 'match' => 'superadmin.users.*',
            'icon'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.4 3.4-5 6.5-5s5.7 1.6 6.5 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15.3c1.8.7 3 2.3 3.5 4.7"/>',
        ],
        [
            'label' => 'Organisations', 'route' => 'superadmin.organisations.index', 'match' => 'superadmin.organisations.*',
            'icon'  => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M8 7h4M8 11h4M8 15h4M2 21h20"/>',
        ],
        [
            'label' => 'Streams', 'route' => 'superadmin.streams.index', 'match' => 'superadmin.streams.*',
            'icon'  => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        ],
        [
            'label' => 'Subjects', 'route' => 'superadmin.subjects.index', 'match' => 'superadmin.subjects.*',
            'icon'  => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z"/><path d="M4 21h15"/>',
        ],
        [
            'label' => 'Routines', 'route' => 'superadmin.routines.index', 'match' => 'superadmin.routines.*',
            'icon'  => '<rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        ],
    ];
    $base = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/40';
@endphp

<div id="sidebar-scrim" class="fixed inset-0 z-[35] hidden bg-black/50 lg:hidden"></div>

<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-black text-white transition-transform duration-200 motion-reduce:transition-none lg:translate-x-0">

    <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-5">
        <a href="{{ $home }}" class="flex items-center gap-3 text-lg font-extrabold tracking-tight">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-600">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/>
                    <circle cx="12" cy="10" r="2.5"/><path d="M7.5 17c.8-2 2.5-3 4.5-3s3.7 1 4.5 3"/>
                </svg>
            </span>
            facial
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Main">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-widest text-white/35">Manage</p>

        @foreach ($items as $item)
            @php
                $enabled = $has($item['route']);
                $active  = $enabled && request()->routeIs($item['match']);
            @endphp

            @if ($enabled)
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                   class="{{ $base }} {{ $active ? 'bg-red-600 text-white shadow-lg shadow-red-600/25' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                    {{ $item['label'] }}
                </a>
            @else
                <div class="{{ $base }} cursor-not-allowed text-white/30" title="Coming soon">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                    {{ $item['label'] }}
                    <span class="ml-auto rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Soon</span>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-5 py-4 text-xs text-white/40">Super admin console</div>
</aside>