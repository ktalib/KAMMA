<x-app-layout title="Payment">
    @php
        $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
        $errs = $errors->getBag('payment');
        $pay = fn ($bill) => [
            'id' => $bill->id,
            'url' => route('bpa.payments.store', $bill),
            'bill_no' => $bill->bill_no,
            'application_no' => $bill->application?->application_no,
            'developer' => $bill->application?->developer_name,
            'amount' => number_format((float) $bill->amount, 2, '.', ''),
            'amount_text' => '₦'.number_format((float) $bill->amount, 2),
        ];
    @endphp

    <div class="space-y-6"
         x-data="{
            bill: @js($reopen ? $pay($reopen) : null),
            channel: @js(old('payment_channel', '')),
            openPay(b) { this.bill = b; this.channel = ''; this.$nextTick(() => document.getElementById('receipt_no')?.focus()); },
         }"
         @keydown.escape.window="bill = null">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Payment</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Confirm the application bill was paid. A paid application moves on to File Commissioning.</p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <x-tabs :tabs="['unpaid' => ['Awaiting payment', $counts['unpaid']], 'paid' => ['Paid', $counts['paid']]]" :active="$tab" route="bpa.payments.index" />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Bill no., receipt no., application no., file no., developer…" class="{{ $field }} w-96">
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Search</button>
            @if ($search !== '')
                <a href="{{ route('bpa.payments.index', ['tab' => $tab]) }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        <th class="{{ $th }}">Bill No.</th>
                        <th class="{{ $th }}">Application No.</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }} text-right">Amount (₦)</th>
                        @if ($tab === 'paid')
                            <th class="{{ $th }}">Receipt No.</th>
                            <th class="{{ $th }}">Paid on</th>
                            <th class="{{ $th }}">Channel</th>
                            <th class="{{ $th }}">Recorded by</th>
                        @else
                            <th class="{{ $th }}">Raised</th>
                        @endif
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($bills as $bill)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $bills->firstItem() + $loop->index }}</td>
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $bill->bill_no }}</td>
                            <td class="{{ $td }}">{{ $bill->application?->application_no }}</td>
                            <td class="{{ $td }}">{{ $bill->application?->file_number }}</td>
                            <td class="{{ $td }} min-w-[9rem] !whitespace-normal">{{ $bill->application?->developer_name }}</td>
                            <td class="{{ $td }} text-right font-medium text-slate-900 dark:text-slate-100">{{ number_format((float) $bill->amount, 2) }}</td>
                            @if ($tab === 'paid')
                                <td class="{{ $td }}">{{ $bill->receipt_no }}</td>
                                <td class="{{ $td }}">{{ $bill->paid_on?->format('d M Y') }}</td>
                                <td class="{{ $td }}">{{ $bill->payment_channel === 'Other' ? $bill->payment_channel_other : $bill->payment_channel }}</td>
                                <td class="{{ $td }}">{{ $bill->payment_recorded_by_name }}</td>
                            @else
                                <td class="{{ $td }}">{{ $bill->created_at?->format('d M Y') }}</td>
                            @endif
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                <x-row-actions>
                                    @if ($bill->status === 'Unpaid')
                                        <button type="button" @click.stop="openPay(@js($pay($bill))); open = false"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                                            Record payment
                                        </button>
                                    @endif
                                    @if ($bill->application)
                                        <x-row-action :href="route('bpa.applications.show', $bill->application)">
                                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-slot:icon>
                                            View application
                                        </x-row-action>
                                    @endif
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'paid' ? 11 : 8 }}" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' ? 'No bills match this search.' : ($tab === 'paid' ? 'No payments recorded yet.' : 'No bills are waiting for payment.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $bills->links() }}

        {{-- Record payment pop-up --}}
        <div x-show="bill" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="pay-title">
            <form method="POST" :action="bill ? bill.url : ''" @click.outside="bill = null"
                  class="w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <input type="hidden" name="bill_id" :value="bill ? bill.id : ''">
                <div>
                    <h2 id="pay-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Record payment</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        <span x-text="bill ? bill.bill_no + ' · ' + bill.application_no : ''"></span><br>
                        <span x-text="bill ? bill.developer : ''"></span>
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800/60">
                    <span class="text-slate-500 dark:text-slate-400">Bill amount:</span>
                    <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="bill ? bill.amount_text : ''"></span>
                </div>
                @if ($errs->any())
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        @foreach ($errs->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="receipt_no" class="{{ $label }}">Receipt No. <span class="text-rose-500">*</span></label>
                        <input id="receipt_no" name="receipt_no" value="{{ old('receipt_no') }}" required maxlength="100" class="{{ $field }} uppercase">
                    </div>
                    <div>
                        <label for="paid_on" class="{{ $label }}">Payment date <span class="text-rose-500">*</span></label>
                        <input id="paid_on" type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $field }}">
                    </div>
                    <div>
                        <label for="amount_paid" class="{{ $label }}">Amount paid (₦) <span class="text-rose-500">*</span></label>
                        <input id="amount_paid" name="amount_paid" inputmode="decimal" required :value="@js(old('amount_paid')) || (bill ? bill.amount : '')" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="payment_channel" class="{{ $label }}">Channel <span class="text-rose-500">*</span></label>
                        <select id="payment_channel" name="payment_channel" x-model="channel" required class="{{ $field }}">
                            <option value="">Select…</option>
                            @foreach ($channels as $option)
                                <option value="{{ $option }}">{{ $option === 'Other' ? 'Other (specify)' : $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="channel === 'Other'" class="sm:col-span-2">
                        <label for="payment_channel_other" class="{{ $label }}">Specify the channel <span class="text-rose-500">*</span></label>
                        <input id="payment_channel_other" name="payment_channel_other" value="{{ old('payment_channel_other') }}" :disabled="channel !== 'Other'" :required="channel === 'Other'" class="{{ $field }} uppercase">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="bill = null" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Record payment</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
