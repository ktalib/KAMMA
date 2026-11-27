{{-- One KAMMA bill on A4 (KLAES PP bill print, KAMMA wording): Application Processing Fee or Betterment with the
     rate working and the 4× no-BPA penalty line. Property order: Plot No., District, LGA, Location. --}}
@php
    $naira = fn ($v) => '₦'.number_format((float) $v, 2);
    $label = 'text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $value = 'mt-0.5 text-sm text-slate-800 dark:text-slate-100';
    $p = $b->report ?? $b->file;
    $plot = $b->report?->plot_number ?? $b->file?->plot_number;
@endphp

<x-app-layout :title="'Bill · '.$b->bill_no">
    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="receipt" :title="'Bill · '.$b->bill_no" :subtitle="$b->file_number.' · '.($b->payer_name ?: '—')" section="KAMMA › Bills & Payments" tone="emerald">
                <a href="{{ route('kamma.bills.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Bills</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            </x-ui.page-head>
        </div>

        <article class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:max-w-none print:border-0 print:p-0 print:shadow-none dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center gap-4 border-b border-slate-200 pb-5 dark:border-slate-700">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-16 object-contain">
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">KLAES-METRO · Kano State Metropolitan Area Management Agency</p>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">{{ $b->bill_type === 'processing' ? 'Application Processing Fee Bill' : 'Betterment Bill' }}</h1>
                </div>
                <div class="text-right">
                    <p class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $b->bill_no }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $b->created_at?->format('j F Y') }}</p>
                    <span class="mt-1 inline-block rounded-full px-3 py-0.5 text-xs font-bold uppercase {{ $b->isPaid() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">{{ $b->isPaid() ? 'Paid' : 'Unpaid' }}</span>
                </div>
            </header>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">File No.</p><p class="{{ $value }} font-mono font-semibold">{{ $b->file_number }}</p></div>
                <div class="sm:col-span-3"><p class="{{ $label }}">Payer</p><p class="{{ $value }}">{{ $b->payer_name ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Plot No.</p><p class="{{ $value }}">{{ $plot ?: '—' }}</p></div>
                <div><p class="{{ $label }}">District</p><p class="{{ $value }}">{{ $p?->district ?: '—' }}</p></div>
                <div><p class="{{ $label }}">LGA</p><p class="{{ $value }}">{{ $p?->lga ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Location</p><p class="{{ $value }}">{{ $p?->location ?: '—' }}</p></div>
            </section>

            <table class="mt-8 w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @if ($b->bill_type === 'betterment')
                        <tr><td class="py-2 text-slate-600 dark:text-slate-300">Category</td><td class="py-2 text-right">{{ $b->category }}</td></tr>
                        <tr><td class="py-2 text-slate-600 dark:text-slate-300">Recommended site area</td><td class="py-2 text-right tabular-nums">{{ number_format((float) $b->area, 2) }} m²</td></tr>
                        <tr><td class="py-2 text-slate-600 dark:text-slate-300">Base fee (first {{ number_format((float) $b->base_area, 2) }} m²)</td><td class="py-2 text-right tabular-nums">{{ $naira($b->base_fee) }}</td></tr>
                        <tr><td class="py-2 text-slate-600 dark:text-slate-300">Rate per extra m²</td><td class="py-2 text-right tabular-nums">{{ $naira($b->rate_per_extra_sqm) }}</td></tr>
                    @endif
                    <tr><td class="py-2 text-slate-600 dark:text-slate-300">Standard fee</td><td class="py-2 text-right tabular-nums">{{ $naira($b->standard_amount) }}</td></tr>
                    @if ($b->penalty_multiplier > 1)
                        <tr><td class="py-2 text-rose-700 dark:text-rose-300">No-BPA penalty: building on site without Building Plan Approval (× {{ $b->penalty_multiplier }})</td><td class="py-2 text-right tabular-nums text-rose-700 dark:text-rose-300">{{ $naira((float) $b->amount - (float) $b->standard_amount) }}</td></tr>
                    @endif
                    <tr class="border-t-2 border-slate-300 dark:border-slate-600"><td class="py-3 font-bold text-slate-900 dark:text-white">Amount payable</td><td class="py-3 text-right text-lg font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $naira($b->amount) }}</td></tr>
                </tbody>
            </table>

            <p class="mt-10 text-xs text-slate-500 dark:text-slate-400">Generated by {{ $b->created_by_name ?: '—' }} · KLAES-METRO</p>
        </article>
    </div>
</x-app-layout>
