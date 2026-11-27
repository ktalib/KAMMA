{{-- KAMMA > Title Status Update (KLAES title_status/index: records table with search + "New Title Status" pop-up: land
     file selector, Title Type, Initiated By (options follow the type, single-option types lock), Reason, generated remark).
     KAMMA keeps its own record; the KLAES land file is not flagged from here. S/N by row order; property order
     Plot No., District, LGA, Location. --}}
@php
    $th = 'whitespace-nowrap px-4 py-3';
    $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
@endphp

<x-app-layout title="Title Status Update">
    <div class="space-y-6"
         x-data="{
            show: @js($errors->any()),
            type: @js(old('title_type', '')),
            by: @js(old('initiated_by', '')),
            reason: @js(old('reason', '')),
            file: null,
            map: @js($initiatedByByType),
            all: @js($initiatedBy),
            options() { return this.type && this.type !== 'Other' && this.map[this.type] ? this.map[this.type] : this.all; },
            pickType() { const o = this.options(); if (o.length === 1) { this.by = o[0]; } else if (! o.includes(this.by) && this.by !== 'Other') { this.by = ''; } },
            remark() {
                if (! this.type) { return ''; }
                const holder = this.file && this.file.file_title ? this.file.file_title : '';
                const who = ['Allottee', 'Applicant'].includes(this.by) && holder ? holder + ' (' + this.by + ')' : (this.by || '[Initiated by]');
                return (this.type === 'Other' ? '[Title type]' : this.type) + (this.file ? ' FileNo ' + this.file.file_number : '') + ' was initiated by ' + who + ' on [date] due to ' + (this.reason || '[Reason]');
            },
         }"
         @file-picked="file = $event.detail"
         @keydown.escape.window="show = false">
        <x-ui.page-head icon="badge-check" title="Title Status Update" section="KAMMA" tone="emerald"
                        subtitle="Record a title status (litigation, revocation, withdrawal…) on a land file. Kept in KAMMA; the KLAES land file itself is not changed from here.">
            <button type="button" @click.stop="show = true" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"><i data-lucide="plus" class="h-4 w-4"></i> New title status</button>
        </x-ui.page-head>

        <x-ui.panel title="Title status records" :subtitle="$records->total() ? 'Showing '.$records->firstItem().'–'.$records->lastItem().' of '.$records->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="type" onchange="this.form.submit()" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">All title types</option>
                        @foreach ([...$types, 'Other'] as $t)<option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>@endforeach
                    </select>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="search" name="q" value="{{ $q }}" placeholder="File no., title, plot, district…" class="w-64 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                </form>
            </x-slot:actions>

            @if ($records->isEmpty())
                <x-ui.empty icon="badge-check" :title="$q !== '' || $type ? 'No record matches' : 'No title status recorded yet'">Use "New title status" to record one.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">File title</th><th class="{{ $th }}">Title type</th><th class="{{ $th }}">Initiated by</th><th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th><th class="{{ $th }}">Remark</th><th class="{{ $th }}">Recorded</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($records as $r)
                                <tr class="align-top hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $records->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $r->file_number }}</td>
                                    <td class="px-4 py-3">{{ $r->file_title ?: '—' }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">{{ $r->typeLabel() }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $r->initiated_by === 'Other' ? $r->initiated_by_other : $r->initiated_by }}</td>
                                    <td class="px-4 py-3">{{ $r->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->district ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->lga ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->location ?: '—' }}</td>
                                    <td class="max-w-sm px-4 py-3 text-xs text-slate-600 dark:text-slate-300">{{ $r->remark }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r->created_at?->format('d M Y') }}<span class="block text-xs">{{ $r->created_by_name }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $records->links() }}</div>
            @endif
        </x-ui.panel>

        {{-- New title status pop-up --}}
        <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" role="dialog" aria-modal="true" aria-labelledby="ts-title">
            <form method="POST" action="{{ route('kamma.title-status.store') }}" @click.outside="show = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                @csrf
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 id="ts-title" class="text-base font-bold text-slate-900 dark:text-white">New title status</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pick the land file; its details come from the KLAES indexing record.</p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    @if ($errors->any())
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                            @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
                        </div>
                    @endif

                    <x-ui.file-picker name="klaes_file_indexing_id" source="klaes" label="Land file (KLAES)" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ts-type" class="{{ $lbl }}">Title type <span class="text-rose-500">*</span></label>
                            <select id="ts-type" name="title_type" x-model="type" @change="pickType()" required class="{{ $field }}">
                                <option value="">— Select —</option>
                                @foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                                <option value="Other">Other (specify)</option>
                            </select>
                        </div>
                        <div>
                            <label for="ts-by" class="{{ $lbl }}">Initiated by <span class="text-rose-500">*</span></label>
                            <select id="ts-by" name="initiated_by" x-model="by" required class="{{ $field }}">
                                <option value="">— Select —</option>
                                <template x-for="o in options()" :key="o"><option :value="o" x-text="o" :selected="o === by"></option></template>
                                <option value="Other">Other (specify)</option>
                            </select>
                        </div>
                        <div x-show="type === 'Other'">
                            <label for="ts-type-other" class="{{ $lbl }}">Specify the title type <span class="text-rose-500">*</span></label>
                            <input id="ts-type-other" name="title_type_other" value="{{ old('title_type_other') }}" maxlength="200" :disabled="type !== 'Other'" :required="type === 'Other'" class="{{ $field }}">
                        </div>
                        <div x-show="by === 'Other'">
                            <label for="ts-by-other" class="{{ $lbl }}">Specify who initiated it <span class="text-rose-500">*</span></label>
                            <input id="ts-by-other" name="initiated_by_other" value="{{ old('initiated_by_other') }}" maxlength="200" :disabled="by !== 'Other'" :required="by === 'Other'" class="{{ $field }}">
                        </div>
                    </div>

                    <div>
                        <label for="ts-reason" class="{{ $lbl }}">Reason <span class="text-rose-500">*</span></label>
                        <textarea id="ts-reason" name="reason" x-model="reason" rows="3" maxlength="2000" required class="{{ $field }}"></textarea>
                    </div>

                    <div x-show="type" class="rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                        <span class="font-semibold">Remark:</span> <span x-text="remark()"></span>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" @click="show = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save title status</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
