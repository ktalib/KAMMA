{{-- DMS > Indexing > File Indexing Assistant = KAMMA Shadow File Commissioning, copied from KLAES
     "Match Existing FileNo (Lands)" (resources/views/lands_file_no_matching/index.blade.php): search + Match button,
     Daily / Monthly / Total cards, the matched records table, and the Match pop-up. The briefing's flow: select the
     file number, bring the KLAES indexing record, copy it into KAMMA's indexing table, flag the match on KLAES. --}}
<x-app-layout title="File Indexing Assistant">
    @php
        $th = 'whitespace-nowrap px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300';
        $label = 'mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $ro = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-100';
        $statusTone = [
            'Indexed' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
            'Scanned' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
            'In Progress' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
            'PageType More' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/30',
            'Page Typed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
        ];
    @endphp

    <div class="space-y-6" x-data="shadowFiles(@js([
            'lookup' => route('dms.indexing.lookup'),
            'klaes' => route('dms.indexing.klaes', ['id' => '__ID__']),
            'store' => route('dms.indexing.store'),
            'show' => route('dms.indexing.show', ['indexing' => '__ID__']),
            'officer' => auth()->user()->name,
        ]))" @keydown.escape.window="closeAll()">

        {{-- Heading, search and the Match button (KLAES layout) --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">DMS · Indexing</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">File Indexing Assistant</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">Shadow File Commissioning: pick an existing KLAES file, its KLAES indexing record is copied into KAMMA's indexing and the match is marked on KLAES. Then scan, upload and page type it.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search file, title, plot, district, LGA…"
                           class="w-72 rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                </form>
                <button type="button" @click="openMatch()"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 active:scale-95">
                    <i data-lucide="plus-circle" class="h-4 w-4"></i> Match Existing FileNo
                </button>
            </div>
        </div>

        <div x-show="flash" x-cloak x-transition class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            <i data-lucide="check-circle-2" class="h-4 w-4"></i><span x-text="flash"></span>
        </div>

        {{-- Daily / Monthly / Total (KLAES cards) --}}
        <div class="grid gap-4 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <i data-lucide="calendar-check" class="pointer-events-none absolute -bottom-3 -right-3 h-24 w-24 text-emerald-50 dark:text-emerald-500/5"></i>
                <div class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20"><i data-lucide="calendar-check" class="h-5 w-5"></i></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Daily matching</p>
                        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['today']) }}</p>
                    </div>
                </div>
                <div class="relative mt-4 flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                    <span class="text-slate-400">New matches today</span>
                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">Live update <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span></span>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <i data-lucide="bar-chart-3" class="pointer-events-none absolute -bottom-3 -right-3 h-24 w-24 text-indigo-50 dark:text-indigo-500/5"></i>
                <div class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/20"><i data-lucide="bar-chart-3" class="h-5 w-5"></i></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Monthly matching</p>
                        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['month']) }}</p>
                    </div>
                </div>
                <div class="relative mt-4 flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                    <span class="text-slate-400">Total this month</span>
                    <span class="text-indigo-600 dark:text-indigo-400">{{ strtoupper(now()->format('F Y')) }}</span>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 p-5 text-white shadow-lg shadow-emerald-700/20">
                <i data-lucide="database" class="pointer-events-none absolute -bottom-4 -right-4 h-28 w-28 text-white/10"></i>
                <div class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20"><i data-lucide="database" class="h-5 w-5"></i></span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-100">Total</p>
                        <p class="text-2xl font-black">{{ number_format($stats['total']) }}</p>
                    </div>
                </div>
                <div class="relative mt-4 flex items-center justify-between border-t border-white/15 pt-3 text-[10px] font-black uppercase tracking-widest text-emerald-100">
                    <span>All KAMMA shadow files</span>
                    <span>{{ number_format($stats['scanned']) }} scanned · {{ number_format($stats['typed']) }} typed</span>
                </div>
            </div>
        </div>

        {{-- Matched records --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-slate-100">Matched KAMMA File Records</h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($records->total()) }} {{ $search !== '' ? 'found' : 'files' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="{{ $th }}">S/N</th>
                            <th class="{{ $th }}">File Number</th>
                            <th class="{{ $th }}">Title / Name</th>
                            <th class="{{ $th }}">Plot No.</th>
                            <th class="{{ $th }}">District</th>
                            <th class="{{ $th }}">LGA</th>
                            <th class="{{ $th }}">Location</th>
                            <th class="{{ $th }}">Land Use</th>
                            <th class="{{ $th }}">Matched By</th>
                            <th class="{{ $th }}">Date / Time Matched</th>
                            <th class="{{ $th }}">Pages</th>
                            <th class="{{ $th }}">DMS Status</th>
                            <th class="{{ $th }} sticky right-0 bg-slate-50/95 dark:bg-slate-800/95"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($records as $r)
                            @php $status = $dms->status($r); @endphp
                            <tr class="transition hover:bg-emerald-50/40 dark:hover:bg-slate-800/40">
                                <td class="{{ $td }} text-slate-400">{{ $records->firstItem() + $loop->index }}</td>
                                <td class="{{ $td }} font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $r->file_number }}</td>
                                <td class="{{ $td }} max-w-[16rem] truncate font-semibold text-slate-900 dark:text-slate-100" title="{{ $r->file_title }}">{{ $r->file_title ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $r->plot_number ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $r->district ?? '—' }}</td>
                                <td class="{{ $td }} font-semibold">{{ $r->lga ?? '—' }}</td>
                                <td class="{{ $td }} max-w-[14rem] truncate text-xs" title="{{ $r->location }}">{{ $r->location ?? '—' }}</td>
                                <td class="{{ $td }}"><span class="rounded-md bg-violet-50 px-2 py-0.5 text-[11px] font-bold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">{{ $r->land_use_type ?? '—' }}</span></td>
                                <td class="{{ $td }}">
                                    <span class="inline-flex items-center gap-2"><span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-[10px] font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ mb_strtoupper(mb_substr($r->matched_by_name ?? 'S', 0, 1)) }}</span>{{ $r->matched_by_name ?? 'System' }}</span>
                                </td>
                                <td class="{{ $td }} text-xs"><span class="font-semibold">{{ $r->date_matched->format('jS M, Y') }}</span> <span class="text-slate-400">{{ substr((string) $r->time_matched, 0, 5) }}</span></td>
                                <td class="{{ $td }} text-xs">{{ $r->pages_count }} @if ($r->pages_count)<span class="text-slate-400">({{ $r->typed_count }} typed)</span>@endif</td>
                                <td class="{{ $td }}"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider ring-1 {{ $statusTone[$status] }}">{{ $status }}</span></td>
                                <td class="{{ $td }} sticky right-0 bg-white text-right dark:bg-slate-900">
                                    <x-row-actions>
                                        <button type="button" @click="openView({{ $r->id }})" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <i data-lucide="eye" class="h-4 w-4 text-slate-400"></i> View record
                                        </button>
                                        <x-row-action :href="route('dms.scanning.index', ['file' => $r->id])"><x-slot:icon><i data-lucide="upload-cloud" class="h-4 w-4"></i></x-slot:icon> Upload scanned pages</x-row-action>
                                        @if ($r->pages_count)
                                            <x-row-action :href="route('dms.pagetyping.type', $r)"><x-slot:icon><i data-lucide="type" class="h-4 w-4"></i></x-slot:icon> Page typing</x-row-action>
                                        @endif
                                        @if ($r->typed_count)
                                            <x-row-action :href="route('archive.show', $r)"><x-slot:icon><i data-lucide="library" class="h-4 w-4"></i></x-slot:icon> Open in Doc-WARE</x-row-action>
                                        @endif
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-14 text-center">
                                    <i data-lucide="folder-search" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"></i>
                                    <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $search !== '' ? 'No matched file fits that search.' : 'No file has been matched yet.' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Use “Match Existing FileNo” to bring a KLAES file into KAMMA.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-800">{{ $records->links() }}</div>
            @endif
        </div>

        {{-- Match pop-up (KLAES "Match Lands File Numbers"): pick the KLAES file, its record fills in read-only --}}
        <template x-teleport="body">
            <div x-show="matchOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:items-center">
                <div @click.outside="closeAll()" x-show="matchOpen" x-transition class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Match Existing FileNo</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Commission a KAMMA shadow file from a KLAES indexed file.</p>
                        </div>
                        <button type="button" @click="closeAll()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-rose-500 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>

                    <div class="space-y-5 p-6">
                        {{-- 1. File details --}}
                        <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <h3 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-200"><i data-lucide="file-text" class="h-4 w-4 text-emerald-600"></i> File Details</h3>
                            <div x-show="! file" class="relative">
                                <label for="sf-q" class="{{ $label }}">Select File Number <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                    <input id="sf-q" x-ref="q" type="search" x-model="q" @input.debounce.350ms="search()" @keydown.enter.prevent autocomplete="off"
                                           placeholder="Type the file number or the holder's name (e.g. RES-2016-452)"
                                           class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                </div>
                                <p x-show="searching" class="mt-2 text-xs text-slate-500">Searching KLAES indexing…</p>
                                <p x-show="searched && ! searching && ! results.length" class="mt-2 text-xs text-slate-500">No indexed KLAES file matches that. A file must be indexed in KLAES first.</p>
                                <ul x-show="results.length" class="mt-2 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                                    <template x-for="f in results" :key="f.id">
                                        <li>
                                            <button type="button" @click="pick(f)" :disabled="f.matched"
                                                    class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:bg-slate-800">
                                                <span>
                                                    <span class="block font-mono text-sm font-bold text-emerald-700 dark:text-emerald-400" x-text="f.file_number"></span>
                                                    <span class="block text-xs text-slate-600 dark:text-slate-300" x-text="[f.file_title, f.plot_no && ('Plot ' + f.plot_no), f.district, f.lga].filter(Boolean).join(' · ')"></span>
                                                </span>
                                                <span x-show="f.matched" class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500 dark:bg-slate-800">Already matched</span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <div x-show="loadingFile" class="text-sm text-slate-500">Loading the KLAES indexing record…</div>
                            <template x-if="file">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="{{ $label }}">File Number</label>
                                        <div class="flex items-center gap-2">
                                            <div class="{{ $ro }} font-mono text-emerald-700 dark:text-emerald-400" x-text="file.file_number"></div>
                                            <button type="button" @click="reset()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-rose-500 dark:hover:bg-slate-800" title="Pick another file" aria-label="Pick another file"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></button>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="{{ $label }}">File Title</label>
                                        <div class="{{ $ro }}" x-text="file.file_title || '—'"></div>
                                    </div>
                                </div>
                            </template>
                            <p x-show="file && file.already" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300" x-text="file ? 'This file was already matched in KAMMA on ' + file.already + '.' : ''"></p>
                        </section>

                        <template x-if="file">
                            <div class="space-y-5">
                                {{-- 2. Location details (Plot No., District, LGA, Location) --}}
                                <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                    <h3 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-200"><i data-lucide="map-pin" class="h-4 w-4 text-emerald-600"></i> Location Details <span class="font-medium normal-case tracking-normal text-slate-400">from KLAES indexing</span></h3>
                                    <div class="grid gap-4 sm:grid-cols-4">
                                        <div><label class="{{ $label }}">Plot No.</label><div class="{{ $ro }}" x-text="file.plot_number || '—'"></div></div>
                                        <div><label class="{{ $label }}">District</label><div class="{{ $ro }}" x-text="file.district || '—'"></div></div>
                                        <div><label class="{{ $label }}">LGA</label><div class="{{ $ro }}" x-text="file.lga || '—'"></div></div>
                                        <div><label class="{{ $label }}">Land Use</label><div class="{{ $ro }}" x-text="file.land_use_type || '—'"></div></div>
                                        <div class="sm:col-span-4"><label class="{{ $label }}">Location</label><div class="{{ $ro }}" x-text="file.location || '—'"></div></div>
                                    </div>
                                </section>

                                {{-- 3. Archive details as KLAES holds them --}}
                                <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                    <h3 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-200"><i data-lucide="archive" class="h-4 w-4 text-emerald-600"></i> KLAES Archive Details</h3>
                                    <div class="grid gap-4 sm:grid-cols-5">
                                        <div><label class="{{ $label }}">Registry</label><div class="{{ $ro }}" x-text="file.registry || '—'"></div></div>
                                        <div><label class="{{ $label }}">Batch No.</label><div class="{{ $ro }}" x-text="file.batch_no || '—'"></div></div>
                                        <div><label class="{{ $label }}">Shelf / Rack</label><div class="{{ $ro }}" x-text="file.shelf_location || '—'"></div></div>
                                        <div><label class="{{ $label }}">Serial No.</label><div class="{{ $ro }}" x-text="file.serial_no || '—'"></div></div>
                                        <div><label class="{{ $label }}">Tracking ID</label><div class="{{ $ro }} font-mono text-xs" x-text="file.tracking_id || '—'"></div></div>
                                    </div>
                                </section>

                                {{-- 4. Matching metadata --}}
                                <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                    <h3 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-200"><i data-lucide="clock" class="h-4 w-4 text-emerald-600"></i> Matching Metadata</h3>
                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <div><label class="{{ $label }}">Date Matched</label><div class="{{ $ro }}" x-text="today"></div></div>
                                        <div><label class="{{ $label }}">Time Matched</label><div class="{{ $ro }}" x-text="now"></div></div>
                                        <div><label class="{{ $label }}">Matched By</label><div class="{{ $ro }}" x-text="cfg.officer"></div></div>
                                        <div class="sm:col-span-3">
                                            <label for="sf-remarks" class="{{ $label }}">Remarks <span class="font-medium normal-case text-slate-400">(optional)</span></label>
                                            <textarea id="sf-remarks" x-model="remarks" rows="2" maxlength="1000" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </template>

                        <p x-show="error" class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="error"></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/60">
                        <button type="button" @click="closeAll()" class="rounded-xl px-5 py-2.5 text-xs font-black uppercase tracking-wider text-slate-500 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800">Cancel</button>
                        <button type="button" @click="match()" :disabled="saving || ! file || !! file.already"
                                class="inline-flex min-w-[150px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none">
                            <i data-lucide="link-2" class="h-4 w-4"></i><span x-text="saving ? 'Matching…' : 'Match File'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- View record pop-up --}}
        <template x-teleport="body">
            <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:items-center">
                <div @click.outside="closeAll()" x-show="viewOpen" x-transition class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">KAMMA Shadow File</h2>
                            <p class="font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400" x-text="view ? view.file_number : 'Loading…'"></p>
                        </div>
                        <button type="button" @click="closeAll()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-rose-500 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <template x-if="view">
                        <dl class="grid gap-x-6 gap-y-4 p-6 sm:grid-cols-3">
                            <template x-for="[k, v] in [['File Title', view.file_title], ['Plot No.', view.plot_number], ['District', view.district], ['LGA', view.lga], ['Location', view.location], ['Land Use', view.land_use_type], ['Plot Size', view.plot_size], ['Registry', view.registry], ['Batch No.', view.batch_no], ['Shelf / Rack', view.shelf_location], ['Serial No.', view.serial_no], ['KLAES Tracking ID', view.tracking_id], ['Matched By', view.matched_by], ['Date / Time Matched', view.date_matched + ' ' + view.time_matched], ['Pages', view.pages + ' (' + view.typed + ' typed)'], ['DMS Status', view.status], ['KAMMA Tracking ID', view.tracking], ['Remarks', view.remarks]]" :key="k">
                                <div :class="k === 'Location' || k === 'Remarks' ? 'sm:col-span-3' : ''">
                                    <dt class="{{ $label }}" x-text="k"></dt>
                                    <dd class="text-sm font-medium text-slate-800 dark:text-slate-100" x-text="v || '—'"></dd>
                                </div>
                            </template>
                        </dl>
                    </template>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            function shadowFiles(cfg) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const json = async (url, opts = {}) => {
                    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(opts.body ? { 'Content-Type': 'application/json' } : {}) }, ...opts });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Something went wrong.'));
                    return data;
                };
                return {
                    cfg, matchOpen: false, viewOpen: false, q: '', results: [], searching: false, searched: false,
                    file: null, loadingFile: false, remarks: '', saving: false, error: '', flash: '', view: null,
                    get today() { return new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); },
                    get now() { return new Date().toLocaleTimeString('en-GB'); },
                    init() {
                        const f = sessionStorage.getItem('kamma-sf-flash');
                        if (f) { this.flash = f; sessionStorage.removeItem('kamma-sf-flash'); setTimeout(() => this.flash = '', 6000); }
                    },
                    openMatch() { this.reset(); this.matchOpen = true; this.$nextTick(() => { this.$refs.q.focus(); window.lucide && lucide.createIcons(); }); },
                    closeAll() { this.matchOpen = false; this.viewOpen = false; },
                    reset() { this.file = null; this.q = ''; this.results = []; this.searched = false; this.remarks = ''; this.error = ''; },
                    async search() {
                        this.error = '';
                        if (this.q.trim().length < 2) { this.results = []; this.searched = false; return; }
                        this.searching = true;
                        try { this.results = (await json(cfg.lookup + '?q=' + encodeURIComponent(this.q.trim()))).files; }
                        catch (e) { this.error = e.message; }
                        finally { this.searching = false; this.searched = true; }
                    },
                    async pick(f) {
                        this.results = []; this.loadingFile = true; this.error = '';
                        try { this.file = (await json(cfg.klaes.replace('__ID__', f.id))).file; this.$nextTick(() => window.lucide && lucide.createIcons()); }
                        catch (e) { this.error = e.message; }
                        finally { this.loadingFile = false; }
                    },
                    async match() {
                        if (! this.file) return;
                        this.saving = true; this.error = '';
                        try {
                            const r = await json(cfg.store, { method: 'POST', body: JSON.stringify({ klaes_file_indexing_id: this.file.id, remarks: this.remarks }) });
                            sessionStorage.setItem('kamma-sf-flash', r.message);
                            window.location.reload();
                        } catch (e) { this.error = e.message; this.saving = false; }
                    },
                    async openView(id) {
                        this.view = null; this.viewOpen = true;
                        try { this.view = (await json(cfg.show.replace('__ID__', id))).record; } catch (e) { this.viewOpen = false; alert(e.message); }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
