{{-- KAMMA > Bills & Payments > Payments (KLAES PP Bills & Payments; also REV-M Awaiting Receipts / e-Receipt / Print Receipt).
     Tabs Awaiting payment / Paid; S/N by row order; "Record payment" opens a pop-up from the three-dots menu
     (receipt no. unique, amount must equal the bill, channel with one "Other → specify"). --}}
@php
    $naira = fn ($v) => '₦'.number_format((float) $v, 2);
    $th = 'whitespace-nowrap px-4 py-3';
    $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
    $errs = $errors->getBag('payment');
    $title = $receipts ? 'Print Receipt' : ($tab === 'paid' ? ($fromRevm ? 'e-Receipt' : 'Payments') : ($fromRevm ? 'Awaiting Receipts' : 'Payments'));
    $pay = fn ($b) => [
        'id' => $b->id, 'url' => route('kamma.payments.store', $b->id), 'bill_no' => $b->bill_no, 'file_number' => $b->file_number,
        'payer' => $b->payer_name, 'amount' => number_format((float) $b->amount, 2, '.', ''), 'amount_text' => $naira($b->amount),
    ];
    $keep = array_filter(['from' => $fromRevm ? 'revm' : null, 'type' => $type, 'q' => $q]);
    $chip = fn ($active) => $active ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700';
@endphp

<x-app-layout :title="$title">
    <div class="space-y-6"
         x-data="{
            bill: @js($reopen ? $pay($reopen) : null),
            channel: @js(old('channel', '')),
            openPay(b) { this.bill = b; this.channel = ''; this.$nextTick(() => document.getElementById('receipt_no')?.focus()); },
         }"
         @keydown.escape.window="bill = null">
        <x-ui.page-head icon="banknote" :title="$title" :section="$fromRevm ? 'KLAES REV-M › Receipts' : 'KAMMA › Bills & Payments'" tone="emerald"
                        subtitle="Record the applicant's payment against a KAMMA bill: Application Processing Fee or Betterment. Staff record payments; there is no online payment.">
            <a href="{{ route('kamma.bills.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="receipt" class="h-4 w-4"></i> Bills</a>
            <a href="{{ route('kamma.payments.report') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i> Payment Reports</a>
        </x-ui.page-head>

        @unless ($receipts)
            <x-tabs :tabs="['unpaid' => ['Awaiting payment', $counts['unpaid']], 'paid' => ['Paid', $counts['paid']]]" :active="$tab" route="kamma.payments.index" :query="$keep" />
        @endunless

        <div class="flex flex-wrap items-center gap-2">
            @foreach ([null => 'All bills', 'processing' => 'Processing fee', 'betterment' => 'Betterment'] as $k => $v)
                <a href="{{ route($receipts ? 'kamma.payments.receipts' : 'kamma.payments.index', array_filter(['from' => $keep['from'] ?? null, 'q' => $q, 'tab' => $receipts ? null : $tab, 'type' => $k ?: null])) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $chip($type === ($k ?: null)) }}">{{ $v }}</a>
            @endforeach
        </div>

        <x-ui.panel :title="$tab === 'paid' ? 'Paid bills' : 'Bills awaiting payment'" :subtitle="$bills->total() ? 'Showing '.$bills->firstItem().'–'.$bills->lastItem().' of '.$bills->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="relative">
                    @foreach (array_filter(['from' => $keep['from'] ?? null, 'type' => $type, 'tab' => $receipts ? null : $tab]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Bill no., file no., payer, receipt…" class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </x-slot:actions>

            @if ($bills->isEmpty())
                <x-ui.empty icon="banknote" :title="$q !== '' || $type ? 'No bill matches' : ($tab === 'paid' ? 'No payments recorded yet' : 'No bill is waiting for payment')">
                    Bills are generated under Bills &amp; Payments › Bills.
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th><th class="{{ $th }}">Bill No.</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">Payer</th><th class="{{ $th }}">Type</th>
                                <th class="{{ $th }} text-right">Amount</th>
                                @if ($tab === 'paid')
                                    <th class="{{ $th }}">Receipt No.</th><th class="{{ $th }}">Paid on</th><th class="{{ $th }}">Channel</th>
                                @else
                                    <th class="{{ $th }} text-center">×</th><th class="{{ $th }}">Generated</th>
                                @endif
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($bills as $b)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $bills->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold">{{ $b->bill_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $b->file_number }}</td>
                                    <td class="px-4 py-3">{{ $b->payer_name ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $b->bill_type === 'processing' ? 'Processing fee' : 'Betterment' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $naira($tab === 'paid' ? $b->amount_paid : $b->amount) }}</td>
                                    @if ($tab === 'paid')
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $b->receipt_no }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $b->paid_on?->format('d M Y') }}</td>
                                        <td class="px-4 py-3">{{ $b->channel === 'Other' ? 'Other: '.$b->channel_other : $b->channel }}</td>
                                    @else
                                        <td class="px-4 py-3 text-center">@if ($b->penalty_multiplier > 1)<span class="rounded bg-rose-50 px-1.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">×{{ $b->penalty_multiplier }}</span>@else <span class="text-slate-400">1</span> @endif</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $b->created_at?->format('d M Y') }}</td>
                                    @endif
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            @if ($b->isPaid())
                                                <x-row-action :href="route('kamma.payments.receipt', $b->id)"><x-slot:icon><i data-lucide="file-check-2" class="h-4 w-4"></i></x-slot:icon> e-Receipt</x-row-action>
                                            @else
                                                <button type="button" @click.stop="openPay(@js($pay($b))); open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="banknote" class="h-4 w-4 text-emerald-600"></i> Record payment</button>
                                            @endif
                                            <x-row-action :href="route('kamma.bills.print', $b->id)"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Print bill</x-row-action>
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

        {{-- Record payment pop-up --}}
        <div x-show="bill" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/50 p-4 sm:p-10" role="dialog" aria-modal="true" aria-labelledby="pay-title">
            <form method="POST" :action="bill ? bill.url : ''" @click.outside="bill = null" class="w-full max-w-lg rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                @csrf
                <input type="hidden" name="bill_id" :value="bill ? bill.id : ''">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 id="pay-title" class="text-base font-bold text-slate-900 dark:text-white">Record payment</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400" x-text="bill ? bill.bill_no + ' · ' + bill.file_number + (bill.payer ? ' · ' + bill.payer : '') : ''"></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800/60">
                        <span class="text-slate-500 dark:text-slate-400">Bill amount:</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="bill ? bill.amount_text : ''"></span>
                    </div>
                    @if ($errs->any())
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                            @foreach ($errs->all() as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="receipt_no" class="{{ $lbl }}">Receipt No. <span class="text-rose-500">*</span></label>
                            <input id="receipt_no" name="receipt_no" value="{{ old('receipt_no') }}" required maxlength="100" class="{{ $field }} uppercase">
                        </div>
                        <div>
                            <label for="paid_on" class="{{ $lbl }}">Payment date <span class="text-rose-500">*</span></label>
                            <input id="paid_on" type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $field }}">
                        </div>
                        <div>
                            <label for="amount_paid" class="{{ $lbl }}">Amount paid (₦) <span class="text-rose-500">*</span></label>
                            <input id="amount_paid" name="amount_paid" inputmode="decimal" required :value="@js(old('amount_paid')) || (bill ? bill.amount : '')" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="channel" class="{{ $lbl }}">Channel <span class="text-rose-500">*</span></label>
                            <select id="channel" name="channel" x-model="channel" required class="{{ $field }}">
                                <option value="">Select…</option>
                                @foreach ($channels as $option)
                                    <option value="{{ $option }}">{{ $option === 'Other' ? 'Other (specify)' : $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="channel === 'Other'" class="sm:col-span-2">
                            <label for="channel_other" class="{{ $lbl }}">Specify the channel <span class="text-rose-500">*</span></label>
                            <input id="channel_other" name="channel_other" value="{{ old('channel_other') }}" maxlength="100" :disabled="channel !== 'Other'" :required="channel === 'Other'" class="{{ $field }}">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" @click="bill = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Record payment</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
