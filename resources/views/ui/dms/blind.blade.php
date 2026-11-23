{{-- DMS > Blind Scanning (KLAES scanning/blind_scans): upload scanned folders before indexing — registry, the file number the
     folder is meant for (picked, never typed), paper size, pick the local folder, then process. Upload History below. --}}
<x-app-layout title="Blind Scanning">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6" x-data="{ files: [], kind: 'full' }">
        <x-ui.page-head icon="scan" title="Blind Scanning" subtitle="Upload scanned folders, then match them to their files" tone="emerald" section="DMS" />
        <x-ui.preview-note what="Blind-scan upload and processing" />

        <form method="POST" action="{{ route('ui.preview', 'blind-scanning') }}" class="grid gap-6 xl:grid-cols-3">
            @csrf
            <x-ui.panel title="Blind scan" subtitle="Step 1: the target file and folder" class="xl:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select-other name="registry" label="Registry" :options="$registries ?: ['Lands Registry', 'KAMMA Registry']" required />
                    <div>
                        <span class="{{ $label }}">What is in the folder <span class="text-rose-500">*</span></span>
                        <div class="flex flex-wrap gap-2 pt-1">
                            @foreach (['full' => 'Full file', 'lf' => 'LF Scan (A3)', 'cards' => 'Index cards'] as $v => $l)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm" :class="kind === '{{ $v }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-300 text-slate-600 dark:border-slate-700 dark:text-slate-300'">
                                    <input type="radio" name="kind" value="{{ $v }}" x-model="kind" class="sr-only">{{ $l }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.file-picker name="file_indexing_id" label="File number (target on server)" hint="Pick the KLAES land file the scanned folder belongs to." />
                    </div>
                    <div>
                        <label for="bs-paper" class="{{ $label }}">Paper size <span class="text-rose-500">*</span></label>
                        <select id="bs-paper" name="paper_size" required class="{{ $field }}"><option>A4</option><option>A3</option><option>Mixed</option></select>
                    </div>
                    <div>
                        <label for="bs-folder" class="{{ $label }}">Pick local folder <span class="text-rose-500">*</span></label>
                        <input id="bs-folder" type="file" name="pages[]" multiple webkitdirectory accept="image/*,.pdf,.tif,.tiff" required
                               @change="files = [...$event.target.files].map(f => ({ name: f.name, size: f.size }))"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:text-slate-300 dark:file:bg-indigo-500/10 dark:file:text-indigo-300">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="upload-cloud" class="h-4 w-4"></i> Process to BLIND_SCAN</button>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Picked pages" subtitle="Step 2: check before processing">
                <p x-show="! files.length" class="text-sm text-slate-500 dark:text-slate-400">No folder picked yet.</p>
                <ul x-show="files.length" x-cloak class="max-h-80 space-y-1 overflow-y-auto text-sm">
                    <template x-for="(f, i) in files" :key="i">
                        <li class="flex justify-between gap-2 rounded-md px-2 py-1 odd:bg-slate-50 dark:odd:bg-slate-800/60">
                            <span class="truncate text-slate-700 dark:text-slate-200" x-text="(i + 1) + '. ' + f.name"></span>
                            <span class="shrink-0 text-xs text-slate-400" x-text="(f.size / 1024).toFixed(0) + ' KB'"></span>
                        </li>
                    </template>
                </ul>
                <p x-show="files.length" x-cloak class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-300"><span x-text="files.length"></span> page(s)</p>
            </x-ui.panel>
        </form>

        <x-ui.panel title="Upload History" subtitle="Blind Scanning Summary" :padded="false">
            <x-ui.empty icon="history" title="No blind scans yet">Each processed folder will be listed with its file number, date uploaded, registry, page totals, uploader and progress.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
