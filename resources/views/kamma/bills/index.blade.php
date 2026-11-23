{{-- KAMMA > Bills & Payments > Bills (KLAES PP Bills & Payments: figure cards, type / status filters, bills table with
     S/N and a three-dots menu). Also REV-M > Betterment Bill and Initial Bill (?from=revm&type=…). --}}
<x-app-layout title="Bills">
    @php
        $naira = fn ($v) => '₦'.number_format((float) $v, 2);
        $th = 'whitespace-nowrap px-4 py-3';
        $title = match ($type) { 'betterment' => 'Betterment Bills', 'processing' => 'Application Processing Fee Bills (Initial Bill)', default => 'Bills' };
        $chip = fn ($active) => $active ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700';
        $keep = array_filter(['from' => $fromRevm ? 'revm' : null, 'q' => $q]);
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="receipt" :title="$title" :section="$fromRevm ? 'KLAES REV-M › Automated Billing' : 'KAMMA › Bills & Payments'"
                        subtitle="Application Processing Fee and Betterment bills. Betterment is charged on the KSI's recommended site measurement; a building without Building Plan Approval pays the standard fee × 4.">
            <a href="{{ route('kamma.bills.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"><i data-lucide="calculator" class="h-4 w-4"></i> Generate Bill</a>
            <a href="{{ route('kamma.payments.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="banknote" class="h-4 w-4"></i> Payments</a>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Total billed" :value="$naira($totals['billed'])" :hint="number_format($totals['count']).' bill(s)'" icon="receipt" tone="blue" />
            <x-ui.stat title="Paid" :value="$naira($totals['paid'])" hint="Payments recorded" icon="check-circle-2" tone="emerald" />
            <x-ui.stat title="Outstanding" :value="$naira($totals['outstanding'])" hint="Bills not yet paid" icon="hourglass" tone="amber" />
            <x-ui.stat title="No-BPA penalty" :value="number_format($totals['penalised'])" hint="Betterment bills charged × 4" icon="siren" tone="rose" />
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach ([null => 'All bills', 'processing' => 'Processing fee', 'betterment' => 'Betterment'] as $k => $v)
                <a href="{{ route('kamma.bills.index', array_filter($keep + ['type' => $k ?: null, 'status' => $status])) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $chip($type === ($k ?: null)) }}">{{ $v }}</a>
            @endforeach
            <span class="mx-2 h-5 w-px bg-slate-200 dark:bg-slate-700"></span>
            @foreach ([null => 'Any status', 'unpaid' => 'Unpaid', 'paid' => 'Paid'] as $k => $v)
                <a href="{{ route('kamma.bills.index', array_filter($keep + ['type' => $type, 'status' => $k ?: null])) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $chip($status === ($k ?: null)) }}">{{ $v }}</a>
            @endforeach
        </div>

        <x-ui.panel :title="$title" :subtitle="$bills->total() ? 'Showing '.$bills->firstItem().'–'.$bills->lastItem().' of '.$bills->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="relative">
                    @foreach (array_filter(['from' => $fromRevm ? 'revm' : null, 'type' => $type, 'status' => $status]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Bill no., file no., payer, receipt…" class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </x-slot:actions>
            @if ($bills->isEmpty())
                <x-ui.empty icon="receipt" :title="$q !== '' || $status ? 'No bill matches' : 'No bills yet'">Generate the processing fee on a shadow file, or the betterment bill from a KSI report.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Bill No.</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">Payer</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">Category</th><th class="{{ $th }} text-right">Area (m²)</th><th class="{{ $th }} text-right">Standard</th><th class="{{ $th }} text-center">×</th><th class="{{ $th }} text-right">Amount</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Generated</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($bills as $b)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $bills->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold">{{ $b->bill_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $b->file_number }}</td>
                                    <td class="px-4 py-3">{{ $b->payer_name ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $b->bill_type === 'processing' ? 'Processing fee' : 'Betterment' }}</td>
                                    <td class="px-4 py-3">{{ $b->category ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $b->area !== null ? number_format((float) $b->area, 2) : '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $naira($b->standard_amount) }}</td>
                                    <td class="px-4 py-3 text-center">@if ($b->penalty_multiplier > 1)<span class="rounded bg-rose-50 px-1.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">×{{ $b->penalty_multiplier }}</span>@else <span class="text-slate-400">1</span> @endif</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $naira($b->amount) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $b->isPaid() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">{{ ucfirst($b->status) }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $b->created_at?->format('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action :href="route('kamma.bills.print', $b->id)"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Print bill</x-row-action>
                                            @if ($b->isPaid())
                                                <x-row-action :href="route('kamma.payments.receipt', $b->id)"><x-slot:icon><i data-lucide="file-check-2" class="h-4 w-4"></i></x-slot:icon> e-Receipt</x-row-action>
                                            @else
                                                <x-row-action :href="route('kamma.payments.index', ['q' => $b->bill_no])"><x-slot:icon><i data-lucide="banknote" class="h-4 w-4"></i></x-slot:icon> Record payment</x-row-action>
                                            @endif
                                            @if ($b->file_indexing_id)
                                                <x-row-action :href="route('kamma.shadow-files.show', $b->file_indexing_id)"><x-slot:icon><i data-lucide="folder-open" class="h-4 w-4"></i></x-slot:icon> Open shadow file</x-row-action>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $bills->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
