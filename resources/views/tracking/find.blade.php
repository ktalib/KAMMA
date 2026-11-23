{{-- Track File (Archive), copied from KLAES (track-file-archive): Archive Search with File Number / QR Scan, and the
     colour-tiled File Details (Tracking ID, Land Use, Origin, Destination, Rack, Shelf, Full Rack Shelf, File Log
     History) with Movement History. Data: KAMMA's tracked files. --}}
<x-app-layout title="Track File (Archive)" :bare="true">
    <div x-data="trackArchive(@js(['lookupUrl' => route('tracking.find.lookup'), 'filesUrl' => route('tracking.lookup')]))">
        <div class="border-b border-gray-200 bg-white px-6 py-5 dark:border-slate-800 dark:bg-slate-900">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Track File (Archive)</h1>
            <p class="text-gray-600 dark:text-slate-400">Search and track archived files in the system</p>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Archive Search</h2>
                <div class="mt-4 inline-flex overflow-hidden rounded-md border border-gray-300 dark:border-slate-700">
                    <button type="button" @click="mode = 'number'" :class="mode === 'number' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 dark:bg-slate-800 dark:text-slate-200'" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold"><i data-lucide="hash" class="h-4 w-4"></i> File Number</button>
                    <button type="button" @click="startScan()" :class="mode === 'scan' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 dark:bg-slate-800 dark:text-slate-200'" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold"><i data-lucide="qr-code" class="h-4 w-4"></i> QR Scan</button>
                </div>

                <div x-show="mode === 'number'" class="mt-5 space-y-4">
                    <div class="relative">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">File Number</label>
                        <div class="flex">
                            <input :value="fileNumber" readonly @click="openPicker()" placeholder="e.g. RES-2016-452" class="w-full cursor-pointer rounded-l-md border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <button type="button" @click="openPicker()" class="rounded-r-md border border-l-0 border-gray-300 px-3 text-gray-500 hover:text-gray-800 dark:border-slate-700" aria-label="Select file"><i data-lucide="search" class="h-4 w-4"></i></button>
                        </div>
                        <div x-show="picker" x-cloak @click.outside="picker = false" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <input x-ref="pq" x-model="pq" @input.debounce.300ms="findFiles()" placeholder="Type to find the file…" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <template x-for="f in files" :key="f.id">
                                <button type="button" @click="choose(f.label)" class="block w-full px-2 py-2 text-left hover:bg-blue-50 dark:hover:bg-slate-800">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-slate-100" x-text="f.label"></span>
                                    <span class="block text-xs text-gray-500" x-text="f.sub"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">File Name</label>
                        <input :value="d.file_title || ''" readonly placeholder="File name will appear here..." class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                        <p class="mt-1 text-xs text-gray-500">Auto-filled when a file number is selected</p>
                    </div>
                    <button type="button" @click="search()" :disabled="! fileNumber" class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><i data-lucide="search" class="h-4 w-4"></i> Search Archive</button>
                </div>

                <div x-show="mode === 'scan'" x-cloak class="mt-5 space-y-3">
                    <div id="archive-qr-reader" class="overflow-hidden rounded-md border border-gray-200 dark:border-slate-700"></div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Point the camera at the file's QR label, or use a scanner in the box below.</p>
                    <input x-model="scanned" @keydown.enter.prevent="useScan()" placeholder="KMA-TRK-…" class="w-full rounded-md border border-gray-300 px-3 py-2.5 font-mono text-sm uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <p x-show="message" x-text="message" class="mt-4 text-sm text-red-600"></p>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100">File Details</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ([['tracking_id', 'Tracking ID', 'hash', 'border-blue-200 bg-blue-50', 'bg-blue-600', 'Not Selected'], ['land_use', 'Land Use', 'map-pin', 'border-green-200 bg-green-50', 'bg-green-600', '-'], ['origin', 'Origin (Unit)', 'file-symlink', 'border-purple-200 bg-purple-50', 'bg-purple-600', '-'], ['destination', 'Destination (Location)', 'user-round', 'border-cyan-200 bg-cyan-50', 'bg-cyan-600', '-'], ['rack', 'Rack', 'layers', 'border-orange-200 bg-orange-50', 'bg-orange-600', '-'], ['shelf_no', 'Shelf', 'package', 'border-amber-200 bg-amber-50', 'bg-amber-600', '-']] as [$k, $l, $i, $box, $pill, $empty])
                        <div class="rounded-lg border {{ $box }} p-3 dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300"><i data-lucide="{{ $i }}" class="h-4 w-4"></i> {{ $l }}</div>
                            <div class="rounded-full {{ $pill }} px-3 py-1 text-center text-sm font-semibold text-white" x-text="d.{{ $k }} || '{{ $empty }}'"></div>
                        </div>
                    @endforeach
                    <div class="col-span-2 rounded-lg border border-indigo-200 bg-indigo-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300"><i data-lucide="archive" class="h-4 w-4"></i> Full Rack Shelf</div>
                        <div class="rounded-full bg-indigo-600 px-3 py-1 text-center text-sm font-semibold text-white" x-text="d.full_rack_shelf || '-'"></div>
                    </div>
                    <div class="col-span-2 rounded-lg border border-gray-200 p-3 dark:border-slate-700">
                        <div class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-slate-300"><i data-lucide="info" class="h-4 w-4"></i> File Log History</div>
                        <div class="rounded-full bg-gray-700 px-3 py-1 text-center text-sm font-semibold text-white" x-text="d.found ? (d.log_count + ' log ' + (d.log_count === 1 ? 'entry' : 'entries')) : 'Ready'"></div>
                    </div>
                </div>
                <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                    <div class="flex items-center justify-between text-sm"><span class="flex items-center gap-2 font-semibold uppercase tracking-wide text-gray-700 dark:text-slate-300"><i data-lucide="git-branch" class="h-4 w-4"></i> Movement History</span><span class="text-xs text-gray-500" x-text="'Last updated: ' + (d.last_updated || '--')"></span></div>
                    <template x-if="! (d.movements && d.movements.length)"><p class="mt-2 border-l-2 border-gray-200 pl-4 text-sm text-gray-500 dark:border-slate-700 dark:text-slate-400">Select a file to view movement history.</p></template>
                    <ol class="mt-2 space-y-2 border-l-2 border-gray-200 pl-4 dark:border-slate-700">
                        <template x-for="m in (d.movements || [])" :key="m.at + m.action + m.to">
                            <li class="text-sm"><span class="font-semibold text-gray-900 dark:text-slate-100" x-text="m.action === 'logged' ? 'Logged into ' + m.to : (m.action === 'sent' ? 'Sent from ' + m.from + ' to ' + m.to : 'Received in ' + m.to)"></span>
                                <span class="text-gray-500" x-text="m.purpose ? ' · ' + m.purpose : ''"></span>
                                <div class="text-xs text-gray-500 dark:text-slate-400" x-text="m.by + ' · ' + m.at"></div></li>
                        </template>
                    </ol>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @endpush
    <script>
        window.trackArchive = (cfg) => ({
            mode: 'number', fileNumber: '', d: {}, message: '', picker: false, pq: '', files: [], scanned: '', scanner: null,
            openPicker() { this.picker = true; this.$nextTick(() => this.$refs.pq.focus()); },
            async findFiles() {
                if (this.pq.trim().length < 2) { this.files = []; return; }
                const r = await fetch(`${cfg.filesUrl}?q=${encodeURIComponent(this.pq.trim())}`, { headers: { Accept: 'application/json' } });
                this.files = r.ok ? (await r.json()).files : [];
            },
            choose(n) { this.fileNumber = n; this.picker = false; this.search(); },
            async search() {
                this.message = '';
                const r = await fetch(`${cfg.lookupUrl}?q=${encodeURIComponent(this.fileNumber)}`, { headers: { Accept: 'application/json' } });
                const j = r.ok ? await r.json() : { found: false, message: 'The search did not respond.' };
                if (! j.found) { this.d = {}; this.message = j.message; return; }
                this.d = j;
            },
            startScan() {
                this.mode = 'scan';
                if (this.scanner || ! window.Html5Qrcode) return;
                this.$nextTick(() => {
                    this.scanner = new Html5Qrcode('archive-qr-reader');
                    this.scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, (text) => { this.scanned = text; this.useScan(); }).catch(() => {});
                });
            },
            useScan() {
                if (! this.scanned.trim()) return;
                this.fileNumber = this.scanned.trim().toUpperCase();
                if (this.scanner) { this.scanner.stop().catch(() => {}); this.scanner = null; }
                this.mode = 'number';
                this.search();
            },
        });
    </script>
</x-app-layout>
