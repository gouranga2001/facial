@extends('layouts.superAdmin')

@section('title', 'Organisations')

@section('content')
@php
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
    $statusOf   = fn ($o) => $o->status instanceof \BackedEnum ? $o->status->value : $o->status;
    $hasFilters = ! empty(array_filter($filters ?? []));
    $badges = [
        'active'    => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', 'bg-emerald-500'],
        'suspended' => ['bg-red-50 text-red-700 ring-1 ring-red-600/20', 'bg-red-500'],
        'inactive'  => ['bg-black/5 text-black/60', 'bg-black/30'],
    ];
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">Organisations</h1>
        <p class="mt-1 text-black/60">Every tenant on the platform. Select a row for details.</p>
    </div>
    <p class="text-sm font-semibold text-black/50">
        {{ number_format($organisations->total()) }} {{ \Illuminate\Support\Str::plural('organisation', $organisations->total()) }}
    </p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 rounded-xl border border-emerald-600/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-4 rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ $errors->first() }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('superadmin.organisations.index') }}"
      class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-black/10 bg-white p-4">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" aria-label="Search organisations"
           placeholder="Search name or slug"
           class="{{ $input }} min-w-[220px] flex-1">

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
        <a href="{{ route('superadmin.organisations.index') }}" class="text-sm font-semibold text-red-600 hover:underline">Reset</a>
    @endif
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-black/10 bg-neutral-50 text-xs font-semibold uppercase tracking-wider text-black/50">
                <tr>
                    <th scope="col" class="px-5 py-3">Organisation</th>
                    <th scope="col" class="px-5 py-3">Users</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3">Created</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-black/5">
                @forelse ($organisations as $org)
                    @php
                        $initials = strtoupper(mb_substr($org->name, 0, 2));
                        $status   = $statusOf($org);
                        [$badgeCls, $dotCls] = $badges[$status] ?? $badges['inactive'];
                        $showUrl  = route('superadmin.organisations.show', $org->uuid);
                    @endphp
                    <tr data-org-row data-org-uuid="{{ $org->uuid }}" data-org-url="{{ $showUrl }}"
                        class="cursor-pointer transition hover:bg-red-50/50">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-black text-xs font-bold text-white" aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0">
                                    <a href="{{ $showUrl }}"
                                       class="block truncate font-bold transition hover:text-red-600 focus-visible:text-red-600 focus-visible:outline-none focus-visible:underline">{{ $org->name }}</a>
                                    <p class="truncate text-black/55">{{ $org->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ number_format($org->users_count) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeCls }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotCls }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-black/60">
                            {{ $org->created_at?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-16 text-center">
                            <p class="font-bold">No organisations found</p>
                            <p class="mt-1 text-black/55">
                                @if ($hasFilters)
                    Try different filters or <a href="{{ route('superadmin.organisations.index') }}" class="font-semibold text-red-600 hover:underline">reset them</a>.
                                @else
                                    Organisations will appear here once they are added.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pager --}}
@if ($organisations->total() > 0)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-black/60">
            Showing <span class="font-semibold text-black">{{ $organisations->firstItem() }}–{{ $organisations->lastItem() }}</span>
            of <span class="font-semibold text-black">{{ number_format($organisations->total()) }}</span>
        </p>

        @if ($organisations->hasPages())
            <div class="flex items-center gap-2">
                @if ($organisations->onFirstPage())
                    <span class="rounded-xl border border-black/10 px-4 py-2 font-semibold text-black/30">Previous</span>
                @else
                    <a href="{{ $organisations->previousPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Previous</a>
                @endif

                <span class="px-1 text-black/55">Page {{ $organisations->currentPage() }} of {{ $organisations->lastPage() }}</span>

                @if ($organisations->hasMorePages())
                    <a href="{{ $organisations->nextPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Next</a>
                @else
                    <span class="rounded-xl border border-black/10 px-4 py-2 font-semibold text-black/30">Next</span>
                @endif
            </div>
        @endif
    </div>
@endif
@endsection

@push('overlays')
    <div id="drawer-backdrop" aria-hidden="true"
         class="pointer-events-none fixed inset-0 z-50 bg-black/40 opacity-0 transition-opacity duration-200 motion-reduce:transition-none"></div>

    <aside id="org-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true" inert
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-200 ease-out motion-reduce:transition-none">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-black/10 px-6">
            <h2 id="drawer-title" class="text-lg font-extrabold tracking-tight">Organisation details</h2>
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
            </div>
        </div>
    </template>
@endpush

@push('scripts')
<script>
    (() => {
        const drawer   = document.getElementById('org-drawer');
        const backdrop = document.getElementById('drawer-backdrop');
        const body     = document.getElementById('drawer-body');
        const closeBtn = document.getElementById('drawer-close');
        const skeleton = document.getElementById('drawer-skeleton').innerHTML;
        const showUrl  = @json(route('superadmin.organisations.show', ['uuid' => '__UUID__']));
        const UUID_RE  = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

        let trigger = null, pending = null, currentUrl = null;

        const setParam = (uuid) => {
            const url = new URL(window.location.href);
            uuid ? url.searchParams.set('organisation', uuid) : url.searchParams.delete('organisation');
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
            const text = status === 403 ? "You don't have access to this organisation."
                       : status === 404 ? 'This organisation no longer exists.'
                       : 'Could not load this organisation.';
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
                if (res.redirected) { window.location.reload(); return; }
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

        document.querySelectorAll('[data-org-row]').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                open(row.dataset.orgUrl, row.dataset.orgUuid, row);
            });
        });

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.inert) close(); });

        drawer.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            const focusable = [...drawer.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')];
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });

        // Copy + retry inside the loaded fragment.
        body.addEventListener('click', async (e) => {
            const copy = e.target.closest('[data-copy]');
            if (copy) {
                try {
                    await navigator.clipboard.writeText(copy.dataset.copy);
                    copy.textContent = 'Copied';
                    setTimeout(() => { copy.textContent = 'Copy'; }, 1500);
                } catch (_) {}
                return;
            }
            if (e.target.closest('[data-retry]')) load(currentUrl);
        });

        // Update / delete forms inside the fragment (submitted via fetch).
        body.addEventListener('submit', async (e) => {
            const form = e.target.closest('[data-org-form]');
            if (!form) return;
            e.preventDefault();

            const isDelete = form.dataset.orgForm === 'delete';
            if (isDelete && !window.confirm(form.dataset.confirm || 'Delete this organisation? This cannot be undone.')) return;

            const btn = form.querySelector('[type="submit"]');
            const box = form.querySelector('[data-form-error]');
            box.classList.add('hidden');
            btn.disabled = true;

            try {
                const res = await fetch(form.action, {
                    method: 'POST', // _method (PATCH/DELETE) is carried in the form data
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok || res.status === 419) {
                    if (isDelete) setParam(null);
                    window.location.reload();
                    return;
                }

                const data = await res.json().catch(() => ({}));
                box.textContent = data.errors ? Object.values(data.errors)[0][0]
                                : (data.message || 'Something went wrong.');
            } catch (_) {
                box.textContent = 'Network error. Please try again.';
            }

            box.classList.remove('hidden');
            btn.disabled = false;
        });

        // Deep link: ?organisation=<uuid>
        const initial = new URL(window.location.href).searchParams.get('organisation');
        if (initial && UUID_RE.test(initial)) {
            const row = document.querySelector(`[data-org-uuid="${initial}"]`);
            open(row ? row.dataset.orgUrl : showUrl.replace('__UUID__', initial), initial, row);
        }
    })();
</script>
@endpush