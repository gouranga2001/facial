@extends('layouts.superAdmin')

@section('title', 'Users')

@section('content')
@php
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
    $statusOf   = fn ($u) => $u->status instanceof \BackedEnum ? $u->status->value : $u->status;
    $hasFilters = ! empty(array_filter($filters ?? []));
    $cols       = $isSuperAdmin ? 6 : 5;
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">Users</h1>
        <p class="mt-1 text-black/60">Everyone across {{ $isSuperAdmin ? 'all organisations' : 'your organisation' }}. Select a row for details.</p>
    </div>
    <p class="text-sm font-semibold text-black/50">
        {{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}
    </p>
</div>

@if ($errors->any())
    <div role="alert" class="mb-4 rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ $errors->first() }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('superadmin.users.index') }}"
      class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-black/10 bg-white p-4">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" aria-label="Search users"
           placeholder="Search name, email or employee ID"
           class="{{ $input }} min-w-[220px] flex-1">

    @if ($isSuperAdmin)
        <select name="organisation" aria-label="Organisation" class="{{ $input }} sm:w-auto">
            <option value="">All organisations</option>
            @foreach ($organisations as $org)
                <option value="{{ $org->uuid }}" @selected(($filters['organisation'] ?? '') === $org->uuid)>{{ $org->name }}</option>
            @endforeach
        </select>
    @endif

    <select name="role" aria-label="Role" class="{{ $input }} sm:w-auto">
        <option value="">All roles</option>
        @foreach ($roles as $role)
            <option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->name }}</option>
        @endforeach
    </select>

    <select name="status" aria-label="Status" class="{{ $input }} sm:w-auto">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <button type="submit"
            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 active:scale-[.99]">
        Filter
    </button>

    @if ($hasFilters)
        <a href="{{ route('superadmin.users.index') }}" class="text-sm font-semibold text-red-600 hover:underline">Reset</a>
    @endif
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-black/10 bg-neutral-50 text-xs font-semibold uppercase tracking-wider text-black/50">
                <tr>
                    <th scope="col" class="px-5 py-3">User</th>
                    <th scope="col" class="px-5 py-3">Role</th>
                    @if ($isSuperAdmin)<th scope="col" class="px-5 py-3">Organisation</th>@endif
                    <th scope="col" class="px-5 py-3">Stream</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3">Last login</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-black/5">
                @forelse ($users as $user)
                    @php
                        $name     = trim($user->first_name . ' ' . $user->last_name);
                        $initials = strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
                        $status   = $statusOf($user);
                        $showUrl  = route('superadmin.users.show', $user->uuid);
                    @endphp
                    <tr data-user-row data-user-uuid="{{ $user->uuid }}" data-user-url="{{ $showUrl }}"
                        class="cursor-pointer transition hover:bg-red-50/50">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-black text-xs font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0">
                                    <a href="{{ $showUrl }}"
                                       class="block truncate font-bold transition hover:text-red-600 focus-visible:text-red-600 focus-visible:outline-none focus-visible:underline">{{ $name }}</a>
                                    <p class="truncate text-black/55">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $user->role?->name ?? '—' }}</td>
                        @if ($isSuperAdmin)
                            <td class="whitespace-nowrap px-5 py-3.5">{{ $user->organisation?->name ?? '—' }}</td>
                        @endif
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $user->stream?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-black/5 text-black/60' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $status === 'active' ? 'bg-emerald-500' : 'bg-black/30' }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-black/60">
                            {{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $cols }}" class="px-5 py-16 text-center">
                            <p class="font-bold">No users found</p>
                            <p class="mt-1 text-black/55">{{ $hasFilters ? 'Try different filters or ' : '' }}@if ($hasFilters)<a href="{{ route('superadmin.users.index') }}" class="font-semibold text-red-600 hover:underline">reset them</a>.@else Users will appear here once they are added.@endif</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pager --}}
@if ($users->total() > 0)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-black/60">
            Showing <span class="font-semibold text-black">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span>
            of <span class="font-semibold text-black">{{ number_format($users->total()) }}</span>
        </p>

        @if ($users->hasPages())
            <div class="flex items-center gap-2">
                @if ($users->onFirstPage())
                    <span class="rounded-xl border border-black/10 px-4 py-2 font-semibold text-black/30">Previous</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Previous</a>
                @endif

                <span class="px-1 text-black/55">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Next</a>
                @else
                    <span class="rounded-xl border border-black/10 px-4 py-2 font-semibold text-black/30">Next</span>
                @endif
            </div>
        @endif
    </div>
@endif
@endsection

@push('overlays')
    {{-- Drawer backdrop --}}
    <div id="drawer-backdrop" aria-hidden="true"
         class="pointer-events-none fixed inset-0 z-50 bg-black/40 opacity-0 transition-opacity duration-200 motion-reduce:transition-none"></div>

    {{-- Right-side drawer --}}
    <aside id="user-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true" inert
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-200 ease-out motion-reduce:transition-none">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-black/10 px-6">
            <h2 id="drawer-title" class="text-lg font-extrabold tracking-tight">User details</h2>
            <button type="button" id="drawer-close" aria-label="Close details"
                    class="grid h-9 w-9 place-items-center rounded-xl text-black/60 transition hover:bg-black/5 hover:text-black focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/20">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div id="drawer-body" class="flex-1 overflow-y-auto" aria-live="polite"></div>
    </aside>

    <template id="drawer-skeleton">
        <div class="animate-pulse space-y-8 p-6 motion-reduce:animate-none" aria-busy="true">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-2xl bg-black/10"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-5 w-2/3 rounded bg-black/10"></div>
                    <div class="h-4 w-1/2 rounded bg-black/10"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="h-11 rounded-xl bg-black/10"></div>
                <div class="h-11 rounded-xl bg-black/10"></div>
                <div class="h-11 rounded-xl bg-black/10"></div>
                <div class="h-11 rounded-xl bg-black/10"></div>
            </div>
        </div>
    </template>
@endpush

@push('scripts')
<script>
    (() => {
        const drawer   = document.getElementById('user-drawer');
        const backdrop = document.getElementById('drawer-backdrop');
        const body     = document.getElementById('drawer-body');
        const closeBtn = document.getElementById('drawer-close');
        const skeleton = document.getElementById('drawer-skeleton').innerHTML;
        const showUrl  = @json(route('superadmin.users.show', ['uuid' => '__UUID__']));
        const UUID_RE  = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

        let trigger = null, pending = null, currentUrl = null;

        // Keep ?user=<uuid> in the address bar so a drawer can be shared / refreshed.
        const setParam = (uuid) => {
            const url = new URL(window.location.href);
            uuid ? url.searchParams.set('user', uuid) : url.searchParams.delete('user');
            history.replaceState(null, '', url);
        };

        const setOpen = (open) => {
            drawer.classList.toggle('translate-x-full', !open);
            drawer.inert = !open;
            drawer.setAttribute('aria-hidden', String(!open));
            backdrop.classList.toggle('opacity-0', !open);
            backdrop.classList.toggle('pointer-events-none', !open);
            document.documentElement.classList.toggle('overflow-hidden', open);
        };

        const failure = (status) => {
            const text = status === 403 ? "You don't have access to this user."
                       : status === 404 ? 'This user no longer exists.'
                       : 'Could not load this user.';
            const retry = (status === 403 || status === 404) ? '' :
                '<button type="button" data-retry class="mt-4 rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-700">Try again</button>';
            return `<div class="p-10 text-center"><p class="font-semibold">${text}</p>${retry}</div>`;
        };

        const load = async (url) => {
            pending?.abort();
            pending = new AbortController();
            currentUrl = url;
            body.innerHTML = skeleton;

            try {
                const res = await fetch(url, {
                    signal: pending.signal,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (res.redirected) { window.location.reload(); return; } // session expired
                body.innerHTML = res.ok ? await res.text() : failure(res.status);
            } catch (e) {
                if (e.name !== 'AbortError') body.innerHTML = failure(0);
            }
        };

        const open = (url, uuid, from) => {
            trigger = from || document.activeElement;
            setOpen(true);
            setParam(uuid);
            closeBtn.focus();
            load(url);
        };

        const close = () => {
            pending?.abort();
            setOpen(false);
            setParam(null);
            if (trigger && trigger.focus) trigger.focus();
        };

        // Row / name click opens the drawer (Ctrl/Cmd/Shift-click still opens the link normally).
        document.querySelectorAll('[data-user-row]').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                open(row.dataset.userUrl, row.dataset.userUuid, row);
            });
        });

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.inert) close(); });

        // Keep Tab inside the drawer while it is open.
        drawer.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            const focusable = [...drawer.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')];
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });

        // Copy-to-clipboard and retry inside the loaded fragment.
        body.addEventListener('click', async (e) => {
            const copy = e.target.closest('[data-copy]');
            if (copy) {
                try {
                    await navigator.clipboard.writeText(copy.dataset.copy);
                    copy.textContent = 'Copied';
                    setTimeout(() => { copy.textContent = 'Copy'; }, 1500);
                } catch (_) { /* clipboard unavailable */ }
                return;
            }
            if (e.target.closest('[data-retry]')) load(currentUrl);
        });

        // Deep link: /superadmin/users?user=<uuid>
        const initial = new URL(window.location.href).searchParams.get('user');
        if (initial && UUID_RE.test(initial)) {
            const row = document.querySelector(`[data-user-uuid="${initial}"]`);
            open(row ? row.dataset.userUrl : showUrl.replace('__UUID__', initial), initial, row);
        }
    })();
</script>
@endpush