@extends('portal.layouts.app')

@section('title', 'Create an account')

@php
    $input = 'mt-2 block w-full rounded-lg border-slate-300 px-3.5 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'block text-sm font-bold text-slate-800 dark:text-slate-200';
    $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
@endphp

@section('form')
    <form method="POST" action="{{ route('portal.register.submit') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <div>
            <label for="name" class="{{ $label }}">Full name <span class="text-rose-500">*</span></label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="150" autocomplete="name" class="{{ $input }}">
            <p class="{{ $hint }}">As it appears on your title documents.</p>
        </div>
        <div>
            <label for="phone" class="{{ $label }}">Mobile number <span class="text-rose-500">*</span></label>
            <input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="08031234567" class="{{ $input }}">
            <p class="{{ $hint }}">We send a code to this number to confirm it, and every update on your application.</p>
        </div>
        <div>
            <label for="email" class="{{ $label }}">Email <span class="font-normal text-slate-500 dark:text-slate-400">(optional)</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" autocapitalize="none" class="{{ $input }}">
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="{{ $label }}">Password <span class="text-rose-500">*</span></label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $input }}">
                <p class="{{ $hint }}">At least 8 characters.</p>
            </div>
            <div>
                <label for="password_confirmation" class="{{ $label }}">Confirm password <span class="text-rose-500">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}">
            </div>
        </div>
        <label class="flex items-start gap-3">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800">
            <span class="text-sm text-slate-600 dark:text-slate-400">These details are mine and correct. I understand KAMMA will contact me on this number about my applications.</span>
        </label>
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            <i data-lucide="user-plus" class="h-5 w-5" aria-hidden="true"></i> Create account
        </button>
    </form>

    <p class="mt-8 text-sm text-slate-600 dark:text-slate-400">
        Already have an account?
        <a href="{{ route('portal.login') }}" class="font-bold text-emerald-600 underline underline-offset-2 dark:text-emerald-400">Sign in</a>
    </p>
@endsection

@section('body')
    @include('portal.auth._shell', ['heading' => 'Create an account', 'intro' => 'One account for all your Planning Recommendation requests with KAMMA.'])
@endsection
