@extends('portal.layouts.portal')

@section('title', $a['ref'])

@section('content')
@php
    $card = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';
    $h2 = 'text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400';
    $dt = 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $dd = 'mt-0.5 break-words text-sm text-slate-900 dark:text-slate-100';
    $t = $a['timeline'];
    $size = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format(max(1, $bytes / 1024)).' KB';
    $yesNo = fn ($v) => $v === null ? '—' : ($v ? 'Yes' : 'No');
@endphp

<div class="mb-6">
    <a href="{{ route('portal.dashboard') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i> All requests
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="break-all font-mono text-xl font-bold text-slate-900 sm:text-2xl dark:text-white">{{ $a['ref'] }}</h1>
                @include('portal.partials.status', ['label' => $a['status_label']])
            </div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $a['type'] }} &middot; {{ \App\Support\Portal\PortalForm::CATEGORIES[$a['category']] }} &middot; land file {{ $a['file_number'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-300">Shadow File</p>
            <p class="mt-0.5 text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ ! empty($a['shadow_at']) ? 'Opened '.$a['shadow_at']->format('j M Y') : 'Not opened yet' }}</p>
        </div>
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
            <p><span class="font-bold">Done.</span> The Planning Recommendation is issued and your land file has gone back to the Ministry of Land for the next stage.</p>
        </div>
    @else
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
            <p><span class="font-bold">Now: {{ $t['current'] }}.</span> {{ $t['current_hint'] }}</p>
        </div>
    @endif

    @if ($a['outstanding'] !== [])
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="#bills" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="receipt" class="h-4 w-4" aria-hidden="true"></i> Pay the bill</a>
        </div>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Progress --}}
    <div class="min-w-0 lg:col-span-1">
        <div class="{{ $card }} p-6 lg:sticky lg:top-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="{{ $h2 }}">Progress</h2>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $t['percent'] }}%</span>
            </div>
            @include('portal.partials.timeline', ['timeline' => $t])
        </div>
    </div>

    <div class="min-w-0 space-y-6 lg:col-span-2">
        {{-- Bills: processing fee, then betterment (with the 4x no-BPA penalty when it applies) --}}
        <div id="bills" class="{{ $card }} scroll-mt-20 p-6">
            <h2 class="{{ $h2 }}">Bills</h2>
            @forelse ($a['bills'] as $bill)
                <div class="mt-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800" data-bill="{{ $bill['kind'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $bill['bill_type'] }}</p>
                            <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $bill['bill_no'] }} &middot; raised {{ $bill['raised_on']->format('j M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-slate-900 dark:text-white">₦{{ number_format($bill['amount'], 2) }}</p>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $bill['status'] === 'Paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' }}">{{ $bill['status'] }}</span>
                        </div>
                    </div>

                    @if ($bill['kind'] === 'betterment' && $bill['standard'] !== null)
                        <dl class="mt-3 grid gap-2 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-3 dark:bg-slate-800/60">
                            <div><dt class="{{ $dt }}">Standard betterment</dt><dd class="{{ $dd }}">₦{{ number_format($bill['standard'], 2) }}</dd></div>
                            <div><dt class="{{ $dt }}">Multiplier</dt><dd class="{{ $dd }} {{ $bill['multiplier'] > 1 ? '!text-rose-600 dark:!text-rose-400 font-bold' : '' }}">×{{ $bill['multiplier'] }}</dd></div>
                            <div><dt class="{{ $dt }}">Betterment fee</dt><dd class="{{ $dd }} font-semibold">₦{{ number_format($bill['amount'], 2) }}</dd></div>
                        </dl>
                        @if ($bill['penalty_reason'])
                            <p class="mt-3 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" data-penalty>
                                <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
                                <span><span class="font-bold">No-BPA penalty.</span> {{ $bill['penalty_reason'] }}</span>
                            </p>
                        @endif
                    @endif

                    @if ($bill['status'] === 'Paid')
                        <p class="mt-3 rounded-lg bg-emerald-50 px-4 py-2.5 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                            Paid{{ $bill['receipt_no'] ? ', receipt '.$bill['receipt_no'] : '' }}{{ $bill['paid_on'] ? ', on '.$bill['paid_on']->format('j M Y') : '' }}. Thank you.
                        </p>
                    @else
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">How to pay</p>
                            <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-700 dark:text-slate-300">
                                <li>Pay exactly <span class="font-semibold">₦{{ number_format($bill['amount'], 2) }}</span>. Part payments are not taken.</li>
                                <li>Quote the bill number <span class="font-mono font-semibold">{{ $bill['bill_no'] }}</span> as the payment reference.</li>
                                <li>Pay through an official channel: bank, POS, Interswitch or USSD.</li>
                                <li>Keep the receipt. KAMMA confirms the payment against the receipt number, then moves your request on.</li>
                            </ol>
                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">The account details and online payment will be shown here when KAMMA confirms them. Never pay into a private account.</p>
                            <button type="button" onclick="window.print()" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i data-lucide="printer" class="h-3.5 w-3.5" aria-hidden="true"></i> Print this page
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">No bill yet. The processing fee bill is raised when KAMMA opens the Shadow File.</p>
            @endforelse
        </div>

        {{-- Site visit and KSI --}}
        @if ($a['site_visit'] || $a['ksi'])
            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}">Site visit and KSI</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @if ($a['site_visit'])
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white"><i data-lucide="camera" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i> Site visit</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $a['site_visit']['date']->format('j M Y, g:i a') }}</p>
                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $a['site_visit']['photos'] }} geocoded photos at {{ number_format($a['site_visit']['lat'], 4) }}, {{ number_format($a['site_visit']['lng'], 4) }}</p>
                        </div>
                    @endif
                    @if ($a['ksi'])
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white"><i data-lucide="clipboard-check" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i> KAMMA Site Inspection (KSI)</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $a['ksi']['date']->format('j M Y') }}</p>
                            <dl class="mt-2 grid grid-cols-2 gap-2">
                                <div><dt class="{{ $dt }}">Recommended area</dt><dd class="{{ $dd }}">{{ number_format($a['ksi']['recommended_area']) }} m²</dd></div>
                                <div><dt class="{{ $dt }}">Building on the land</dt><dd class="{{ $dd }}">{{ $yesNo($a['ksi']['building_exists']) }}</dd></div>
                                @if ($a['ksi']['building_exists'])
                                    <div><dt class="{{ $dt }}">Building Plan Approval</dt><dd class="{{ $dd }}">{{ $yesNo($a['ksi']['has_bpa']) }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>
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
            <p class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">What you uploaded</p>
            @forelse ($a['documents'] as $doc)
                <div class="mt-2 flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-2.5 dark:border-slate-800">
                    <i data-lucide="paperclip" class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-900 dark:text-white">{{ $doc['name'] }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $doc['kind'] }} &middot; {{ $size($doc['size']) }} &middot; {{ $doc['at']->format('j M Y') }}</span>
                    </span>
                </div>
            @empty
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">No files recorded.</p>
            @endforelse
        </div>

        {{-- What you asked for: property order Plot No., District, LGA, Location (KLAES rule) --}}
        <div class="{{ $card }} p-6">
            <h2 class="{{ $h2 }}">What you asked for</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Plot No.</dt><dd class="{{ $dd }}">{{ $a['plot_no'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">District</dt><dd class="{{ $dd }}">{{ $a['district'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">LGA</dt><dd class="{{ $dd }}">{{ $a['lga'] ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Location</dt><dd class="{{ $dd }}">{{ $a['location'] ?: collect([$a['district'], $a['lga']])->filter()->implode(', ') ?: '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Land file</dt><dd class="{{ $dd }}">{{ $a['file_number'] }}</dd></div>
                <div><dt class="{{ $dt }}">Applicant</dt><dd class="{{ $dd }}">{{ $a['applicant_name'] }}</dd></div>
                <div><dt class="{{ $dt }}">Type of request</dt><dd class="{{ $dd }}">{{ $a['type'] }}</dd></div>
                <div><dt class="{{ $dt }}">Land use (land file)</dt><dd class="{{ $dd }}">{{ $a['file_land_use'] ?: 'Not recorded' }}</dd></div>
                <div class="sm:col-span-2"><dt class="{{ $dt }}">Proposal</dt><dd class="{{ $dd }}">{{ $a['proposal'] }}</dd></div>
                @foreach ($a['details'] as $label => $value)
                    <div><dt class="{{ $dt }}">{{ $label }}</dt><dd class="{{ $dd }}">{{ $value }}</dd></div>
                @endforeach
                @if ($a['merge_files'] !== [])
                    <div><dt class="{{ $dt }}">Files to merge with</dt><dd class="{{ $dd }}">{{ implode(', ', $a['merge_files']) }}</dd></div>
                @endif
                <div><dt class="{{ $dt }}">Building on the land</dt><dd class="{{ $dd }}">{{ $yesNo($a['building_exists']) }}</dd></div>
                @if ($a['building_exists'])
                    <div><dt class="{{ $dt }}">Building Plan Approval</dt><dd class="{{ $dd }}">{{ $yesNo($a['has_bpa']) }}</dd></div>
                @endif
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
