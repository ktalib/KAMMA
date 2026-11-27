{{-- DMS > Scanning > Upload Indexed Files, copied from KLAES Scan Uploads (resources/views/scanning/index.blade.php):
     "Document Upload" heading, Today's Uploads / Pending Page Typing / Total Scanned / All Time Documents, the Upload
     Documents and Uploaded Documents tabs, Select Indexed File, "Convert PDF files to images (client-side)", drag and drop,
     Start Upload / Cancel / Upload More / View Uploaded Files. ?url=scmore = DMS Update > Scan More (typed files only).
     2026-11-25 (user: copy the KLAES UI, back end next): the KLAES Select Indexed File dialog (Registry, Upload Method
     Blind scan / Direct upload, Browse Large-Format Scans, Preview file, Load Documents), the Blind Scan Documents
     pop-up over EDMS/BLIND_SCAN/KAMMA_Raw, and on Uploaded Documents the source tabs, paper-size filter, Toggle View and
     Proceed to Page Typing. Blind scan is live since 2026-11-25 (BLIND_SCAN/KAMMA_Raw -> SCAN_UPLOAD/KAMMA); Large-Format and Module Uploads are
     still preview. --}}
<x-app-layout :title="$scanMore ? 'Scan More' : 'Upload Indexed Files'">
    @php
        $th = 'whitespace-nowrap px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300';
        $cards = [
            ["Today's Uploads", $stats['today'], 'Pages uploaded today', 'upload', 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300'],
            ['Pending Page Typing', $stats['pending'], 'Files with pages waiting for typing', 'clock', 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'],
            ['Total Scanned', $stats['files'], 'Files with scanned pages', 'file-check-2', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'],
            ['All Time Documents', $stats['pages'], 'Pages captured in KAMMA', 'infinity', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
        ];
        $statusTone = [
            'Scanned' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'In Progress' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            'PageType More' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
            'Page Typed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'Indexed' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        ];
        $pre = $selected ? ['id' => $selected->id, 'file_number' => $selected->file_number, 'file_title' => $selected->file_title,
            'plot_number' => $selected->plot_number, 'district' => $selected->district, 'lga' => $selected->lga,
            'pages' => $selected->pages_count, 'status' => $dms->status($selected)] : null;
    @endphp

    <div class="space-y-6" x-data="scanUpload(@js([
            'files' => route('dms.scanning.files', $scanMore ? ['typed' => 1] : []),
            'upload' => route('dms.scanning.upload'),
            'pages' => route('dms.scanning.pages', ['indexing' => '__ID__']),
            'typing' => route('dms.pagetyping.type', ['indexing' => '__ID__']),
            'blindFolder' => route('dms.blind.folder', ['indexing' => '__ID__', 'pending' => 1]),
            'blindTake' => route('dms.scanning.blind'),
            'selected' => $pre,
            'scanMore' => $scanMore,
            'tab' => $tab,
            'maxKb' => (int) config('kamma.dms_page_max_kb'),
        ]))" @keydown.escape.window="pickerOpen = false; pagesOpen = false">

        <div class="flex flex-wrap items-center gap-4">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-600/25"><i data-lucide="upload-cloud" class="h-6 w-6"></i></span>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $scanMore ? 'Scan More' : 'Document Upload' }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $scanMore ? 'DMS Update: add newly scanned pages to a file that is already page typed. They show under PageType More.' : 'Manage scanned documents and organise files' }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as [$title, $value, $hint, $icon, $tone])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $title }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ number_format($value) }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b border-slate-200 dark:border-slate-800">
            <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-blue-600 text-blue-700 dark:text-blue-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                    class="-mb-px inline-flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-bold"><i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Documents</button>
            <button type="button" @click="tab = 'uploaded'" :class="tab === 'uploaded' ? 'border-blue-600 text-blue-700 dark:text-blue-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                    class="-mb-px inline-flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-bold"><i data-lucide="files" class="h-4 w-4"></i> Uploaded Documents <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] dark:bg-slate-800">{{ number_format($uploaded->total()) }}</span></button>
        </div>

        {{-- Upload Documents --}}
        <div x-show="tab === 'upload'" class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Upload New Documents</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Select a file and upload its scanned documents</p>
            </div>

            <div class="space-y-5 p-6">
                {{-- Select Indexed File --}}
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Select Indexed File</p>
                        <template x-if="file">
                            <p class="mt-0.5 truncate text-sm">
                                <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400" x-text="file.file_number"></span>
                                <span class="text-slate-600 dark:text-slate-300" x-text="' · ' + [file.file_title, file.plot_number && ('Plot ' + file.plot_number), file.district, file.lga].filter(Boolean).join(' · ')"></span>
                                <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700" x-text="file.pages + ' page(s) on file'"></span>
                            </p>
                        </template>
                        <p x-show="! file" class="text-xs text-slate-500 dark:text-slate-400">Only files matched in the File Indexing Assistant can take scans.</p>
                    </div>
                    <button type="button" @click="openPicker()" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="folder-open" class="h-4 w-4"></i><span x-text="file ? 'Change File' : 'Select File'"></span>
                    </button>
                </div>

                <div class="rounded-2xl border-2 border-dashed border-slate-200 p-5 dark:border-slate-700">
                    <p class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200"><i data-lucide="upload-cloud" class="h-4 w-4 text-blue-600"></i> Upload Scanned Documents</p>

                    <div class="mb-4 grid gap-4 lg:grid-cols-3">
                        <label class="flex items-start gap-3 rounded-xl border-l-4 border-blue-500 bg-blue-50 px-4 py-3 lg:col-span-2 dark:bg-blue-500/10">
                            <input type="checkbox" x-model="convert" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Convert PDF files to images (client-side)</span>
                                <span class="ml-2 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white">Auto-activated</span>
                                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-300">PDF files are turned into one JPG per page in your browser before upload, so every page can be typed.</span>
                                <span class="mt-0.5 block text-[11px] text-blue-600 dark:text-blue-300" x-text="pdfReady ? 'PDF conversion ready' : 'Loading PDF converter…'"></span>
                            </span>
                        </label>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Paper size</p>
                            <div class="mt-2 flex gap-2">
                                <template x-for="p in ['A4', 'A3']" :key="p">
                                    <button type="button" @click="paper = p" :class="paper === p ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700'"
                                            class="flex-1 rounded-lg border px-3 py-1.5 text-sm font-bold" x-text="p"></button>
                                </template>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">A3 for plans, layouts and large drawings.</p>
                        </div>
                    </div>

                    <div @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drag = false; addFiles($event.dataTransfer.files)"
                         :class="drag ? 'border-blue-500 bg-blue-50/60 dark:bg-blue-500/10' : 'border-slate-200 dark:border-slate-700'"
                         class="rounded-xl border-2 border-dashed px-6 py-10 text-center transition">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><i data-lucide="file-up" class="h-6 w-6"></i></span>
                        <p class="mt-4 text-lg font-semibold text-slate-800 dark:text-slate-100">Drag and drop scanned documents here</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">or click to browse files on your computer</p>
                        <input x-ref="input" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.tif,.tiff,.webp" class="hidden" @change="addFiles($event.target.files); $event.target.value = ''">
                        <button type="button" @click="$refs.input.click()" :disabled="! file || busy"
                                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300 dark:disabled:bg-blue-900">
                            <i data-lucide="upload" class="h-4 w-4"></i> Browse Files
                        </button>
                        <p x-show="! file" class="mt-3 text-sm font-medium text-rose-600 dark:text-rose-400">Please select an indexed file first</p>
                        <p class="mt-2 text-xs text-slate-400">Supported: PDF, JPG, PNG, GIF, BMP, TIFF, WebP</p>
                    </div>

                    {{-- Queue --}}
                    <div x-show="queue.length" class="mt-5">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><span x-text="queue.length"></span> page(s) ready to upload</p>
                            <p x-show="converting" class="text-xs font-semibold text-blue-600 dark:text-blue-300" x-text="converting"></p>
                        </div>
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-8">
                            <template x-for="(q, i) in queue" :key="q.key">
                                <div class="group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <img x-show="q.preview" :src="q.preview" alt="" class="h-28 w-full object-cover">
                                    <div x-show="! q.preview" class="flex h-28 items-center justify-center text-slate-400"><i data-lucide="file" class="h-8 w-8"></i></div>
                                    <p class="truncate px-2 py-1 text-[10px] text-slate-600 dark:text-slate-300" x-text="(i + 1) + '. ' + q.file.name"></p>
                                    <button type="button" @click="remove(i)" x-show="! busy" class="absolute right-1 top-1 hidden rounded-full bg-rose-600 p-1 text-white group-hover:block" aria-label="Remove"><i data-lucide="x" class="h-3 w-3"></i></button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="busy || progress" class="mt-5">
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-blue-600 transition-all" :style="`width: ${progress}%`"></div></div>
                        <p class="mt-1 text-xs text-slate-500" x-text="progress + '% uploaded'"></p>
                    </div>
                    <p x-show="error" class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="error"></p>
                    <p x-show="done" class="mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><i data-lucide="check-circle-2" class="h-4 w-4"></i><span x-text="done"></span></p>
                </div>

                <div class="flex flex-wrap justify-center gap-3 pt-2">
                    <button type="button" @click="start()" :disabled="! file || ! queue.length || busy || converting"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"><i data-lucide="upload" class="h-4 w-4"></i> Start Upload</button>
                    <button type="button" @click="cancel()" :disabled="busy"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50"><i data-lucide="x-circle" class="h-4 w-4"></i> Cancel</button>
                    <button type="button" @click="more()" :disabled="busy"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="refresh-cw" class="h-4 w-4"></i> Upload More</button>
                    <button type="button" @click="file ? showPages(file) : (tab = 'uploaded')"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"><i data-lucide="check-circle-2" class="h-4 w-4"></i> View Uploaded Files</button>
                    <a x-show="file && (file.pages || done)" :href="file ? cfg.typing.replace('__ID__', file.id) : '#'"
                       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"><i data-lucide="type" class="h-4 w-4"></i> Go to Page Typing</a>
                </div>
            </div>
        </div>

        {{-- Uploaded Documents --}}
        <div x-show="tab === 'uploaded'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">Uploaded Documents</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Files with scanned pages, latest first.</p>
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="uploaded">
                    @if ($scanMore)<input type="hidden" name="url" value="scmore"><input type="hidden" name="from" value="{{ request('from') }}">@endif
                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 dark:bg-slate-800/50">
                        <label for="paper-size-filter" class="whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-slate-200">Filter:</label>
                        <select id="paper-size-filter" name="paper" onchange="this.form.submit()" class="rounded-lg border-slate-300 py-1 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @foreach (['' => 'All Sizes', 'A4' => 'A4', 'A5' => 'A5', 'A3' => 'A3', 'Letter' => 'Letter', 'Legal' => 'Legal', 'Custom' => 'Custom'] as $v => $l)
                                <option value="{{ $v }}" @selected($paper === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Search files..." class="w-72 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <button type="button" @click="view = view === 'list' ? 'folder' : 'list'" class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="layout-grid" class="h-4 w-4"></i> Toggle View
                    </button>
                </form>
            </div>
            {{-- Source tabs: where the pages came from (KLAES ScanSource). Module Uploads = pages other KAMMA modules upload. --}}
            <nav class="flex flex-wrap gap-2 border-b border-slate-100 px-5 py-3 dark:border-slate-800" aria-label="Document source">
                @foreach (['scanned' => ['Scanned Files', 'scan-line', $uploaded->total()], 'uploads' => ['Module Uploads', 'blocks', 0]] as $key => [$lbl, $ico, $n])
                    <a href="{{ route('dms.scanning.index', array_filter(['tab' => 'uploaded', 'source' => $key, 'url' => $scanMore ? 'scmore' : null, 'from' => $scanMore ? request('from') : null])) }}"
                       class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium {{ $source === $key ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' }}"
                       @if ($source === $key) aria-current="page" @endif>
                        <i data-lucide="{{ $ico }}" class="h-4 w-4"></i> {{ $lbl }}
                        <span class="rounded-full px-2 text-xs {{ $source === $key ? 'bg-white text-blue-700' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ number_format($n) }}</span>
                    </a>
                @endforeach
            </nav>
            @if ($source === 'uploads')
                <div class="p-6"><x-ui.empty icon="blocks" title="No module uploads yet">Pages uploaded from other KAMMA modules (KSI photos, memos, bills) will be listed here once those uploads go through the DMS.</x-ui.empty></div>
            @else
            <div x-show="view === 'list'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="{{ $th }}">S/N</th><th class="{{ $th }}">File Number</th><th class="{{ $th }}">Title</th>
                            <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th>
                            <th class="{{ $th }}">Pages</th><th class="{{ $th }}">Typed</th><th class="{{ $th }}">Status</th>
                            <th class="{{ $th }} sticky right-0 bg-slate-50/95 dark:bg-slate-800/95"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($uploaded as $f)
                            @php $st = $dms->status($f); @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="{{ $td }} text-slate-400">{{ $uploaded->firstItem() + $loop->index }}</td>
                                <td class="{{ $td }} font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $f->file_number }}</td>
                                <td class="{{ $td }} max-w-[16rem] truncate font-medium" title="{{ $f->file_title }}">{{ $f->file_title ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->plot_number ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->district ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->lga ?? '—' }}</td>
                                <td class="{{ $td }} font-bold">{{ $f->pages_count }}</td>
                                <td class="{{ $td }}">{{ $f->typed_count }}</td>
                                <td class="{{ $td }}"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider {{ $statusTone[$st] }}">{{ $st }}</span></td>
                                <td class="{{ $td }} sticky right-0 bg-white text-right dark:bg-slate-900">
                                    <x-row-actions>
                                        <button type="button" @click="showPages(@js(['id' => $f->id, 'file_number' => $f->file_number]))" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="images" class="h-4 w-4 text-slate-400"></i> View pages</button>
                                        <x-row-action :href="route('dms.scanning.index', array_filter(['file' => $f->id, 'url' => $scanMore ? 'scmore' : null, 'from' => $scanMore ? request('from') : null]))"><x-slot:icon><i data-lucide="upload-cloud" class="h-4 w-4"></i></x-slot:icon> Upload more pages</x-row-action>
                                        <x-row-action :href="route('dms.pagetyping.type', $f)"><x-slot:icon><i data-lucide="type" class="h-4 w-4"></i></x-slot:icon> Page typing</x-row-action>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-12 text-center text-sm text-slate-500">{{ $search !== '' || $paper !== '' ? 'No uploaded file fits that search.' : 'Nothing uploaded yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Folder view: one card per file, as KLAES's Toggle View. --}}
            <div x-show="view === 'folder'" x-cloak class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($uploaded as $f)
                    @php $st = $dms->status($f); @endphp
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"><i data-lucide="folder" class="h-5 w-5"></i></span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $statusTone[$st] }}">{{ $st }}</span>
                        </div>
                        <p class="mt-3 font-mono text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ $f->file_number }}</p>
                        <p class="truncate text-sm text-slate-700 dark:text-slate-200" title="{{ $f->file_title }}">{{ $f->file_title ?? '—' }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ collect([$f->plot_number ? 'Plot '.$f->plot_number : null, $f->district, $f->lga])->filter()->implode(' · ') ?: '—' }}</p>
                        <p class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $f->pages_count }} page(s) · {{ $f->typed_count }} typed</p>
                        <button type="button" @click="showPages(@js(['id' => $f->id, 'file_number' => $f->file_number]))" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 hover:underline dark:text-blue-300"><i data-lucide="images" class="h-3.5 w-3.5"></i> View pages</button>
                    </div>
                @empty
                    <p class="col-span-full py-10 text-center text-sm text-slate-500">{{ $search !== '' || $paper !== '' ? 'No uploaded file fits that search.' : 'Nothing uploaded yet.' }}</p>
                @endforelse
            </div>
            @if ($uploaded->hasPages())<div class="border-t border-slate-100 px-5 py-3 dark:border-slate-800">{{ $uploaded->links() }}</div>@endif
            @endif
            {{-- Batch Actions --}}
            @if ($uploaded->total() > 0)
                <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/40">
                    <a href="{{ route('dms.pagetyping.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="arrow-right" class="h-4 w-4"></i> Proceed to Page Typing</a>
                </div>
            @endif
        </div>

        {{-- Select Indexed File pop-up --}}
        <template x-teleport="body">
            <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 pt-20 backdrop-blur-sm">
                <div @click.outside="pickerOpen = false" class="w-full max-w-5xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="rounded-lg bg-blue-100 p-2 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><i data-lucide="folder-search" class="h-5 w-5"></i></span>
                            <div><h2 class="font-bold text-slate-900 dark:text-white">Select Indexed File</h2><p class="text-xs text-slate-500 dark:text-slate-400">Click on a file to select it</p></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div x-show="pending" x-cloak class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                                <p class="text-[11px] font-semibold text-emerald-900 dark:text-emerald-200">Selected</p>
                                <p class="font-mono text-xs text-emerald-700 dark:text-emerald-300" x-text="pending && pending.file_number"></p>
                            </div>
                            <button type="button" @click="pickerOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                        </div>
                    </div>
                    <div class="space-y-4 p-5">
                      <div class="grid gap-4 lg:grid-cols-2 lg:items-start">
                        <div class="space-y-4">
                            <div>
                                <label for="scan-registry" class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Select Registry <span class="text-rose-500">*</span></label>
                                <select id="scan-registry" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="KAMMA" selected>KAMMA</option></select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">KAMMA's files go to its own folders (EDMS/SCAN_UPLOAD/KAMMA).</p>
                            </div>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input x-ref="pq" type="search" x-model="pq" @input.debounce.300ms="loadFiles()" placeholder="File number, title, plot, district, LGA…"
                                   class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                        <ul class="mt-3 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                            <template x-for="f in picks" :key="f.id">
                                <li><button type="button" @click="pending = f" @dblclick="pending = f; confirmPick()" :class="pending && pending.id === f.id ? 'bg-blue-50 dark:bg-slate-800' : ''" class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-blue-50 dark:hover:bg-slate-800">
                                    <span class="min-w-0">
                                        <span class="block font-mono text-sm font-bold text-emerald-700 dark:text-emerald-400" x-text="f.file_number"></span>
                                        <span class="block truncate text-xs text-slate-600 dark:text-slate-300" x-text="[f.file_title, f.plot_number && ('Plot ' + f.plot_number), f.district, f.lga].filter(Boolean).join(' · ')"></span>
                                    </span>
                                    <span class="shrink-0 text-[10px] font-bold uppercase text-slate-400" x-text="f.pages + ' pages · ' + f.status"></span>
                                </button></li>
                            </template>
                            <li x-show="! picks.length" class="px-4 py-6 text-center text-sm text-slate-500" x-text="cfg.scanMore ? 'No page-typed file matches. Scan More is for files already page typed.' : 'No indexed file matches. Match it first in the File Indexing Assistant.'"></li>
                        </ul>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <div><p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Upload Method</p><p class="text-xs text-slate-500 dark:text-slate-400">Choose how these documents will be added to the file.</p></div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach (['blind' => ['Blind scan', 'scan-line', 'Browse documents from the KAMMA blind-scan folder.'], 'direct' => ['Direct upload', 'upload', 'Documents are added from this workstation.']] as $v => [$l, $ico, $d])
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition" :class="method === '{{ $v }}' ? 'border-blue-500 bg-blue-50/60 dark:bg-blue-500/10' : 'border-slate-200 hover:border-blue-400 dark:border-slate-700'">
                                            <input type="radio" value="{{ $v }}" x-model="method" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500">
                                            <span><span class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100"><i data-lucide="{{ $ico }}" class="h-4 w-4"></i>{{ $l }}</span><span class="text-xs text-slate-500 dark:text-slate-400">{{ $d }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="space-y-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                                    <button type="button" :disabled="! pending" @click="lfNote = 'Large-format browsing opens with the back end (preview).'" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><i data-lucide="image" class="h-4 w-4"></i>Browse Large-Format Scans</button>
                                    <p class="break-all text-xs text-slate-500 dark:text-slate-400">Master LFS Folder: storage/EDMS/UPLOAD/KAMMA/LFS</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="lfNote || (pending ? 'LF images are added to the upload pages of ' + pending.file_number + '.' : 'Select an indexed file first. LF images are added to its upload pages.')"></p>
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <div><p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Preview</p><p class="text-xs text-slate-500 dark:text-slate-400">The file-type instruction is usually written on the file's cover.</p></div>
                                    <button type="button" :disabled="! pending" @click="coverOpen = ! coverOpen" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><i data-lucide="eye" class="h-4 w-4"></i>Preview file</button>
                                </div>
                                <div x-show="coverOpen && pending" x-cloak class="mt-3 flex min-h-[140px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400"
                                     x-text="pending && pending.pages ? pending.pages + ' page(s) on file: open View Uploaded Files to see them.' : 'No pages on this file yet.'"></div>
                            </div>
                        </div>
                      </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <button type="button" @click="pickerOpen = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Cancel</button>
                        <button type="button" @click.stop="confirmPick()" :disabled="! pending" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><i data-lucide="check" class="h-4 w-4"></i> Load Documents</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Blind Scan Documents (KLAES blind_scan_modal): the file's pages waiting in EDMS/BLIND_SCAN/KAMMA_Raw. Tick and Add:
             they are copied into EDMS/SCAN_UPLOAD/KAMMA and become the file's scanned pages. --}}
        <template x-teleport="body">
            <div x-show="blindOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 pt-16 backdrop-blur-sm">
                <div @click.outside="blindOpen = false" class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="rounded-lg bg-indigo-100 p-2 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><i data-lucide="scan-line" class="h-5 w-5"></i></span>
                            <div><h2 class="font-bold text-slate-900 dark:text-white">Blind Scan Documents</h2><p class="font-mono text-xs text-slate-600 dark:text-slate-300" x-text="blindLoading ? 'Searching for folder...' : (blindFolder || (file ? file.file_number : ''))"></p></div>
                        </div>
                        <button type="button" @click="blindOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <div class="space-y-4 p-5">
                        <p x-show="blindLoading" class="text-sm text-slate-600 dark:text-slate-300">Scanning blind scan storage...</p>
                        <div x-show="! blindLoading && blind.length" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-500/10"><p class="text-xs text-indigo-700 dark:text-indigo-300">Total Files</p><p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100" x-text="blind.length"></p></div>
                            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-500/10"><p class="text-xs text-indigo-700 dark:text-indigo-300">Paper Sizes</p><p class="text-sm font-bold text-indigo-900 dark:text-indigo-100" x-text="countBy('paper')"></p></div>
                            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-500/10"><p class="text-xs text-indigo-700 dark:text-indigo-300">Document Types</p><p class="text-sm font-bold text-indigo-900 dark:text-indigo-100" x-text="countBy('type')"></p></div>
                        </div>
                        <div x-show="! blindLoading && ! blind.length" class="rounded-lg border border-dashed border-slate-300 p-5 text-center text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                            <p class="font-semibold text-slate-900 dark:text-white">No blind scan documents found.</p>
                            <p class="mt-1">Ask the scanning team to confirm the folder exists in EDMS/BLIND_SCAN/KAMMA_Raw (DMS &gt; Blind Scanning).</p>
                        </div>
                        <div x-show="blind.length" class="max-h-96 overflow-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-sm"><thead class="sticky top-0 bg-slate-50 dark:bg-slate-800"><tr>
                                <th class="{{ $th }}"><input type="checkbox" :checked="ticked.length === blind.length" @change="ticked = $event.target.checked ? blind.map((b) => b.id) : []" aria-label="Tick all" class="rounded border-slate-300 text-blue-600"></th>
                                @foreach (['File Name', 'Relative Path', 'Paper', 'Type', 'Size'] as $h)<th class="{{ $th }}">{{ $h }}</th>@endforeach
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="b in blind" :key="b.id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                        <td class="{{ $td }}"><input type="checkbox" :value="b.id" x-model.number="ticked" class="rounded border-slate-300 text-blue-600"></td>
                                        <td class="{{ $td }}"><a :href="b.url" target="_blank" class="text-blue-700 hover:underline dark:text-blue-300" x-text="b.name"></a></td>
                                        <td class="{{ $td }} font-mono text-xs" x-text="b.path"></td>
                                        <td class="{{ $td }}" x-text="b.paper"></td>
                                        <td class="{{ $td }}" x-text="b.type"></td>
                                        <td class="{{ $td }}" x-text="Math.round(b.size / 1024) + ' KB'"></td>
                                    </tr>
                                </template>
                            </tbody></table>
                        </div>
                        <p x-show="blindError" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="blindError"></p>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <span class="text-xs text-slate-500" x-text="ticked.length + ' of ' + blind.length + ' ticked'"></span>
                        <div class="flex gap-3">
                            <button type="button" @click="blindOpen = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Cancel</button>
                            <button type="button" @click="addBlind()" :disabled="! ticked.length || busy" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><i data-lucide="plus" class="h-4 w-4"></i> Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Pages of a file --}}
        <template x-teleport="body">
            <div x-show="pagesOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm">
                <div @click.outside="pagesOpen = false" class="my-8 w-full max-w-5xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div><h2 class="font-bold text-slate-900 dark:text-white">Uploaded pages</h2><p class="font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400" x-text="pagesOf"></p></div>
                        <button type="button" @click="pagesOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-4 lg:grid-cols-6">
                        <template x-for="p in pageList" :key="p.id">
                            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                <a :href="p.url" target="_blank" class="block bg-slate-50 dark:bg-slate-800">
                                    <img x-show="! p.pdf" :src="p.url" loading="lazy" alt="" class="h-36 w-full object-contain">
                                    <div x-show="p.pdf" class="flex h-36 items-center justify-center text-xs font-bold text-rose-600">PDF</div>
                                </a>
                                <div class="space-y-1 p-2 text-[11px]">
                                    <p class="font-bold text-slate-700 dark:text-slate-200" x-text="'Page ' + p.no + ' · ' + p.paper"></p>
                                    <p x-show="p.type" class="truncate text-emerald-700 dark:text-emerald-400" x-text="p.type ? p.type.label + ' (' + p.type.code + ')' : ''"></p>
                                    <p x-show="! p.type" class="text-amber-600" x-text="p.more ? 'Scan More · not typed' : 'Not typed'"></p>
                                    <button type="button" x-show="! p.type" @click="removePage(p)" class="text-rose-600 hover:underline">Remove</button>
                                </div>
                            </div>
                        </template>
                        <p x-show="! pageList.length" class="col-span-full py-8 text-center text-sm text-slate-500">Loading…</p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            function scanUpload(cfg) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const icons = () => setTimeout(() => window.lucide && lucide.createIcons(), 0);
                const CHUNK = 10;
                return {
                    cfg, tab: cfg.tab, file: cfg.selected, convert: true, pdfReady: false, paper: 'A4', drag: false,
                    queue: [], converting: '', busy: false, progress: 0, error: '', done: '',
                    pickerOpen: false, pq: '', picks: [], pagesOpen: false, pagesOf: '', pageList: [],
                    pending: null, method: 'direct', lfNote: '', coverOpen: false, blindOpen: false, view: 'list',
                    blind: [], ticked: [], blindLoading: false, blindError: '', blindFolder: '',
                    init() {
                        if (window.pdfjsLib) {
                            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                            this.pdfReady = true;
                        }
                    },
                    confirmPick() {
                        if (! this.pending) return;
                        this.choose(this.pending);
                        if (this.method === 'blind') this.openBlind();
                    },
                    async openBlind() {
                        this.blindOpen = true; this.blind = []; this.ticked = []; this.blindError = ''; this.blindLoading = true;
                        try {
                            const r = await fetch(cfg.blindFolder.replace('__ID__', this.file.id), { headers: { Accept: 'application/json' } });
                            const data = await r.json();
                            this.blind = data.pages || []; this.blindFolder = data.folder || '';
                            this.ticked = this.blind.map((b) => b.id);
                        } catch (e) { this.blindError = 'Could not read the blind-scan folder.'; }
                        finally { this.blindLoading = false; icons(); }
                    },
                    countBy(key) {
                        const c = {}; this.blind.forEach((b) => { c[b[key]] = (c[b[key]] || 0) + 1; });
                        return Object.entries(c).map(([k, n]) => k + ': ' + n).join(' · ');
                    },
                    async addBlind() {
                        this.busy = true; this.blindError = '';
                        try {
                            const res = await fetch(cfg.blindTake, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                                body: JSON.stringify({ file_indexing_id: this.file.id, ids: this.ticked }) });
                            const data = await res.json().catch(() => ({}));
                            if (! res.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not add the pages.'));
                            this.file.pages = (this.file.pages || 0) + (data.count || 0);
                            this.done = data.message; this.blindOpen = false;
                        } catch (e) { this.blindError = e.message; }
                        finally { this.busy = false; icons(); }
                    },
                    openPicker() { this.pending = this.file; this.lfNote = ''; this.coverOpen = false; this.pickerOpen = true; this.loadFiles(); this.$nextTick(() => this.$refs.pq.focus()); icons(); },
                    async loadFiles() {
                        const r = await fetch(cfg.files + (cfg.files.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(this.pq), { headers: { Accept: 'application/json' } });
                        this.picks = (await r.json()).files || [];
                    },
                    choose(f) { this.file = f; this.pickerOpen = false; this.queue = []; this.done = ''; this.error = ''; this.progress = 0; icons(); },
                    async addFiles(list) {
                        if (! this.file) { this.error = 'Please select an indexed file first.'; return; }
                        this.error = ''; this.done = '';
                        for (const f of Array.from(list)) {
                            if (f.size > cfg.maxKb * 1024) { this.error = f.name + ' is larger than ' + Math.round(cfg.maxKb / 1024) + ' MB.'; continue; }
                            const isPdf = f.type === 'application/pdf' || /\.pdf$/i.test(f.name);
                            if (isPdf && this.convert && this.pdfReady) { await this.pdfToImages(f); continue; }
                            this.queue.push({ key: Math.random(), file: f, preview: /^image\/(jpeg|png|gif|webp|bmp)$/.test(f.type) ? URL.createObjectURL(f) : null, converted: false });
                        }
                        icons();
                    },
                    async pdfToImages(f) {
                        try {
                            const pdf = await pdfjsLib.getDocument({ data: await f.arrayBuffer() }).promise;
                            const base = f.name.replace(/\.pdf$/i, '');
                            for (let n = 1; n <= pdf.numPages; n++) {
                                this.converting = 'Converting ' + f.name + ': page ' + n + ' of ' + pdf.numPages + '…';
                                const page = await pdf.getPage(n);
                                const vp = page.getViewport({ scale: 2 });
                                const canvas = document.createElement('canvas');
                                canvas.width = vp.width; canvas.height = vp.height;
                                await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
                                const blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', 0.88));
                                const img = new File([blob], base + '_page_' + String(n).padStart(3, '0') + '.jpg', { type: 'image/jpeg' });
                                this.queue.push({ key: Math.random(), file: img, preview: URL.createObjectURL(img), converted: true });
                            }
                        } catch (e) {
                            this.error = 'Could not convert ' + f.name + ' (' + e.message + '). It will be uploaded as a PDF.';
                            this.queue.push({ key: Math.random(), file: f, preview: null, converted: false });
                        } finally { this.converting = ''; }
                    },
                    remove(i) { this.queue.splice(i, 1); },
                    async start() {
                        if (! this.file || ! this.queue.length) return;
                        this.busy = true; this.error = ''; this.done = ''; this.progress = 0;
                        const total = this.queue.length; let sent = 0;
                        try {
                            while (this.queue.length) {
                                const part = this.queue.slice(0, CHUNK);
                                const fd = new FormData();
                                fd.append('file_indexing_id', this.file.id);
                                fd.append('paper_size', this.paper);
                                fd.append('converted', part.some((q) => q.converted) ? 1 : 0);
                                part.forEach((q) => fd.append('documents[]', q.file, q.file.name));
                                const res = await fetch(cfg.upload, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
                                const data = await res.json().catch(() => ({}));
                                if (! res.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Upload failed.'));
                                this.queue.splice(0, part.length); sent += part.length;
                                this.progress = Math.round(sent / total * 100);
                            }
                            this.file.pages = (this.file.pages || 0) + sent;
                            this.done = sent + ' page(s) uploaded to ' + this.file.file_number + '.';
                        } catch (e) {
                            this.error = e.message + (sent ? ' ' + sent + ' page(s) were uploaded before the error; the rest are still listed.' : '');
                        } finally { this.busy = false; icons(); }
                    },
                    cancel() { this.queue = []; this.progress = 0; this.error = ''; this.converting = ''; },
                    more() { this.queue = []; this.progress = 0; this.done = ''; this.error = ''; this.$refs.input.click(); },
                    async showPages(f) {
                        this.pagesOf = f.file_number; this.pageList = []; this.pagesOpen = true; icons();
                        const r = await fetch(cfg.pages.replace('__ID__', f.id), { headers: { Accept: 'application/json' } });
                        this.pageList = (await r.json()).pages || [];
                    },
                    async removePage(p) {
                        if (! confirm('Remove page ' + p.no + ' from this file?')) return;
                        const res = await fetch(p.delete_url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
                        const data = await res.json().catch(() => ({}));
                        if (! res.ok) { alert(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not remove the page.')); return; }
                        this.pageList = this.pageList.filter((x) => x.id !== p.id);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
