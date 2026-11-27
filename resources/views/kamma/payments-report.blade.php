{{-- KAMMA > Bills & Payments > Payment Reports (KLAES programmes.payments?url=report design): period (default this year),
     figure cards, monthly collections chart with its table, breakdown by bill type / application / channel, the payments
     list (S/N), CSV export and landscape print. Figures come from kamma_bills. --}}
@php
    $naira = fn ($v) => '₦'.number_format((float) $v, 2);
    $th = 'whitespace-nowrap px-4 py-3';
    $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $period = ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];
    $typeLabel = fn ($t) => $t === 'processing' ? 'Processing fee' : 'Betterment';
    $sourceLabel = fn ($s) => match ($s) { 'conversion' => 'Conversion', 'st' => 'Sectional Titling', 'sltr' => 'SLTR', 'master_ksi' => 'Master KSI (Post CofO)', default => ucfirst((string) $s) };
    $chart = ['type' => 'bar', 'money' => true, 'labels' => array_column($months, 'label'), 'series' => [['label' => 'Collected', 'data' => array_column($months, 'amount')]]];
@endphp

<x-app-layout title="Payment Reports">
    <style>@media print { @page { size: A4 landscape; } }</style>
    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="chart-no-axes-combined" title="Payment Reports" section="KAMMA › Bills & Payments" tone="emerald"
                            :subtitle="'Payments recorded '.$from->format('j M Y').' – '.$to->format('j M Y').'.'">
                <a href="{{ route('kamma.payments.report.csv', $period) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            </x-ui.page-head>
        </div>
        <h1 class="hidden text-lg font-bold print:block">KLAES-METRO · KAMMA Payment Report · {{ $from->format('j M Y') }} – {{ $to->format('j M Y') }}</h1>

        <form method="GET" class="flex flex-wrap items-end gap-3 print:hidden">
            <div><label for="date_from" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">From</label><input id="date_from" type="date" name="date_from" value="{{ $period['date_from'] }}" class="{{ $field }}"></div>
            <div><label for="date_to" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">To</label><input id="date_to" type="date" name="date_to" value="{{ $period['date_to'] }}" class="{{ $field }}"></div>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Apply</button>
            <a href="{{ route('kamma.payments.report') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">This year</a>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Collected" :value="$naira($totals['collected'])" :hint="number_format($totals['payments']).' payment(s) in the period'" icon="banknote" tone="emerald" />
            <x-ui.stat title="Billed" :value="$naira($totals['billed'])" :hint="number_format($totals['bills']).' bill(s) generated in the period'" icon="receipt" tone="blue" />
            <x-ui.stat title="Outstanding" :value="$naira($totals['outstanding'])" hint="All unpaid bills, any date" icon="hourglass" tone="amber" />
            <x-ui.stat title="No-BPA penalty collected" :value="$naira($totals['penalty'])" hint="The × 4 share above the standard fee" icon="siren" tone="rose" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Collections by month" class="xl:col-span-2">
                <x-ui.chart :chart="$chart" label="Collections by month" />
                <table class="mt-4 w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th class="py-2">Month</th><th class="py-2 text-right">Payments</th><th class="py-2 text-right">Collected</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($months as $m)
                            <tr><td class="py-1.5">{{ $m['label'] }}</td><td class="py-1.5 text-right tabular-nums">{{ number_format($m['count']) }}</td><td class="py-1.5 text-right tabular-nums">{{ $naira($m['amount']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.panel>
            <div class="space-y-6">
                @foreach ([['By bill type', $byType, $typeLabel], ['By application', $bySource, $sourceLabel], ['By channel', $byChannel, fn ($c) => $c]] as [$heading, $group, $fmt])
                    <x-ui.panel :title="$heading">
                        @if ($group->isEmpty())
                            <p class="text-sm text-slate-500 dark:text-slate-400">No payments in the period.</p>
                        @else
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($group as $key => $g)
                                        <tr><td class="py-1.5">{{ $fmt($key) }}</td><td class="py-1.5 text-right tabular-nums text-slate-500">{{ $g['count'] }}</td><td class="py-1.5 text-right font-semibold tabular-nums">{{ $naira($g['amount']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </x-ui.panel>
                @endforeach
            </div>
        </div>

        <x-ui.panel title="Payments in the period" :subtitle="number_format($rows->count()).' payment(s)'" :padded="false">
            @if ($rows->isEmpty())
                <x-ui.empty icon="banknote" title="No payments in this period" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Paid on</th><th class="{{ $th }}">Receipt No.</th><th class="{{ $th }}">Bill No.</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">Payer</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">Application</th><th class="{{ $th }}">Channel</th><th class="{{ $th }} text-right">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $b)
                                <tr>
                                    <td class="px-4 py-2 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $b->paid_on?->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 font-mono text-xs">{{ $b->receipt_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 font-mono text-xs">{{ $b->bill_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $b->file_number }}</td>
                                    <td class="px-4 py-2">{{ $b->payer_name ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $typeLabel($b->bill_type) }}</td>
                                    <td class="whitespace-nowrap px-4 py-2">{{ $sourceLabel($b->source) }}</td>
                                    <td class="px-4 py-2">{{ $channelOf($b) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold tabular-nums">{{ $naira($b->amount_paid) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
