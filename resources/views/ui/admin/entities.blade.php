{{-- System Admin > Configurable Entities > Entities & Rates (KLAES configurable-entries.index: entries grouped by kind, each
     with its rate or value and an edit pop-up). KAMMA's entries: the processing fee, the serial generators (serials,
     real values), the request purposes and the extra page types. Edits post
     to the preview stub. --}}
<x-app-layout title="Entities & Rates">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-4 py-3';
        $head = 'bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400';
    @endphp

    <div class="space-y-6" x-data="{ edit: null }">
        <x-ui.page-head icon="sliders-horizontal" title="Entries & Rates" subtitle="Fees, serial generators and lookup lists used across KLAES-METRO" tone="slate" section="System Admin › Configurable Entities" />
        <x-ui.preview-note what="Changing an entry" />

        <x-ui.panel title="Rates" :padded="false">
            <table class="min-w-full text-sm">
                <thead class="{{ $head }}"><tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Entry</th><th class="{{ $th }}">Basis</th><th class="{{ $th }} text-right">Rate</th><th class="{{ $th }}">Note</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td class="px-4 py-3 text-slate-500">1</td><td class="px-4 py-3 font-semibold">Application Processing Fee</td><td class="px-4 py-3">Per file</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">₦{{ number_format($fee, 2) }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">Not set yet: KAMMA_PROCESSING_FEE (amount awaiting the Agency)</td>
                        <td class="px-4 py-2 text-right">
                            <x-row-actions>
                                <button type="button" @click.stop="edit = { name: 'Application Processing Fee', value: {{ $fee }} }; open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="pencil" class="h-4 w-4 text-slate-400"></i> Edit rate</button>
                            </x-row-actions>
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-ui.panel>

        <x-ui.panel title="Serial generators" subtitle="Each serial restarts at 1 on 1 January" :padded="false">
            <table class="min-w-full text-sm">
                <thead class="{{ $head }}"><tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Generator</th><th class="{{ $th }}">Numbers</th><th class="{{ $th }}">Format</th><th class="{{ $th }}">Year</th><th class="{{ $th }} text-right">Last issued</th><th class="{{ $th }}">Next</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach (\App\Http\Controllers\Ui\AdminController::SERIALS as $name => [$what, $format])
                        @php $row = $serials->where('name', $name)->sortByDesc('year')->first(); @endphp
                        <tr>
                            <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $name }}</td>
                            <td class="px-4 py-3">{{ $what }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $format }}</td>
                            <td class="px-4 py-3">{{ $row->year ?? now()->year }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->last_value ?? 0) }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-700 dark:text-indigo-300">{{ str_replace(['YYYY', '00001', '-n'], [$row->year ?? now()->year, sprintf('%05d', ($row->last_value ?? 0) + 1), '-'.(($row->last_value ?? 0) + 1)], $format) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.panel>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.panel title="Request purposes" subtitle="Log a File / Quick Search: purpose and turnaround days" :padded="false">
                <table class="min-w-full text-sm">
                    <thead class="{{ $head }}"><tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Purpose</th><th class="{{ $th }} text-right">Days</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($purposes as $p => $d)
                            <tr><td class="px-4 py-2.5 text-slate-500">{{ $loop->iteration }}</td><td class="px-4 py-2.5">{{ $p }}</td><td class="px-4 py-2.5 text-right tabular-nums">{{ $d }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="border-t border-slate-100 px-6 py-3 text-xs text-amber-700 dark:border-slate-800 dark:text-amber-300">Placeholder list until KAMMA sends its real request purposes.</p>
            </x-ui.panel>
            <x-ui.panel title="KAMMA page types" subtitle="Added to KLAES's page types for Page Typing" :padded="false">
                <table class="min-w-full text-sm">
                    <thead class="{{ $head }}"><tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Page type</th><th class="{{ $th }}">Sub-types</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($pageTypes as $type => $subs)
                            <tr><td class="px-4 py-2.5 align-top text-slate-500">{{ $loop->iteration }}</td><td class="px-4 py-2.5 align-top font-semibold">{{ $type }}</td><td class="px-4 py-2.5 text-slate-600 dark:text-slate-300">{{ implode(', ', $subs) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.panel>
        </div>

        <div x-show="edit" x-cloak class="fixed inset-0 z-40 flex items-start justify-center bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="edit = null">
            <form method="POST" action="{{ route('ui.preview', 'entity-rate') }}" @click.outside="edit = null" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Edit rate: <span x-text="edit?.name"></span></h2>
                <div><label for="en-rate" class="{{ $label }}">Rate (₦) <span class="text-rose-500">*</span></label><input id="en-rate" type="number" min="0" step="0.01" name="rate" :value="edit?.value" required class="{{ $field }}"></div>
                <div><label for="en-eff" class="{{ $label }}">Effective from <span class="text-rose-500">*</span></label><input id="en-eff" type="date" name="effective_from" required min="{{ now()->toDateString() }}" class="{{ $field }}"></div>
                <div><label for="en-why" class="{{ $label }}">Reason <span class="text-rose-500">*</span></label><input id="en-why" name="reason" required class="{{ $field }}"></div>
                <div class="flex justify-end gap-3"><button type="button" @click="edit = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700 dark:text-slate-300">Cancel</button><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
