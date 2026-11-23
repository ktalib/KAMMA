@extends('portal.layouts.app')

@section('title', 'Sign in')

@php
    $input = 'mt-2 block w-full rounded-lg border-slate-300 px-3.5 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'block text-sm font-bold text-slate-800 dark:text-slate-200';
@endphp

@section('form')
    <form method="POST" action="{{ route('portal.login.submit') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="login" class="{{ $label }}">Username, phone or email</label>
            <input id="login" name="login" value="{{ old('login') }}" required autofocus autocapitalize="none" autocomplete="username" spellcheck="false"
                   placeholder="e.g. 08031234567" class="{{ $input }}">
        </div>
        <div x-data="{ show: false }">
            <label for="password" class="{{ $label }}">Password</label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="{{ $input }} pr-12">
                <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 mt-2 flex w-12 items-center justify-center text-slate-500 dark:text-slate-400" :aria-label="show ? 'Hide password' : 'Show password'">
                    <i x-show="! show" data-lucide="eye" class="h-5 w-5" aria-hidden="true"></i>
                    <i x-show="show" x-cloak data-lucide="eye-off" class="h-5 w-5" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            <i data-lucide="log-in" class="h-5 w-5" aria-hidden="true"></i> Sign in
        </button>
    </form>

    @if ($demo)
        <div class="mt-6 rounded-xl border border-dashed border-emerald-300 bg-emerald-50 p-4 dark:border-emerald-500/40 dark:bg-emerald-500/10">
            <p class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Demo applicant (preview)</p>
            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                Username <span class="font-mono font-semibold">{{ $demo['username'] }}</span>, password <span class="font-mono font-semibold">{{ $demo['password'] }}</span>.
                No sign-in code is asked, and nothing is saved.
            </p>
            <form method="POST" action="{{ route('portal.demo') }}" class="mt-3">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-slate-800">
                    <i data-lucide="user-round-check" class="h-4 w-4" aria-hidden="true"></i> Sign in as the demo applicant
                </button>
            </form>
        </div>
    @endif

    <p class="mt-8 text-sm text-slate-600 dark:text-slate-400">
        First time here?
        <a href="{{ route('portal.register') }}" class="font-bold text-emerald-600 underline underline-offset-2 dark:text-emerald-400">Create an account</a>
    </p>
@endsection

@section('body')
    @include('portal.auth._shell', ['heading' => 'Sign in', 'intro' => 'Ask for a Planning Recommendation, pay your bills and follow your request.'])
@endsection
