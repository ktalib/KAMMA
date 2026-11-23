@extends('portal.layouts.portal')

@section('title', 'My profile')

@section('content')
@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900';
    $field = 'block w-full rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $locked = 'block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200';
    $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
    $h2 = 'text-base font-semibold text-slate-900 dark:text-white';
    $hint = 'mt-1 text-sm text-slate-500 dark:text-slate-400';
    $button = 'inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500';
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My profile</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Your account details. Updates on your applications go to the phone number here.</p>
</div>

@if (! empty($applicant['is_demo']))
    <p class="mb-6 rounded-xl border border-dashed border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
        This is the demo applicant. You can try the forms; nothing is saved.
    </p>
@endif

<div class="grid max-w-4xl gap-6">
    <form method="POST" action="{{ route('portal.profile.update') }}" class="{{ $card }}" novalidate>
        @csrf
        @method('PUT')
        <h2 class="{{ $h2 }}">Your details</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="{{ $label }}">Full name</label>
                <input id="name" name="name" value="{{ old('name', $applicant['name']) }}" required maxlength="150" autocomplete="name" class="{{ $field }}">
                <x-input-error :messages="$errors->details->get('name')" class="mt-2" />
            </div>
            <div>
                <span class="{{ $label }}">Mobile number</span>
                <div class="{{ $locked }}">{{ $applicant['phone'] ?: '—' }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Changing it needs a code sent to the new number. Coming with the backend.</p>
            </div>
            <div>
                <label for="email" class="{{ $label }}">Email <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="email" type="email" name="email" value="{{ old('email', $applicant['email']) }}" maxlength="150" autocapitalize="none" class="{{ $field }}">
                <x-input-error :messages="$errors->details->get('email')" class="mt-2" />
            </div>
            <div>
                <span class="{{ $label }}">Username</span>
                <div class="{{ $locked }} font-mono">{{ $applicant['username'] }}</div>
            </div>
            <div>
                <span class="{{ $label }}">Signed in since</span>
                <div class="{{ $locked }}">{{ \Illuminate\Support\Carbon::parse($applicant['since'])->format('j M Y, g:i a') }}</div>
            </div>
        </div>
        <div class="mt-5 flex justify-end">
            <button type="submit" class="{{ $button }}"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i> Save details</button>
        </div>
    </form>

    <form method="POST" action="{{ route('portal.profile.password') }}" class="{{ $card }}" novalidate>
        @csrf
        @method('PUT')
        <h2 class="{{ $h2 }}">Change password</h2>
        <p class="{{ $hint }}">Use at least 8 characters, and not one you use elsewhere.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="current_password" class="{{ $label }}">Current password</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
                <x-input-error :messages="$errors->password->get('current_password')" class="mt-2" />
            </div>
            <div>
                <label for="password" class="{{ $label }}">New password</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                <x-input-error :messages="$errors->password->get('password')" class="mt-2" />
            </div>
            <div>
                <label for="password_confirmation" class="{{ $label }}">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
            </div>
        </div>
        <div class="mt-5 flex justify-end">
            <button type="submit" class="{{ $button }}"><i data-lucide="key-round" class="h-4 w-4" aria-hidden="true"></i> Change password</button>
        </div>
    </form>

    <div class="{{ $card }} flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="{{ $h2 }}">Sign out</h2>
            <p class="{{ $hint }}">Sign out on shared phones and computers.</p>
        </div>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i> Sign out
            </button>
        </form>
    </div>
</div>
@endsection
