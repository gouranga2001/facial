@php
    $status   = $organisation->status instanceof \BackedEnum ? $organisation->status->value : $organisation->status;
    $initials = strtoupper(mb_substr($organisation->name, 0, 2));
    $badges = [
        'active'    => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', 'bg-emerald-500'],
        'suspended' => ['bg-red-50 text-red-700 ring-1 ring-red-600/20', 'bg-red-500'],
        'inactive'  => ['bg-black/5 text-black/60', 'bg-black/30'],
    ];
    [$badgeCls, $dotCls] = $badges[$status] ?? $badges['inactive'];
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-2.5 text-sm outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
    $hasUsers = $organisation->users_count > 0;
@endphp

<div class="space-y-8 p-6">
    {{-- Identity --}}
    <div class="flex items-center gap-4">
        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-black text-xl font-extrabold text-white" aria-hidden="true">{{ $initials }}</span>
        <div class="min-w-0">
            <h3 class="truncate text-xl font-extrabold tracking-tight">{{ $organisation->name }}</h3>
            <p class="truncate text-sm text-black/55">{{ $organisation->slug }}</p>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeCls }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $dotCls }}"></span>
                {{ ucfirst($status) }}
            </span>
        </div>
    </div>

    {{-- Details --}}
    <dl class="divide-y divide-black/5 rounded-2xl border border-black/10 text-sm">
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-black/55">UUID</dt>
            <dd class="flex min-w-0 items-center gap-2">
                <span class="truncate font-mono text-xs">{{ $organisation->uuid }}</span>
                <button type="button" data-copy="{{ $organisation->uuid }}"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold text-red-600 transition hover:bg-red-50">Copy</button>
            </dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-black/55">Users</dt>
            <dd class="font-semibold">
                @if ($hasUsers && \Illuminate\Support\Facades\Route::has('superadmin.users.index'))
                    <a href="{{ route('superadmin.users.index', ['organisation' => $organisation->uuid]) }}"
                       class="text-red-600 hover:underline">{{ number_format($organisation->users_count) }} — view</a>
                @else
                    {{ number_format($organisation->users_count) }}
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-black/55">Created</dt>
            <dd class="font-semibold">{{ $organisation->created_at?->format('d M Y, H:i') ?? '—' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-black/55">Last updated</dt>
            <dd class="font-semibold">{{ $organisation->updated_at?->diffForHumans() ?? '—' }}</dd>
        </div>
    </dl>

    {{-- Edit --}}
    <form data-org-form="update" method="POST" action="{{ route('superadmin.organisations.update', $organisation->uuid) }}" class="space-y-4">
        @csrf
        @method('PATCH')

        <h4 class="text-sm font-extrabold uppercase tracking-wider text-black/50">Edit</h4>

        <div data-form-error role="alert"
             class="hidden rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></div>

        <div>
            <label for="org-name" class="mb-1.5 block text-sm font-semibold">Name</label>
            <input id="org-name" type="text" name="name" value="{{ $organisation->name }}" maxlength="150" required class="{{ $input }}">
        </div>

        <div>
            <label for="org-status" class="mb-1.5 block text-sm font-semibold">Status</label>
            <select id="org-status" name="status" class="{{ $input }}">
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-black/50">Suspended or inactive organisations should not be able to sign in.</p>
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 active:scale-[.99] disabled:opacity-60">
            Save changes
        </button>
    </form>

    {{-- Danger zone --}}
    <form data-org-form="delete" method="POST" action="{{ route('superadmin.organisations.destroy', $organisation->uuid) }}"
          data-confirm="Delete “{{ $organisation->name }}”? This cannot be undone."
          class="space-y-3 rounded-2xl border border-red-600/20 bg-red-50/40 p-4">
        @csrf
        @method('DELETE')

        <h4 class="text-sm font-extrabold uppercase tracking-wider text-red-700">Danger zone</h4>

        <div data-form-error role="alert"
             class="hidden rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></div>

        <p class="text-sm text-black/60">
            @if ($hasUsers)
                This organisation still has {{ number_format($organisation->users_count) }} {{ \Illuminate\Support\Str::plural('user', $organisation->users_count) }}.
                Remove or reassign them first, or set the status to inactive instead.
            @else
                Permanently deletes this organisation.
            @endif
        </p>

        <button type="submit" @disabled($hasUsers)
                class="w-full rounded-xl border border-red-600/30 bg-white px-5 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-600 hover:text-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/20 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-red-700">
            Delete organisation
        </button>
    </form>
</div>