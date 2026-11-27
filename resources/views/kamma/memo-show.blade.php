{{-- One KAMMA Memo (KLAES pr_memos print, KAMMA wording): memo heading, file and property (Plot No., District, LGA,
     Location), the KSI findings it rests on, the planner's recommendation and the MD KAMMA's recommendation. Prints on A4. --}}
@php
    $label = 'text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $value = 'mt-0.5 text-sm text-slate-800 dark:text-slate-100';
    $r = $m->report;
    $m2 = fn ($v) => $v === null ? '—' : number_format((float) $v, 2).' m²';
@endphp

<x-app-layout :title="'Memo · '.$m->memo_no">
    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="file-text" :title="'KAMMA Memo · '.$m->memo_no" :subtitle="$m->file_number.' · '.$m->applicant_name" :section="'KAMMA › '.$k['label']" tone="violet">
                <a href="{{ route('kamma.memos.index', [$kind, 'tab' => 'memos']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Back</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            </x-ui.page-head>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:border-0 print:p-0 print:shadow-none dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center gap-4 border-b border-slate-200 pb-5 dark:border-slate-700">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-16 object-contain">
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">KLAES-METRO · Kano State Metropolitan Area Management Agency</p>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">Memo · Planning Recommendation</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $k['label'] }}</p>
                </div>
                <div class="text-right">
                    <p class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $m->memo_no }}</p>
                    <span @class(['mt-1 inline-block rounded-full px-3 py-1 text-xs font-bold uppercase', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $m->status === 'approved', 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' => $m->status === 'declined', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $m->status === 'submitted'])>{{ $m->status === 'submitted' ? 'Awaiting MD KAMMA' : ucfirst($m->status).' by MD KAMMA' }}</span>
                </div>
            </header>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">File No.</p><p class="{{ $value }} font-mono font-semibold">{{ $m->file_number }}</p></div>
                <div class="sm:col-span-3"><p class="{{ $label }}">Applicant</p><p class="{{ $value }}">{{ $m->applicant_name ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Plot No.</p><p class="{{ $value }}">{{ $r?->plot_number ?: '—' }}</p></div>
                <div><p class="{{ $label }}">District</p><p class="{{ $value }}">{{ $r?->district ?: '—' }}</p></div>
                <div><p class="{{ $label }}">LGA</p><p class="{{ $value }}">{{ $r?->lga ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Location</p><p class="{{ $value }}">{{ $r?->location ?: '—' }}</p></div>
            </section>

            @if ($r)
                <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">KAMMA Site Inspection</h2>
                <section class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><p class="{{ $label }}">Inspected</p><p class="{{ $value }}">{{ $r->inspection_date?->format('j F Y') ?? '—' }}</p></div>
                    <div><p class="{{ $label }}">Applied land use</p><p class="{{ $value }}">{{ $r->applied_land_use ?: '—' }}</p></div>
                    <div><p class="{{ $label }}">Existing site area</p><p class="{{ $value }}">{{ $m2($r->existing_site_area) }}</p></div>
                    <div><p class="{{ $label }}">Recommended site area</p><p class="{{ $value }}">{{ $m2($r->recommended_site_area) }}</p></div>
                    <div><p class="{{ $label }}">Compliance</p><p class="{{ $value }}">{{ $r->compliance_status ?: '—' }}</p></div>
                    <div><p class="{{ $label }}">MD KAMMA (Planning Recommendation)</p><p class="{{ $value }}">{{ ucfirst((string) $r->md_decision) ?: '—' }} · {{ $r->md_decided_at?->format('j M Y') }}</p></div>
                </section>
            @endif

            <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">Planner's recommendation</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-800 dark:text-slate-100">{{ $m->planner_recommendation }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $m->created_by_name }} · {{ $m->planner_recommendation_date?->format('j F Y') }}</p>

            <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">MD KAMMA's recommendation</h2>
            @if ($m->status === 'submitted')
                <p class="mt-2 text-sm italic text-slate-500 dark:text-slate-400">Awaiting the MD KAMMA.</p>
            @else
                <p class="mt-2 whitespace-pre-line text-sm text-slate-800 dark:text-slate-100">{{ $m->md_recommendation ?: ucfirst($m->status).'.' }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($m->status) }} · {{ $m->md_recommendation_date?->format('j F Y') }}</p>
            @endif

            <div class="mt-16 grid grid-cols-2 gap-10 text-center text-xs text-slate-500 dark:text-slate-400">
                <div class="border-t border-slate-300 pt-2 dark:border-slate-600">Planning Officer</div>
                <div class="border-t border-slate-300 pt-2 dark:border-slate-600">MD KAMMA</div>
            </div>
        </article>
    </div>
</x-app-layout>
