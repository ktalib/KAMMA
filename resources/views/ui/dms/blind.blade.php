{{-- DMS > Blind Scanning, copied from KLAES scanning/blind_scans + blind_scan_js (user 2026-11-25). KAMMA shadow files
     (BP files on hold), on KAMMA's own storage: EDMS/BLIND_SCAN/KAMMA_Raw/[file no.] (never the KLAES F:\storage).
     Migrate to Server sends the picked folder in batches (Dms\BlindScanController@migrate); the Server Browser, Upload
     History and Summary read kamma_blind_scans. Scan Upload (Upload Method = Blind scan) then takes the pages in. --}}
<x-app-layout title="Blind Scanning">
    @php
        $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $hint = 'mt-1 text-xs text-slate-500 dark:text-slate-400';
        $btn = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700';
        $th = 'p-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'p-3 text-sm text-slate-700 dark:text-slate-300';
        $size = function ($b) { $b = (int) $b; foreach (['B', 'KB', 'MB', 'GB'] as $u) { if ($b < 1024) return round($b, 1).' '.$u; $b /= 1024; } return round($b, 1).' TB'; };
        $cards = [['Uploaded Today', $stats['today'], 'calendar-days', 'text-indigo-500'], ['This Week', $stats['week'], 'calendar-range', 'text-blue-500'],
            ['Total Uploaded', $stats['total'], 'database', 'text-slate-500'], ['Awaiting Scan Upload', $stats['pending'], 'hourglass', 'text-amber-500'],
            ['Moved to Scan Upload', $stats['moved'], 'circle-check', 'text-emerald-600']];
    @endphp

    <div class="space-y-6" x-data="blindScan(@js([
            'migrate' => route('dms.blind.migrate'),
            'folder' => route('dms.blind.folder', ['indexing' => '__ID__']),
            'batch' => $batch,
            'maxKb' => (int) config('kamma.dms_page_max_kb'),
            'extensions' => array_values((array) config('kamma.edms.extensions')),
        ]))" @file-picked.window="file = $event.detail; done = ''; error = ''" @keydown.escape.window="preview = null">

        {{-- Page header: the three steps and the destination folder (KLAES layout). --}}
        <div class="rounded-2xl border-l-4 border-indigo-500 bg-gradient-to-r from-indigo-50 to-blue-50 p-6 dark:from-indigo-500/10 dark:to-blue-500/5">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-500 text-white"><i data-lucide="scan-line" class="h-6 w-6"></i></div>
                <div class="flex-1">
                    <h1 class="mb-2 text-2xl font-bold text-slate-900 dark:text-white">BLIND SCANNING - Document Upload &amp; Migration</h1>
                    <p class="mb-4 text-slate-700 dark:text-slate-300">Upload and migrate scanned shadow files to KAMMA's document management system.</p>
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ([['Select File Number', 'Choose the KAMMA shadow file'], ['Pick Scanned Folder', 'Select folder with A4/A3 subfolders'], ['Migrate to Server', 'System creates structured storage']] as $i => [$t, $d])
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-sm font-bold text-white">{{ $i + 1 }}</div>
                                <div><h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $t }}</h3><p class="text-xs text-slate-600 dark:text-slate-400">{{ $d }}</p></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <i data-lucide="folder-open" class="h-4 w-4 text-indigo-600 dark:text-indigo-400"></i>
                        <span class="text-slate-600 dark:text-slate-400">Destination:</span>
                        <code class="rounded border bg-white px-2 py-1 font-mono text-xs text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-indigo-300">{{ $root }}/[FileNumber]</code>
                    </div>
                </div>
            </div>
        </div>

        {{-- Blind Scan Dashboard (KLAES, filled from the summary). --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($cards as [$t, $v, $ico, $tone])
                <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">{{ $t }} <i data-lucide="{{ $ico }}" class="h-4 w-4 {{ $tone }}"></i></div>
                    <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($v) }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">pages</div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            {{-- Left: Upload & Migrate --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-white"><i data-lucide="cloud-upload" class="h-5 w-5"></i>Upload &amp; Migrate</h2>

                <div class="space-y-4">
                    <div>
                        <label for="registry" class="{{ $label }}">Registry <span class="text-rose-500">*</span></label>
                        <select id="registry" class="{{ $field }}"><option value="KAMMA" selected>KAMMA</option></select>
                        <p class="{{ $hint }}">Scans are stored in KAMMA's own blind-scan folder (<code>KAMMA_Raw</code>).</p>
                    </div>

                    <x-ui.file-picker source="shadow" name="file_indexing_id" label="File Number (target on server)"
                                      hint="Choose the shadow file with the search. The field cannot be typed into." />

                    <div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input type="checkbox" x-model="typeOn" @change="if (! typeOn) { type = ''; clearPicked() }" class="h-4 w-4 rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800">
                            <span>Choose upload type <span class="text-rose-500">*</span></span>
                        </label>
                        <div x-show="typeOn" x-cloak class="mt-2 space-y-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            @foreach (['full' => 'Full file (A4 / A3 folders)', 'lf' => 'LF Scan only (large format, A3)', 'ic' => 'Index Cards (property index cards)'] as $v => $l)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="radio" value="{{ $v }}" x-model="type" @change="clearPicked()" class="h-4 w-4 border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800">
                                    <span>{{ $l }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="{{ $hint }}" x-show="! type">Required before a folder can be picked.</p>
                    </div>

                    <div x-show="type === 'full' || ! type">
                        <label for="folder" class="{{ $label }}">Pick local folder</label>
                        <input id="folder" x-ref="folder" type="file" webkitdirectory directory multiple :disabled="type !== 'full'" @change="pick($event.target.files, true)"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                        <p class="{{ $hint }}">Use Chrome/Edge on desktop. The folder name should match the File Number.</p>
                    </div>

                    <div x-show="type === 'lf' || type === 'ic'" x-cloak>
                        <label for="lf" class="{{ $label }}" x-text="type === 'ic' ? 'Pick the index card images' : 'Pick the LF images'"></label>
                        <input id="lf" x-ref="lf" type="file" multiple accept=".jpg,.jpeg,.png,.tif,.tiff,.bmp,.gif,.webp" @change="pick($event.target.files, false)"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                        <p class="{{ $hint }}" x-text="type === 'ic' ? 'Select the scanned property index cards to add to the INDEX_CARDS folder.' : 'Select only the large-format image files to add to the A3 folder.'"></p>
                    </div>

                    <div class="text-sm text-slate-500 dark:text-slate-400" x-text="status"></div>
                    <p x-show="folderWarning" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-300" x-text="folderWarning"></p>
                    <p x-show="error" x-cloak class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="error"></p>
                    <p x-show="done" x-cloak class="flex items-start gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0"></i><span x-text="done"></span></p>

                    <button type="button" @click="migrate()" :disabled="! ready || busy"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="send" class="h-4 w-4"></i>Migrate to Server
                    </button>
                </div>
            </div>

            {{-- Right: Server Storage & Logs --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3 dark:border-slate-800 dark:bg-slate-900">
                <div class="p-6 pb-2">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Server Storage &amp; Logs</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Browse <code>{{ $root }}</code>, preview files, and review migration logs.</p>
                </div>
                <div class="flex gap-2 px-6">
                    <button type="button" @click="tab = 'server'" :class="tab === 'server' ? '!border-indigo-600 !bg-indigo-600 !text-white' : ''" class="{{ $btn }}">Server Browser</button>
                    <button type="button" @click="tab = 'logs'" :class="tab === 'logs' ? '!border-indigo-600 !bg-indigo-600 !text-white' : ''" class="{{ $btn }}">Upload History</button>
                </div>

                <div x-show="tab === 'server'" class="p-6">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-700 dark:text-slate-300"><span class="mr-2">Path:</span><span class="font-mono" x-text="'/{{ $root }}' + (open ? '/' + open.name : '')"></span></div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <button type="button" @click="open = null; preview = null" class="hover:underline">Root</button><span x-show="open" x-text="' / ' + (open ? open.name : '')"></span>
                            </div>
                        </div>
                        <a href="{{ route('dms.blind') }}" class="{{ $btn }}"><i data-lucide="rotate-cw" class="h-4 w-4"></i>Refresh</a>
                    </div>
                    <form method="GET" class="mb-3 flex items-center gap-2" x-show="! open">
                        <input type="search" name="search" value="{{ $search }}" class="{{ $field }} !w-56" placeholder="Find a folder by file number">
                        <button type="submit" class="{{ $btn }}"><i data-lucide="search" class="h-4 w-4"></i>Find</button>
                    </form>

                    <div class="max-h-[28rem] overflow-auto rounded-lg border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50 dark:bg-slate-800">
                                <tr class="border-b border-slate-200 dark:border-slate-800">
                                    <th class="{{ $th }} w-10">S/N</th><th class="{{ $th }} w-10"></th><th class="{{ $th }}">Name</th><th class="{{ $th }}">Size</th>
                                    <th class="{{ $th }}">Modified</th><th class="{{ $th }}">Uploaded By</th><th class="{{ $th }}"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            {{-- Root: one row per file folder. --}}
                            <tbody x-show="! open" class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($folders as $f)
                                    <tr class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40" @click="openFolder(@js(['id' => $f->file_indexing_id, 'name' => basename($f->folder)]))">
                                        <td class="{{ $td }} text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="{{ $td }}"><i data-lucide="folder" class="h-4 w-4 text-amber-500"></i></td>
                                        <td class="{{ $td }} font-mono font-semibold">{{ basename($f->folder) }}
                                            <span class="ml-1 text-[11px] font-normal text-slate-500">{{ $f->a4 }} A4 · {{ $f->a3 }} A3{{ $f->ic ? ' · '.$f->ic.' cards' : '' }}</span></td>
                                        <td class="{{ $td }}">{{ $size($f->bytes) }}</td>
                                        <td class="{{ $td }} whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($f->last_at)->format('d M Y H:i') }}</td>
                                        <td class="{{ $td }}">{{ $f->uploaded_by_name ?? '—' }}</td>
                                        <td class="{{ $td }} text-right"><i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ $search !== '' ? 'No folder matches that file number.' : 'No folders in KAMMA_Raw yet. Migrated files will be listed here.' }}</td></tr>
                                @endforelse
                            </tbody>
                            {{-- Inside a folder: its pages. --}}
                            <tbody x-show="open" x-cloak class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="(p, n) in pages" :key="p.id">
                                    <tr class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40" :class="preview && preview.id === p.id ? 'bg-indigo-50/60 dark:bg-indigo-500/10' : ''" @click="preview = p">
                                        <td class="{{ $td }} text-slate-400" x-text="n + 1"></td>
                                        <td class="{{ $td }}"><span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="p.paper"></span></td>
                                        <td class="{{ $td }} font-mono text-xs" x-text="p.path"></td>
                                        <td class="{{ $td }}" x-text="fmt(p.size)"></td>
                                        <td class="{{ $td }} whitespace-nowrap" x-text="p.modified"></td>
                                        <td class="{{ $td }}" x-text="p.by || '—'"></td>
                                        <td class="{{ $td }} whitespace-nowrap text-right">
                                            <span x-show="p.status === 'moved'" class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">In Scan Upload</span>
                                            <button type="button" x-show="p.delete_url" @click.stop="remove(p)" class="rounded-md p-1 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="Delete file"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="! pages.length"><td colspan="7" class="p-6 text-center text-sm text-slate-500" x-text="loading ? 'Loading…' : 'This folder is empty.'"></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 p-3 dark:border-slate-800">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-medium text-slate-900 dark:text-white">Quick Preview</span>
                            <a x-show="preview" :href="preview ? preview.url : '#'" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-300">Open full size</a>
                        </div>
                        <div class="flex h-64 items-center justify-center overflow-hidden rounded bg-slate-50 text-sm text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                            <template x-if="preview && ! preview.pdf"><img :src="preview.url" alt="Blind-scan page" class="max-h-full max-w-full object-contain"></template>
                            <template x-if="preview && preview.pdf"><iframe :src="preview.url" class="h-full w-full bg-white" title="PDF page"></iframe></template>
                            <span x-show="! preview">Select a file to preview.</span>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'logs'" x-cloak class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60"><tr class="border-b border-slate-200 dark:border-slate-800">
                                <th class="{{ $th }}">S/N</th><th class="{{ $th }}">Date</th><th class="{{ $th }}">File Number</th><th class="{{ $th }}">Upload type</th><th class="{{ $th }}">Pages</th><th class="{{ $th }}">Uploaded by</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($history as $h)
                                    <tr><td class="{{ $td }} text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="{{ $td }} whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($h->at)->format('d M Y H:i') }}</td>
                                        <td class="{{ $td }} font-mono font-semibold">{{ $h->file_number }}</td>
                                        <td class="{{ $td }}">{{ ['full' => 'Full file', 'lf' => 'LF Scan', 'ic' => 'Index Cards'][$h->upload_type] ?? $h->upload_type }}</td>
                                        <td class="{{ $td }}">{{ $h->pages }}</td><td class="{{ $td }}">{{ $h->uploaded_by_name ?? '—' }}</td></tr>
                                @empty
                                    <tr><td colspan="6" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">No uploads yet. Each migration will be logged here.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aggregated Summary --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Blind Scanning Summary</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Uploads from the last {{ (int) config('kamma.edms.summary_days', 30) }} days, one row per file folder, with how many pages have moved on to Scan Upload. Click a row to list its files.</p>
                </div>
                <a href="{{ route('dms.blind') }}" class="{{ $btn }}">Refresh</a>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th class="{{ $th }} w-10">S/N</th><th class="{{ $th }} w-10"></th><th class="{{ $th }}">File Number</th><th class="{{ $th }}">Date Uploaded</th>
                            <th class="{{ $th }}">Registry</th><th class="{{ $th }}">A4</th><th class="{{ $th }}">A3</th><th class="{{ $th }}">Total</th>
                            <th class="{{ $th }}">Uploaded by</th><th class="{{ $th }}">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($summary as $s)
                            @php $pct = $s->total ? (int) round($s->moved / $s->total * 100) : 0; @endphp
                            <tr class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40" @click="tab = 'server'; openFolder(@js(['id' => $s->file_indexing_id, 'name' => basename($s->folder)])); window.scrollTo({ top: 0, behavior: 'smooth' })">
                                <td class="{{ $td }} text-slate-400">{{ $loop->iteration }}</td>
                                <td class="{{ $td }}"><i data-lucide="folder" class="h-4 w-4 text-amber-500"></i></td>
                                <td class="{{ $td }} font-mono font-semibold">{{ $s->file_number }}</td>
                                <td class="{{ $td }} whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($s->last_at)->format('d M Y') }}</td>
                                <td class="{{ $td }}">KAMMA</td><td class="{{ $td }}">{{ $s->a4 }}</td><td class="{{ $td }}">{{ $s->a3 }}</td><td class="{{ $td }} font-semibold">{{ $s->total }}</td>
                                <td class="{{ $td }}">{{ $s->uploaded_by_name ?? '—' }}</td>
                                <td class="{{ $td }} min-w-[9rem]">
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div></div>
                                    <span class="text-[11px] text-slate-500">{{ $s->moved }} of {{ $s->total }} in Scan Upload</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">No blind scans in the last {{ (int) config('kamma.edms.summary_days', 30) }} days.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Progress Modal (KLAES) --}}
        <template x-teleport="body">
            <div x-show="busy" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Migrating…</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400" x-text="file ? 'To ' + file.file_number : ''"></p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-indigo-600 transition-all" :style="`width: ${progress}%`"></div></div>
                    <p class="mt-2 text-center text-sm text-slate-500" x-text="progress + '%'"></p>
                    <p class="text-center text-xs text-slate-500" x-text="detail"></p>
                    <div class="mt-3 flex justify-center"><button type="button" @click="stop = true" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"><i data-lucide="ban" class="mr-1 inline h-3.5 w-3.5"></i>Cancel after this batch</button></div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            function blindScan(cfg) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const icons = () => setTimeout(() => window.lucide && lucide.createIcons(), 0);
                return {
                    cfg, file: null, typeOn: false, type: '', picked: [], topFolder: '', tab: 'server',
                    busy: false, stop: false, progress: 0, detail: '', error: '', done: '',
                    open: null, pages: [], loading: false, preview: null,
                    get ready() { return this.file && this.type && this.picked.length > 0 },
                    get status() {
                        if (! this.picked.length) return this.type && this.type !== 'full' ? 'No images selected.' : 'No folder selected.';
                        return this.picked.length + (this.picked.length === 1 ? ' file' : ' files') + ' ready to migrate' + (this.topFolder ? ' from ' + this.topFolder : '') + '.';
                    },
                    get folderWarning() {
                        if (! this.file || ! this.topFolder) return '';
                        const norm = (s) => String(s).toUpperCase().replace(/[^A-Z0-9]/g, '');
                        return norm(this.topFolder) === norm(this.file.file_number) ? '' : 'The folder name (' + this.topFolder + ') does not match the file number ' + this.file.file_number + '. Check it is the right folder.';
                    },
                    clearPicked() { this.picked = []; this.topFolder = ''; if (this.$refs.folder) this.$refs.folder.value = ''; if (this.$refs.lf) this.$refs.lf.value = ''; },
                    fmt(b) { for (const u of ['B', 'KB', 'MB', 'GB']) { if (b < 1024) return (Math.round(b * 10) / 10) + ' ' + u; b /= 1024; } return b + ' TB'; },
                    pick(list, isFolder) {
                        this.error = ''; this.done = '';
                        const ok = [], skipped = [];
                        for (const f of Array.from(list)) {
                            const ext = (f.name.split('.').pop() || '').toLowerCase();
                            if (! cfg.extensions.includes(ext)) { skipped.push(f.name); continue; }
                            if (f.size > cfg.maxKb * 1024) { skipped.push(f.name + ' (too large)'); continue; }
                            // The path inside the picked folder (A4/page.jpg), without the folder itself.
                            const rel = isFolder && f.webkitRelativePath ? f.webkitRelativePath.split('/').slice(1).join('/') : f.name;
                            ok.push({ file: f, path: rel });
                        }
                        this.topFolder = isFolder && list.length && list[0].webkitRelativePath ? list[0].webkitRelativePath.split('/')[0] : '';
                        this.picked = ok;
                        if (skipped.length) this.error = skipped.length + ' file(s) left out (not a scan image or PDF, or too large): ' + skipped.slice(0, 5).join(', ');
                    },
                    async migrate() {
                        if (! this.ready) return;
                        this.busy = true; this.stop = false; this.progress = 0; this.error = ''; this.done = '';
                        const batch = new Date().toISOString().replace(/\D/g, '').slice(0, 14) + '-' + Math.random().toString(36).slice(2, 6);
                        const total = this.picked.length; const sum = { saved: 0, skipped: 0, renamed: 0, rejected: 0, failed: 0 };
                        let sent = 0, folder = '';
                        try {
                            for (let i = 0; i < total; i += cfg.batch) {
                                if (this.stop) break;
                                const part = this.picked.slice(i, i + cfg.batch);
                                const fd = new FormData();
                                fd.append('file_indexing_id', this.file.id); fd.append('upload_type', this.type); fd.append('batch', batch);
                                part.forEach((p) => { fd.append('files[]', p.file, p.file.name); fd.append('paths[]', p.path); });
                                this.detail = 'Batch ' + (i / cfg.batch + 1) + ' of ' + Math.ceil(total / cfg.batch);
                                const res = await fetch(cfg.migrate, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
                                const data = await res.json().catch(() => ({}));
                                if (! res.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Migration failed.'));
                                for (const k of Object.keys(sum)) sum[k] += data[k] || 0;
                                folder = data.folder || folder;
                                sent += part.length; this.progress = Math.round(sent / total * 100);
                            }
                            this.done = sum.saved + ' page(s) migrated to ' + folder + '.'
                                + (sum.skipped ? ' ' + sum.skipped + ' already there (skipped).' : '')
                                + (sum.renamed ? ' ' + sum.renamed + ' kept as a new copy, "(2)".' : '')
                                + (sum.rejected ? ' ' + sum.rejected + ' refused (wrong folder for this upload type, or not a scan).' : '')
                                + (sum.failed ? ' ' + sum.failed + ' failed: send them again.' : '')
                                + (this.stop ? ' Stopped after ' + sent + ' of ' + total + '.' : '')
                                + ' Refresh to see it in the Server Browser.';
                            this.clearPicked();
                        } catch (e) {
                            this.error = e.message + (sent ? ' ' + sent + ' file(s) were sent before the error.' : '');
                        } finally { this.busy = false; icons(); }
                    },
                    async openFolder(f) {
                        if (! f.id) return;
                        this.open = f; this.pages = []; this.preview = null; this.loading = true;
                        try {
                            const r = await fetch(cfg.folder.replace('__ID__', f.id), { headers: { Accept: 'application/json' } });
                            this.pages = (await r.json()).pages || [];
                        } finally { this.loading = false; icons(); }
                    },
                    async remove(p) {
                        if (! confirm('Delete ' + p.path + '? It moves to the EDMS trash and can be uploaded again.')) return;
                        const res = await fetch(p.delete_url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
                        const data = await res.json().catch(() => ({}));
                        if (! res.ok) { alert(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not delete the file.')); return; }
                        this.pages = this.pages.filter((x) => x.id !== p.id);
                        if (this.preview && this.preview.id === p.id) this.preview = null;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
