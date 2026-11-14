@extends('layouts.superAdmin')

@section('title', 'Streams')

@section('content')
@php
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
    $statusOf   = fn ($s) => $s->status instanceof \BackedEnum ? $s->status->value : $s->status;
    $hasFilters = ! empty(array_filter($filters ?? []));
    $cols       = $isSuperAdmin ? 5 : 4;
    $badges = [
        'active'   => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', 'bg-emerald-500'],
        'inactive' => ['bg-black/5 text-black/60', 'bg-black/30'],
    ];
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight">Streams</h1>
        <p class="mt-1 text-black/60">Courses offered by {{ $isSuperAdmin ? 'every organisation' : 'your organisation' }}. Select a row for details.</p>
    </div>
    <div class="flex items-center gap-4">
        <p class="text-sm font-semibold text-black/50">
            {{ number_format($streams->total()) }} {{ \Illuminate\Support\Str::plural('stream', $streams->total()) }}
        </p>
        <button type="button" id="create-open"
                class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 active:scale-[.99]">
            New stream
        </button>
    </div>
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
<form method="GET" action="{{ route('superadmin.streams.index') }}"
      class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-black/10 bg-white p-4">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" aria-label="Search streams"
           placeholder="Search code or name"
           class="{{ $input }} min-w-[220px] flex-1">

    @if ($isSuperAdmin)
        <select name="organisation" aria-label="Organisation" class="{{ $input }} sm:w-auto">
            <option value="">All organisations</option>
            @foreach ($organisations as $org)
                <option value="{{ $org->uuid }}" @selected(($filters['organisation'] ?? '') === $org->uuid)>{{ $org->name }}</option>
            @endforeach
        </select>
    @endif

    <select name="status" aria-label="Status" class="{{ $input }} sm:w-auto">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <button type="submit"
            class="rounded-xl bg-black px-5 py-2.5 text-sm font-bold text-white transition hover:bg-black/80 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-black/20 active:scale-[.99]">
        Filter
    </button>

    @if ($hasFilters)
        <a href="{{ route('superadmin.streams.index') }}" class="text-sm font-semibold text-red-600 hover:underline">Reset</a>
    @endif
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-black/10 bg-neutral-50 text-xs font-semibold uppercase tracking-wider text-black/50">
                <tr>
                    <th scope="col" class="px-5 py-3">Stream</th>
                    @if ($isSuperAdmin)<th scope="col" class="px-5 py-3">Organisation</th>@endif
                    <th scope="col" class="px-5 py-3">Students</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3">Created</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-black/5">
                @forelse ($streams as $stream)
                    @php
                        $status  = $statusOf($stream);
                        [$badgeCls, $dotCls] = $badges[$status] ?? $badges['inactive'];
                        $showUrl = route('superadmin.streams.show', $stream->uuid);
                    @endphp
                    <tr data-stream-row data-stream-uuid="{{ $stream->uuid }}" data-stream-url="{{ $showUrl }}"
                        class="cursor-pointer transition hover:bg-red-50/50">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 min-w-9 shrink-0 place-items-center rounded-xl bg-black px-2 text-xs font-bold text-white" aria-hidden="true">{{ $stream->code }}</span>
                                <a href="{{ $showUrl }}"
                                   class="min-w-0 truncate font-bold transition hover:text-red-600 focus-visible:text-red-600 focus-visible:outline-none focus-visible:underline">{{ $stream->name }}</a>
                            </div>
                        </td>
                        @if ($isSuperAdmin)
                            <td class="whitespace-nowrap px-5 py-3.5">{{ $stream->organisation?->name ?? '—' }}</td>
                        @endif
                        <td class="whitespace-nowrap px-5 py-3.5">{{ number_format($stream->students_count) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeCls }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotCls }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-black/60">
                            {{ $stream->created_at?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $cols }}" class="px-5 py-16 text-center">
                            <p class="font-bold">No streams found</p>
                            <p class="mt-1 text-black/55">
                                @if ($hasFilters)
                                    Try different filters or <a href="{{ route('superadmin.streams.index') }}" class="font-semibold text-red-600 hover:underline">reset them</a>.
                                @else
                                    Use “New stream” to add the first one.
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
@if ($streams->total() > 0)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-black/60">
            Showing <span class="font-semibold text-black">{{ $streams->firstItem() }}–{{ $streams->lastItem() }}</span>
            of <span class="font-semibold text-black">{{ number_format($streams->total()) }}</span>
        </p>

        @if ($streams->hasPages())
            <div class="flex items-center gap-2">
                @if ($streams->onFirstPage())
                    <span class="rounded-xl border border-black/10 px-4 py-2 font-semibold text-black/30">Previous</span>
                @else
                    <a href="{{ $streams->previousPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Previous</a>
                @endif

                <span class="px-1 text-black/55">Page {{ $streams->currentPage() }} of {{ $streams->lastPage() }}</span>

                @if ($streams->hasMorePages())
                    <a href="{{ $streams->nextPageUrl() }}" class="rounded-xl border border-black/15 bg-white px-4 py-2 font-semibold transition hover:border-red-600 hover:text-red-600">Next</a>
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

    <aside id="stream-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true" inert
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-200 ease-out motion-reduce:transition-none">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-black/10 px-6">
            <h2 id="drawer-title" class="text-lg font-extrabold tracking-tight">Stream details</h2>
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

    {{-- Create form, shown inside the drawer --}}
    <template id="create-template">
        @php
            $fieldCls = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
        @endphp
        <form method="POST" action="{{ route('superadmin.streams.store') }}" data-stream-form="create" class="space-y-5 p-6">
            @csrf

            @if ($isSuperAdmin)
                <div>
                    <label for="create-organisation" class="mb-1.5 block text-sm font-semibold">Organisation</label>
                    <select id="create-organisation" name="organisation" required class="{{ $fieldCls }}">
                        <option value="">Select an organisation</option>
                        @foreach ($organisations as $org)
                            <option value="{{ $org->uuid }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="create-code" class="mb-1.5 block text-sm font-semibold">Code</label>
                <input id="create-code" name="code" required maxlength="50" placeholder="e.g. BCA"
                       class="{{ $fieldCls }} uppercase">
                <p class="mt-1.5 text-xs text-black/50">Letters, numbers, dot, dash or underscore. Must be unique within the organisation.</p>
            </div>

            <div>
                <label for="create-name" class="mb-1.5 block text-sm font-semibold">Name</label>
                <input id="create-name" name="name" required maxlength="100" placeholder="e.g. Bachelor of Computer Applications"
                       class="{{ $fieldCls }}">
            </div>

            <p data-form-error role="alert" class="hidden rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></p>

            <button type="submit"
                    class="w-full rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 disabled:opacity-60">
                Create stream
            </button>
        </form>
    </template>
@endpush

@push('scripts')
<script>
    (() => {
        const drawer    = document.getElementById('stream-drawer');
        const backdrop  = document.getElementById('drawer-backdrop');
        const body      = document.getElementById('drawer-body');
        const title     = document.getElementById('drawer-title');
        const closeBtn  = document.getElementById('drawer-close');
        const createBtn = document.getElementById('create-open');
        const skeleton  = document.getElementById('drawer-skeleton').innerHTML;
        const createTpl = document.getElementById('create-template').innerHTML;
        const showUrl   = @json(route('superadmin.streams.show', ['uuid' => '__UUID__']));
        const UUID_RE   = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

        let trigger = null, pending = null, currentUrl = null;

        const setParam = (uuid) => {
            const url = new URL(window.location.href);
            uuid ? url.searchParams.set('stream', uuid) : url.searchParams.delete('stream');
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
            const text = status === 403 ? "You don't have access to this stream."
                       : status === 404 ? 'This stream no longer exists.'
                       : 'Could not load this stream.';
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
            title.textContent = 'Stream details';
            setOpen(true);
            setParam(uuid);
            closeBtn.focus();
            load(url);
        };

        const openCreate = () => {
            pending?.abort();
            trigger = createBtn;
            title.textContent = 'New stream';
            body.innerHTML = createTpl;
            setOpen(true);
            setParam(null);
            (body.querySelector('select, input') || closeBtn).focus();
        };

        const close = () => {
            pending?.abort();
            setOpen(false);
            setParam(null);
            if (trigger && trigger.focus) trigger.focus();
        };

        document.querySelectorAll('[data-stream-row]').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                open(row.dataset.streamUrl, row.dataset.streamUuid, row);
            });
        });

        createBtn.addEventListener('click', openCreate);
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

        // Retry inside the loaded fragment.
        body.addEventListener('click', (e) => {
            if (e.target.closest('[data-retry]')) load(currentUrl);
        });

        // Create / update / delete forms (submitted via fetch).
        body.addEventListener('submit', async (e) => {
            const form = e.target.closest('[data-stream-form]');
            if (!form) return;
            e.preventDefault();

            const kind     = form.dataset.streamForm;           // create | update | delete
            const isDelete = kind === 'delete';
            if (isDelete && !window.confirm(form.dataset.confirm || 'Delete this stream? This cannot be undone.')) return;

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

        // Deep link: ?stream=<uuid>
        const initial = new URL(window.location.href).searchParams.get('stream');
        if (initial && UUID_RE.test(initial)) {
            const row = document.querySelector(`[data-stream-uuid="${initial}"]`);
            open(row ? row.dataset.streamUrl : showUrl.replace('__UUID__', initial), initial, row);
        }
    })();
</script>
@endpush