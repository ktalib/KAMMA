@extends('portal.layouts.portal')

@section('title', $a['ref'])

@section('content')
@php
    $card = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';
    $h2 = 'text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400';
    $dt = 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $dd = 'mt-0.5 break-words text-sm text-slate-900 dark:text-slate-100';
    $t = $a['timeline'];
    $bill = $a['bill'];
    $size = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format(max(1, $bytes / 1024)).' KB';
@endphp

<div class="mb-6">
    <a href="{{ route('portal.bpa.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i> All applications
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="break-all font-mono text-xl font-bold text-slate-900 sm:text-2xl dark:text-white">{{ $a['ref'] }}</h1>
                @include('portal.bpa.partials.status', ['label' => $a['status_label']])
            </div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $a['proposed_use'] }} building &middot; land file {{ $a['file_number'] }}</p>
        </div>
        @if ($a['bp_file_no'])
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-300">Your BP file number</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-emerald-700 dark:text-emerald-200">{{ $a['bp_file_no'] }}</p>
            </div>
        @endif
    </div>

    {{-- What happens now --}}
    @if ($t['returned'])
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
            <i data-lucide="undo-2" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
            <p><span class="font-bold">Returned for correction.</span> {{ $t['returned'] }} KAMMA will contact you if anything is needed from you.</p>
        </div>
    @elseif ($t['complete'])
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
            <i data-lucide="badge-check" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
            <p><span class="font-bold">Approved.</span> Your building plan is approved. Upload photos of the work at each stage as you build.</p>
        </div>
    @else
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
            <p><span class="font-bold">Now: {{ $t['current'] }}.</span> {{ $t['current_hint'] }}</p>
        </div>
    @endif

    <div class="mt-4 flex flex-wrap gap-2">
        @if ($a['bill_outstanding'])
            <a href="#bill" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="receipt" class="h-4 w-4" aria-hidden="true"></i> Pay the bill</a>
        @endif
        @if ($a['can_book_inspection'])
            <a href="{{ route('portal.bpa.inspections.index', ['application' => $a['ref']]) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="calendar-plus" class="h-4 w-4" aria-hidden="true"></i> {{ $a['inspection'] ? 'Change the inspection' : 'Book the site inspection' }}</a>
        @endif
        @if ($a['can_report_progress'])
            <a href="{{ route('portal.bpa.progress.index', ['application' => $a['ref']]) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="camera" class="h-4 w-4" aria-hidden="true"></i> Upload construction progress</a>
        @endif
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Progress --}}
    <div class="min-w-0 lg:col-span-1">
        <div class="{{ $card }} p-6 lg:sticky lg:top-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="{{ $h2 }}">Progress</h2>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $t['percent'] }}%</span>
            </div>
            @include('portal.bpa.partials.timeline', ['timeline' => $t])
        </div>
    </div>

    <div class="min-w-0 space-y-6 lg:col-span-2">
        {{-- Bill --}}
        @if ($bill)
            <div id="bill" class="{{ $card }} scroll-mt-20 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="{{ $h2 }}">Bill</h2>
                        <p class="mt-2 font-mono text-sm font-semibold text-slate-900 dark:text-white">{{ $bill['bill_no'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $bill['bill_type'] }} &middot; raised {{ $bill['raised_on']->format('j M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">₦{{ number_format($bill['amount'], 2) }}</p>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $bill['status'] === 'Paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' }}">{{ $bill['status'] }}</span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">₦{{ number_format($bill['amount'] / max(1, $a['number_of_plots']), 2) }} per plot &times; {{ $a['number_of_plots'] }} {{ $a['number_of_plots'] === 1 ? 'plot' : 'plots' }}, non-refundable.</p>

                @if ($bill['status'] === 'Paid')
                    <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                        Paid{{ $bill['receipt_no'] ? ', receipt '.$bill['receipt_no'] : '' }}{{ $bill['paid_on'] ? ', on '.$bill['paid_on']->format('j M Y') : '' }}. Thank you.
                    </p>
                @else
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">How to pay</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-700 dark:text-slate-300">
                            <li>Pay exactly <span class="font-semibold">₦{{ number_format($bill['amount'], 2) }}</span>. Part payments are not taken.</li>
                            <li>Quote the bill number <span class="font-mono font-semibold">{{ $bill['bill_no'] }}</span> as the payment reference.</li>
                            <li>Pay through an official channel: {{ implode(', ', array_slice(\App\Services\Bpa\RecordPayment::CHANNELS, 0, -1)) }}.</li>
                            <li>Keep the receipt. KAMMA confirms the payment against the receipt number, then opens your BP file.</li>
                        </ol>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">The account details and online payment will be shown here when KAMMA confirms them. Never pay into a private account.</p>
                        <button type="button" onclick="window.print()" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            <i data-lucide="printer" class="h-3.5 w-3.5" aria-hidden="true"></i> Print this page
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Site inspection --}}
        @if ($a['inspection'])
            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}">Site inspection</h2>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="flex h-12 w-12 flex-col items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <span class="text-[10px] font-bold uppercase">{{ $a['inspection']['date']->format('M') }}</span>
                        <span class="text-lg font-bold leading-none">{{ $a['inspection']['date']->format('j') }}</span>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $a['inspection']['date']->format('l j F Y') }} at {{ $a['inspection']['time'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $a['inspection']['status'] }}: the Zonal Office confirms the visit and the officer.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Construction progress --}}
        @if ($a['can_report_progress'] || ! empty($a['progress']))
            <div class="{{ $card }} p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="{{ $h2 }}">Construction progress</h2>
                    <a href="{{ route('portal.bpa.progress.index', ['application' => $a['ref']]) }}" class="text-sm font-semibold text-emerald-600 hover:underline dark:text-emerald-400">Upload</a>
                </div>
                @forelse ($a['progress'] as $p)
                    <div class="mt-3 flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <i data-lucide="images" class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $p['stage'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $p['photos'] }} photos &middot; {{ $p['at']->format('j M Y, g:i a') }} &middot; {{ number_format($p['lat'], 4) }}, {{ number_format($p['lng'], 4) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">No photos yet. Upload photos at each stage of the work.</p>
                @endforelse
            </div>
        @endif

        {{-- Documents --}}
        <div class="{{ $card }} p-6">
            <h2 class="{{ $h2 }}">Documents</h2>
            @if (! empty($a['office_documents']))
                <p class="mt-4 text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">From KAMMA</p>
                <ul class="mt-2 space-y-2">
                    @foreach ($a['office_documents'] as $doc)
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                            <span class="flex min-w-0 items-center gap-3">
                                <i data-lucide="file-check-2" class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" aria-hidden="true"></i>
                                <span class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $doc['name'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $doc['at']->format('j M Y') }} &middot; download soon</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Your building plan</p>
            @forelse ($a['documents'] as $doc)
                <div class="mt-2 flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 dark:border-slate-800">
                    <i data-lucide="{{ $doc['kind'] === 'CAD drawing' ? 'drafting-compass' : 'paperclip' }}" class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-900 dark:text-white">{{ $doc['name'] }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $doc['kind'] }} &middot; {{ $size($doc['size']) }} &middot; {{ $doc['at']->format('j M Y') }}</span>
                    </span>
                </div>
            @empty
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">No files recorded.</p>
            @endforelse
            @if (! empty($a['documents_submitted']) || ! empty($a['drawings_submitted']))
                <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 dark:border-slate-800">
                    <div><dt class="{{ $dt }}">Documents submitted</dt><dd class="{{ $dd }}">{{ implode(', ', $a['documents_submitted']) ?: 'None ticked' }}</dd></div>
                    <div><dt class="{{ $dt }}">Drawings / reports submitted</dt><dd class="{{ $dd }}">{{ implode(', ', $a['drawings_submitted']) ?: 'None ticked' }}</dd></div>
                </dl>
            @endif
        </div>

        {{-- What you submitted: property order Plot No., District, LGA, Location (KLAES rule) --}}
        <div class="{{ $card }} p-6">
            <h2 class="{{ $h2 }}">What you submitted</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Plot No.</dt><dd class="{{ $dd }}">{{ $a['plot_no'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">District</dt><dd class="{{ $dd }}">{{ $a['district'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">LGA</dt><dd class="{{ $dd }}">{{ $a['lga'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Location</dt><dd class="{{ $dd }}">{{ $a['location'] ?: collect([$a['district'], $a['lga']])->filter()->implode(', ') ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Land file</dt><dd class="{{ $dd }}">{{ $a['file_number'] }}</dd></div>
                <div><dt class="{{ $dt }}">Developer</dt><dd class="{{ $dd }}">{{ $a['developer_name'] }}</dd></div>
                <div><dt class="{{ $dt }}">Land use (land file)</dt><dd class="{{ $dd }}">{{ $a['file_land_use'] ?: 'Not recorded' }}</dd></div>
                <div><dt class="{{ $dt }}">Proposed use</dt><dd class="{{ $dd }}">{{ $a['proposed_use'] }}</dd></div>
                <div class="sm:col-span-2"><dt class="{{ $dt }}">Land-use check</dt>
                    <dd class="{{ $dd }} {{ $a['land_use_check'] === 'Mismatch' ? '!text-rose-600 dark:!text-rose-400' : '' }}">
                        {{ $a['land_use_check'] === 'Mismatch' ? 'Flagged for review' : $a['land_use_check'] }}{{ $a['land_use_note'] ? ': '.$a['land_use_note'] : '' }}
                    </dd>
                </div>
                <div><dt class="{{ $dt }}">Type of submission</dt><dd class="{{ $dd }}">{{ $a['submission_type'] }}</dd></div>
                <div><dt class="{{ $dt }}">Number of plots</dt><dd class="{{ $dd }}">{{ $a['number_of_plots'] }}</dd></div>
            </dl>
            <p class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">Submitted {{ $a['submitted_at']->format('j M Y, g:i a') }}</p>
        </div>

        {{-- What has happened --}}
        <div class="{{ $card }}">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800"><h2 class="{{ $h2 }}">What has happened</h2></div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach (array_reverse($a['events']) as $e)
                    <li class="flex gap-4 px-6 py-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ ($e['action'] ?? '') === 'not_recommended' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' }}">
                            <i data-lucide="{{ ($e['action'] ?? '') === 'not_recommended' ? 'undo-2' : 'check' }}" class="h-4 w-4" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $e['title'] }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $e['at']->format('j M Y, g:i a') }}</p>
                            </div>
                            @if ($e['body'])
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $e['body'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
