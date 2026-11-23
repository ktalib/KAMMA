{{-- KLAES REV-M > Legacy Billing > Bill Legacy (KLAES revenue.legacy: bills raised before the system, captured so receipts
     and balances follow the file). --}}
<x-app-layout title="Bill Legacy">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="history" title="Bill Legacy" subtitle="Capture bills raised before KLAES-METRO, with their payments" tone="slate" section="KLAES REV-M › Legacy Billing" />
        <x-ui.preview-note what="Capturing a legacy bill" />

        <x-ui.panel title="Capture a legacy bill">
            <form method="POST" action="{{ route('ui.preview', 'bill-legacy') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div class="sm:col-span-2 lg:col-span-4"><x-ui.file-picker name="file_indexing_id" label="Land file (KLAES)" /></div>
                <x-ui.select-other name="bill_type" label="Bill type" :options="['Application Processing Fee', 'Betterment Fee', 'Planning Recommendation Fee', 'Contravention Fine']" required />
                <div><label for="bl-ref" class="{{ $label }}">Old bill reference</label><input id="bl-ref" name="legacy_ref" class="{{ $field }}"></div>
                <div><label for="bl-from" class="{{ $label }}">From year <span class="text-rose-500">*</span></label><input id="bl-from" type="number" min="1960" max="{{ now()->year }}" name="from_year" required class="{{ $field }}"></div>
                <div><label for="bl-to" class="{{ $label }}">To year <span class="text-rose-500">*</span></label><input id="bl-to" type="number" min="1960" max="{{ now()->year }}" name="to_year" required class="{{ $field }}"></div>
                <div><label for="bl-amt" class="{{ $label }}">Amount (₦) <span class="text-rose-500">*</span></label><input id="bl-amt" type="number" min="0" step="0.01" name="amount" required class="{{ $field }}"></div>
                <div><label for="bl-paid" class="{{ $label }}">Amount paid (₦)</label><input id="bl-paid" type="number" min="0" step="0.01" name="amount_paid" class="{{ $field }}"></div>
                <div><label for="bl-rct" class="{{ $label }}">Receipt no.</label><input id="bl-rct" name="receipt_no" class="{{ $field }}"></div>
                <div><label for="bl-rd" class="{{ $label }}">Receipt date</label><input id="bl-rd" type="date" name="receipt_date" max="{{ now()->toDateString() }}" class="{{ $field }}"></div>
                <div class="flex justify-end sm:col-span-2 lg:col-span-4"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save legacy bill</button></div>
            </form>
        </x-ui.panel>

        <x-ui.panel title="Legacy bills" :padded="false">
            <x-ui.empty icon="history" title="No legacy bills captured">Captured bills will be listed with their file, years, amounts and receipts.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
