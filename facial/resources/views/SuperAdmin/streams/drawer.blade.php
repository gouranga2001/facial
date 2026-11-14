{{-- Fragment loaded into the right-side drawer. No layout: returned for XHR requests only. --}}
@php
    $status = $stream->status instanceof \BackedEnum ? $stream->status->value : $stream->status;
    $fmt    = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y, h:i A') : null;
    $field  = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';

    $rows = [
        'Organisation' => $stream->organisation?->name,
        'Students'     => number_format($stream->students_count),
        'Total users'  => number_format($stream->users_count),
        'Created'      => $fmt($stream->created_at),
        'Updated'      => $fmt($stream->updated_at),
    ];
@endphp

<div class="p-6">
    {{-- Identity --}}
    <div class="flex items-center gap-4">
        <span class="grid h-16 min-w-16 shrink-0 place-items-center rounded-2xl bg-black px-3 text-lg font-extrabold text-white" aria-hidden="true">{{ $stream->code }}</span>
        <div class="min-w-0">
            <h3 class="truncate text-xl font-extrabold tracking-tight">{{ $stream->name }}</h3>
            <p class="truncate text-sm text-black/60">{{ $stream->organisation?->name }}</p>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-black/5 text-black/60' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $status === 'active' ? 'bg-emerald-500' : 'bg-black/30' }}"></span>
            {{ ucfirst($status) }}
        </span>
    </div>

    {{-- Overview --}}
    <section class="mt-8">
        <h4 class="text-xs font-semibold uppercase tracking-widest text-black/45">Overview</h4>
        <dl class="mt-3 divide-y divide-black/10 rounded-xl border border-black/10">
            @foreach ($rows as $label => $value)
                <div class="flex items-start justify-between gap-4 px-4 py-3">
                    <dt class="text-sm text-black/55">{{ $label }}</dt>
                    <dd class="text-right text-sm font-semibold">{{ $value ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Edit --}}
    <section class="mt-8">
        <h4 class="text-xs font-semibold uppercase tracking-widest text-black/45">Edit stream</h4>

        <form method="POST" action="{{ route('superadmin.streams.update', $stream->uuid) }}"
              data-stream-form="update" class="mt-3 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="edit-code" class="mb-1.5 block text-sm font-semibold">Code</label>
                <input id="edit-code" name="code" value="{{ $stream->code }}" required maxlength="50" class="{{ $field }} uppercase">
            </div>

            <div>
                <label for="edit-name" class="mb-1.5 block text-sm font-semibold">Name</label>
                <input id="edit-name" name="name" value="{{ $stream->name }}" required maxlength="100" class="{{ $field }}">
            </div>

            <div>
                <label for="edit-status" class="mb-1.5 block text-sm font-semibold">Status</label>
                <select id="edit-status" name="status" class="{{ $field }}">
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <p data-form-error role="alert" class="hidden rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></p>

            <button type="submit"
                    class="w-full rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 disabled:opacity-60">
                Save changes
            </button>
        </form>
    </section>

    {{-- Delete --}}
    <section class="mt-8 rounded-xl border border-red-600/20 bg-red-50/50 p-4">
        <h4 class="text-sm font-bold text-red-700">Delete stream</h4>
        <p class="mt-1 text-sm text-black/60">
            @if ($stream->users_count > 0)
                {{ number_format($stream->users_count) }} {{ \Illuminate\Support\Str::plural('user', $stream->users_count) }} still assigned. Reassign them first, or set the stream to inactive instead.
            @else
                Permanently removes this stream. This cannot be undone.
            @endif
        </p>

        <form method="POST" action="{{ route('superadmin.streams.destroy', $stream->uuid) }}"
              data-stream-form="delete"
              data-confirm="Delete {{ $stream->name }}? This cannot be undone."
              class="mt-3">
            @csrf
            @method('DELETE')

            <p data-form-error role="alert" class="mb-3 hidden rounded-xl border border-red-600/20 bg-white px-4 py-3 text-sm font-medium text-red-700"></p>

            <button type="submit"
                    class="rounded-xl border border-red-600/30 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-600 hover:text-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/20 disabled:opacity-60">
                Delete stream
            </button>
        </form>
    </section>
</div>