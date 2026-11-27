{{-- e-Receipt for a paid KAMMA bill (KLAES PP / REV-M e-Receipt, KAMMA wording). Prints on A4. --}}
@php
    $naira = fn ($v) => '₦'.number_format((float) $v, 2);
    $label = 'text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $value = 'mt-0.5 text-sm text-slate-800 dark:text-slate-100';
    $p = $b->report ?? $b->file;
    $plot = $b->report?->plot_number ?? $b->file?->plot_number;
@endphp

<x-app-layout :title="'e-Receipt · '.$b->receipt_no">
    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="file-check-2" :title="'e-Receipt · '.$b->receipt_no" :subtitle="$b->bill_no.' · '.$b->file_number" section="KAMMA › Bills & Payments" tone="emerald">
                <a href="{{ route('kamma.payments.index', ['tab' => 'paid']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Payments</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            </x-ui.page-head>
        </div>

        <article class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:max-w-none print:border-0 print:p-0 print:shadow-none dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center gap-4 border-b border-slate-200 pb-5 dark:border-slate-700">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-16 object-contain">
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">KLAES-METRO · Kano State Metropolitan Area Management Agency</p>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">e-Receipt</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $b->bill_type === 'processing' ? 'Application Processing Fee' : 'Betterment' }}</p>
                </div>
                <div class="text-right">
                    <p class="{{ $label }}">Receipt No.</p>
                    <p class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $b->receipt_no }}</p>
                </div>
            </header>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">File No.</p><p class="{{ $value }} font-mono font-semibold">{{ $b->file_number }}</p></div>
                <div class="sm:col-span-3"><p class="{{ $label }}">Received from</p><p class="{{ $value }}">{{ $b->payer_name ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Plot No.</p><p class="{{ $value }}">{{ $plot ?: '—' }}</p></div>
                <div><p class="{{ $label }}">District</p><p class="{{ $value }}">{{ $p?->district ?: '—' }}</p></div>
                <div><p class="{{ $label }}">LGA</p><p class="{{ $value }}">{{ $p?->lga ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Location</p><p class="{{ $value }}">{{ $p?->location ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Bill No.</p><p class="{{ $value }} font-mono">{{ $b->bill_no }}</p></div>
                <div><p class="{{ $label }}">Paid on</p><p class="{{ $value }}">{{ $b->paid_on?->format('j F Y') }}</p></div>
                <div><p class="{{ $label }}">Channel</p><p class="{{ $value }}">{{ $b->channel === 'Other' ? 'Other: '.$b->channel_other : $b->channel }}</p></div>
                <div><p class="{{ $label }}">Bill amount</p><p class="{{ $value }}">{{ $naira($b->amount) }}@if ($b->penalty_multiplier > 1) <span class="text-xs text-rose-600 dark:text-rose-300">(incl. × {{ $b->penalty_multiplier }} no-BPA penalty)</span>@endif</p></div>
            </section>

            <div class="mt-8 flex items-center justify-between rounded-xl bg-emerald-50 px-6 py-4 dark:bg-emerald-500/10">
                <span class="text-sm font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Amount received</span>
                <span class="text-2xl font-extrabold tabular-nums text-emerald-800 dark:text-emerald-200">{{ $naira($b->amount_paid) }}</span>
            </div>

            <p class="mt-10 text-xs text-slate-500 dark:text-slate-400">Issued {{ now()->format('j F Y, H:i') }} · KLAES-METRO</p>
        </article>
    </div>
</x-app-layout>
