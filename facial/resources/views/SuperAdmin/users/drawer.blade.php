{{-- Fragment loaded into the right-side drawer. No layout: returned for XHR requests only. --}}
@php
    $status   = $user->status instanceof \BackedEnum ? $user->status->value : $user->status;
    $name     = trim($user->first_name . ' ' . $user->last_name);
    $initials = strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
    $fmt      = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y, h:i A') : null;

    $groups = [
        'Account' => [
            'Organisation' => $user->organisation?->name,
            'Role'         => $user->role?->name,
            'Employee ID'  => $user->employee_id,
            'Stream'       => $user->stream ? $user->stream->name . ' (' . $user->stream->code . ')' : null,
        ],
        'Student' => $user->studentProfile ? [
            'Roll number' => $user->studentProfile->roll_number,
            'Semester'    => $user->studentProfile->semester,
        ] : [],
        'Activity' => [
            'Last login'     => $fmt($user->last_login_at) ?? 'Never',
            'Created'        => $fmt($user->created_at),
        ],
    ];
@endphp

<div class="p-6">
    {{-- Identity --}}
    <div class="flex items-center gap-4">
        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-black text-xl font-extrabold text-white" aria-hidden="true">{{ $initials }}</span>
        <div class="min-w-0">
            <h3 class="truncate text-xl font-extrabold tracking-tight">{{ $name }}</h3>
            <p class="truncate text-sm text-black/60">{{ $user->email }}</p>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-black/5 text-black/60' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $status === 'active' ? 'bg-emerald-500' : 'bg-black/30' }}"></span>
            {{ ucfirst($status) }}
        </span>
        @if ($user->role)
            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user->role->slug === 'super_admin' ? 'bg-red-50 text-red-700 ring-1 ring-red-600/20' : 'bg-black/5 text-black/70' }}">
                {{ $user->role->name }}
            </span>
        @endif
    </div>

    {{-- Detail groups --}}
    @foreach ($groups as $title => $rows)
        @continue(empty($rows))
        <section class="mt-8">
            <h4 class="text-xs font-semibold uppercase tracking-widest text-black/45">{{ $title }}</h4>
            <dl class="mt-3 divide-y divide-black/10 rounded-xl border border-black/10">
                @foreach ($rows as $label => $value)
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-sm text-black/55">{{ $label }}</dt>
                        <dd class="text-right text-sm font-semibold">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach

</div>