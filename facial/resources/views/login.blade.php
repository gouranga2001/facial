@extends('layouts.auth')

@section('title', 'Sign in')
@section('headline', 'Welcome back.')
@section('subline', 'Sign in to pick up where you left off.')

@php
    $input = 'w-full rounded-xl border border-black/15 bg-white px-4 py-3 text-[15px] outline-none transition placeholder:text-black/35 focus:border-red-600 focus:ring-4 focus:ring-red-600/15';
@endphp

@section('content')
    <h1 class="text-3xl font-extrabold tracking-tight">Sign in</h1>
    <p class="mt-2 text-black/60">Use your work email and password.</p>

    <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   placeholder="name@company.com" autocomplete="email" required autofocus
                   class="{{ $input }} @error('email') border-red-600 @enderror">
            @error('email')
                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="text-sm font-semibold">Password</label>
                <a href="#" class="text-sm font-semibold text-red-600 hover:underline">Forgot password?</a>
            </div>
            <div class="relative">
                <input id="password" name="password" type="password"
                       placeholder="Enter your password" autocomplete="current-password" required
                       class="{{ $input }} pr-16">
                <button type="button" data-toggle-password="password"
                        class="absolute inset-y-0 right-4 text-sm font-semibold text-black/50 hover:text-black">Show</button>
            </div>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-black/70">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-black/30 accent-red-600">
            Keep me signed in
        </label>

        <button type="submit"
                class="w-full rounded-xl bg-red-600 px-5 py-3.5 font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-600/30 active:scale-[.99]">
            Sign in
        </button>
    </form>

    <p class="mt-10 border-t border-black/10 pt-6 text-sm text-black/60">
        New to facial?
        <a href="{{ route('setup') }}" class="font-bold text-red-600 hover:underline">Set up your organisation</a>
    </p>
@endsection