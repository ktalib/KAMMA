{{-- DMS > File SerialNo Grouping > Print Files Label (KLAES Print File Labels): pick shadow files, choose a label format,
     print. Each label carries the file number, title, Plot No. / District / LGA and a QR code of the file number. --}}
<x-app-layout title="Print Files Label">
    @php $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp

    <div class="space-y-6" x-data="{ picked: [], format: 'single', all: @js($files->pluck('id')->map(fn ($i) => (string) $i)->all()) }">
        <x-ui.page-head icon="tag" title="Print Files Label" subtitle="Folder labels with QR code for shadow files" tone="violet" section="DMS › Indexing › File SerialNo Grouping" />

        @if ($error)<x-ui.klaes-note :error="$error" />@endif

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-[14rem] flex-1">
                <label for="pl-q" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">File number or title</label>
                <input id="pl-q" type="search" name="q" value="{{ $q }}" class="{{ $field }} w-full">
            </div>
            <div>
                <label for="pl-b" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Batch</label>
                <select id="pl-b" name="batch" class="{{ $field }}"><option value="">All batches</option>@foreach ($batches as $b)<option @selected($batch === $b)>{{ $b }}</option>@endforeach</select>
            </div>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Show files</button>
        </form>

        <x-ui.panel title="Files" :subtitle="$files->count().' file(s)'" :padded="false">
            <x-slot:actions>
                <button type="button" @click="picked = picked.length === all.length ? [] : [...all]" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300" x-text="picked.length === all.length && all.length ? 'Clear all' : 'Select all'"></button>
            </x-slot:actions>
            @if ($files->isEmpty())
                <x-ui.empty icon="tag" title="No shadow files to label">Commission files with the File Indexing Assistant first.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="w-10 px-4 py-3"></th><th class="px-4 py-3">S/N</th><th class="px-4 py-3">File No.</th><th class="px-4 py-3">File Title</th><th class="px-4 py-3">Plot No.</th><th class="px-4 py-3">District</th><th class="px-4 py-3">LGA</th><th class="px-4 py-3">Location</th><th class="px-4 py-3">Batch</th><th class="px-4 py-3">Serial</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($files as $f)
                                <tr>
                                    <td class="px-4 py-3"><input type="checkbox" value="{{ $f->id }}" x-model="picked" aria-label="Select {{ $f->file_number }}" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"></td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $f->file_number }}</td>
                                    <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $f->file_title ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->district ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->lga ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($f->location, 36) ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->batch_no ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->serial_no ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>

        <div x-show="picked.length" x-cloak class="sticky bottom-4 flex flex-wrap items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl dark:bg-slate-800">
            <span class="font-semibold"><span x-text="picked.length"></span> selected</span>
            <label class="flex items-center gap-2"><input type="radio" value="single" x-model="format" class="text-indigo-500"> 100 × 60 mm label</label>
            <label class="flex items-center gap-2"><input type="radio" value="a4" x-model="format" class="text-indigo-500"> A4 sheet (3 × 7)</label>
            <a :href="@js(route('dms.labels.sheet')) + '?format=' + format + '&ids=' + picked.join(',')" target="_blank" class="ml-auto inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 font-semibold hover:bg-indigo-400"><i data-lucide="printer" class="h-4 w-4"></i> Print labels</a>
        </div>
    </div>
</x-app-layout>
