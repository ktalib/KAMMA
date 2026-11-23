@extends('portal.layouts.app')

@section('title', 'Confirm your number')

@section('form')
    <div class="mt-6 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
        <span class="font-bold">Preview:</span> no SMS is sent yet. Your code is
        <span class="font-mono text-base font-bold tracking-widest">{{ $pending['code'] }}</span>.
    </div>

    <form method="POST" action="{{ route('portal.verify.submit') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="code" class="block text-sm font-bold text-slate-800 dark:text-slate-200">6-digit code</label>
            <input id="code" name="code" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}"
                   class="mt-2 block w-full rounded-lg border-slate-300 px-3.5 py-3 text-center font-mono text-2xl tracking-[0.5em] shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The code expires {{ \Illuminate\Support\Carbon::parse($pending['expires_at'])->format('g:i a') }}.</p>
        </div>
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            <i data-lucide="shield-check" class="h-5 w-5" aria-hidden="true"></i> Confirm and continue
        </button>
    </form>

    <p class="mt-8 text-sm text-slate-600 dark:text-slate-400">
        Wrong number?
        <a href="{{ route('portal.register') }}" class="font-bold text-emerald-600 underline underline-offset-2 dark:text-emerald-400">Go back and change it</a>
    </p>
@endsection

@section('body')
    @include('portal.auth._shell', ['heading' => 'Confirm your number', 'intro' => 'Enter the code sent to '.$pending['phone'].'.'])
@endsection
