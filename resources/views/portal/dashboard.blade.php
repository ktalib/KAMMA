@extends('portal.layouts.portal')

@section('title', 'My requests')

@section('content')
@php
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300',
    ];
    $card = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';
    $feeSetting = config('kamma.bills.processing_fee');
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Welcome, {{ $applicant['name'] }}</p>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My requests</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Every Planning Recommendation request you have made with KAMMA, and where each one has reached.</p>
    </div>
    <a href="{{ route('portal.applications.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
        <i data-lucide="file-plus-2" class="h-4 w-4" aria-hidden="true"></i> New request
    </a>
</div>

{{-- Status cards --}}
<div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    @foreach ($cards as $c)
        <div class="{{ $card }} p-4 sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $c['label'] }}</p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tones[$c['tone']] }}">
                    <i data-lucide="{{ $c['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white" data-figure="{{ $c['label'] }}">{{ $c['value'] }}</p>
            @if (! empty($c['sub']))
                <p class="mt-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">{{ $c['sub'] }}</p>
            @endif
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="min-w-0 space-y-4 lg:col-span-2">
        @if ($applications->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                <i data-lucide="folder-open" class="mx-auto mb-4 h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                <p class="text-base font-semibold text-slate-900 dark:text-white">You have not made a request yet</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">
                    Start your first request for a Planning Recommendation. You need the file number of your land file in KLAES and your title documents (PDF or photos).
                </p>
                <a href="{{ route('portal.applications.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                    <i data-lucide="file-plus-2" class="h-4 w-4" aria-hidden="true"></i> Start a request
                </a>
            </div>
        @else
            @foreach ($applications as $a)
                <a href="{{ route('portal.applications.show', $a['ref']) }}" class="{{ $card }} block p-5 transition hover:border-emerald-400 hover:shadow-md dark:hover:border-emerald-500">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $a['ref'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $a['type'] }} &middot; {{ $a['location_line'] ?: 'Location not recorded' }}</p>
                        </div>
                        @include('portal.partials.status', ['label' => $a['status_label']])
                    </div>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <i data-lucide="folder" class="h-3 w-3" aria-hidden="true"></i> {{ $a['file_number'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <i data-lucide="tag" class="h-3 w-3" aria-hidden="true"></i> {{ \App\Support\Portal\PortalForm::CATEGORIES[$a['category']] }}
                        </span>
                    </div>
                    @include('portal.partials.timeline', ['timeline' => $a['timeline'], 'compact' => true])
                    <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">Submitted {{ $a['submitted_at']->format('j M Y') }}</p>
                </a>
            @endforeach
        @endif
    </div>

    <div class="min-w-0 space-y-6">
        <div class="{{ $card }} p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">To do</h2>
            @if ($todo->isEmpty())
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Nothing waiting on you. We will tell you when your request needs something.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($todo as $t)
                        <li>
                            <a href="{{ $t['url'] }}" class="flex items-start gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-slate-800 dark:text-slate-300 dark:hover:border-emerald-500 dark:hover:bg-emerald-500/10">
                                <i data-lucide="{{ $t['icon'] }}" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                                <span class="min-w-0 break-all">{{ $t['text'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="{{ $card }} p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">Track a request</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Open one of your requests by its number.</p>
            <form method="GET" action="{{ route('portal.applications.track') }}" class="mt-3 flex gap-2">
                <label for="track-number" class="sr-only">Request number</label>
                <input id="track-number" name="number" value="{{ old('number') }}" required placeholder="e.g. KPR/2026/00001"
                       class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500">
                <button class="rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Open</button>
            </form>
            <x-input-error :messages="$errors->get('number')" class="mt-2" />
        </div>

        <div class="{{ $card }} p-5">
            <h2 class="text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">How it works</h2>
            <ol class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li><span class="font-semibold text-slate-800 dark:text-slate-200">1.</span> Make your request on your land file and upload your documents.</li>
                <li><span class="font-semibold text-slate-800 dark:text-slate-200">2.</span> KAMMA opens a Shadow File and raises the processing fee{{ $feeSetting !== null ? ' (₦'.number_format((int) $feeSetting, 2).')' : '' }}. Pay it.</li>
                <li><span class="font-semibold text-slate-800 dark:text-slate-200">3.</span> An officer visits the site; the Zonal Office does the KSI.</li>
                <li><span class="font-semibold text-slate-800 dark:text-slate-200">4.</span> Pay the betterment bill raised from the KSI.</li>
                <li><span class="font-semibold text-slate-800 dark:text-slate-200">5.</span> The Planning Recommendation goes to the MD KAMMA, then your file returns to the Ministry.</li>
            </ol>
        </div>
    </div>
</div>
@endsection
