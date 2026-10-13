@extends('layouts.auth')

@section('title', 'Set up your organisation')
@section('headline', 'Set up your organisation.')
@section('subline', 'Name your organisation and create the admin account. You can invite your team afterwards.')

@php
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-3 text-[15px] outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
    $err = 'mt-1.5 text-sm font-medium text-red-600';
@endphp

@section('content')
    <h1 class="text-3xl font-extrabold tracking-tight">Set up your organisation</h1>
    <p class="mt-2 text-black/60">Takes about a minute. You'll be the organisation admin.</p>

    <form method="POST" action="{{ route('setup.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="organisation_name" class="mb-1.5 block text-sm font-semibold">Organisation name</label>
            <input id="organisation_name" name="organisation_name" type="text"
                   value="{{ old('organisation_name') }}" placeholder="Acme Inc." required autofocus
                   class="{{ $input }} @error('organisation_name') border-red-600 @enderror">
            @error('organisation_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="first_name" class="mb-1.5 block text-sm font-semibold">First name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}"
                       placeholder="Jane" autocomplete="given-name" required
                       class="{{ $input }} @error('first_name') border-red-600 @enderror">
                @error('first_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="last_name" class="mb-1.5 block text-sm font-semibold">
                    Last name <span class="font-normal text-black/40">(optional)</span>
                </label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}"
                       placeholder="Doe" autocomplete="family-name"
                       class="{{ $input }} @error('last_name') border-red-600 @enderror">
                @error('last_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   placeholder="name@company.com" autocomplete="email" required
                   class="{{ $input }} @error('email') border-red-600 @enderror">
            @error('email') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" placeholder="At least 8 characters"
                           autocomplete="new-password" required
                           class="{{ $input }} pr-16 @error('password') border-red-600 @enderror">
                    <button type="button" data-toggle-password="password"
                            class="absolute inset-y-0 right-4 text-sm font-semibold text-black/50 hover:text-black">Show</button>
                </div>
                @error('password') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       placeholder="Repeat password" autocomplete="new-password" required
                       class="{{ $input }}">
            </div>
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-red-600 px-5 py-3.5 font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 active:scale-[.99]">
            Create organisation
        </button>
    </form>

    <p class="mt-10 border-t border-black/10 pt-6 text-sm text-black/60">
        Already set up?
        <a href="{{ route('login') }}" class="font-bold text-red-600 hover:underline">Sign in</a>
    </p>
@endsection