{{-- Quick Search & File Location, copied from KLAES (resources/views/create_file_tracker_page/quick_search.blade.php):
     the same band, File Search Report cards, Enter File Number with Select, result card with Next Action, Registry
     Monitor (formerly SCB) Feedback and File Search History. Data: KAMMA's files, units and file requests. --}}
<x-app-layout title="Quick Search & File Location" :bare="true">
    @php
        $cards = [
            ['Total Blind / Open', 'qs-rep-blind', 'sky', 'search'],
            ['Total Found', 'qs-rep-found', 'emerald', 'check-circle'],
            ['Total Not Found', 'qs-rep-notfound', 'amber', 'x-circle'],
            ['Total Not With KAMMA (NWK)', 'qs-rep-missing', 'red', 'alert-triangle'],
            ['Total Awaiting', 'qs-rep-awaiting', 'slate', 'clock'],
        ];
        $tone = [
            'sky' => ['border-sky-300 bg-sky-500', 'text-sky-300', 'text-sky-100', 'bg-sky-700 shadow-sky-900'],
            'emerald' => ['border-emerald-300 bg-emerald-500', 'text-emerald-300', 'text-emerald-100', 'bg-emerald-700 shadow-emerald-900'],
            'amber' => ['border-amber-300 bg-amber-500', 'text-amber-300', 'text-amber-100', 'bg-amber-700 shadow-amber-900'],
            'red' => ['border-red-300 bg-red-500', 'text-red-300', 'text-red-100', 'bg-red-700 shadow-red-900'],
            'slate' => ['border-slate-300 bg-slate-500', 'text-slate-300', 'text-slate-100', 'bg-slate-700 shadow-slate-900'],
        ];
        $panel = 'bg-white rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:bg-slate-900';
    @endphp

    <div class="flex-1">
        <div class="px-6 py-3 flex items-center gap-3 shadow-sm bg-gradient-to-r from-red-700 via-red-600 to-red-800">
            <i data-lucide="search" class="h-5 w-5 text-white shrink-0"></i>
            <div class="flex items-center gap-2">
                <span class="text-white font-bold text-sm uppercase tracking-widest">Quick Search</span>
                <span class="text-red-100 text-sm">·</span>
                <span class="text-white text-sm font-medium">File Location &amp; Status</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Reporting summary -->
            <div class="{{ $panel }} p-5 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2 dark:text-slate-100">
                        <i data-lucide="bar-chart-3" class="h-4 w-4 text-indigo-600"></i> File Search Report
                    </h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-gray-400"></i>
                            <input id="qs-rep-from" type="date" class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" title="From date">
                            <span class="text-gray-400">–</span>
                            <input id="qs-rep-to" type="date" class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" title="To date">
                            <button type="button" id="qs-rep-clear" class="hidden inline-flex items-center gap-1 rounded-lg border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-500 hover:bg-gray-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                <i data-lucide="x" class="h-3 w-3"></i> Clear
                            </button>
                        </div>
                        <span class="hidden sm:inline text-gray-200 dark:text-slate-700">|</span>
                        <a id="qs-export-csv" href="{{ route('tracking.quick-search.csv') }}" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i> CSV
                        </a>
                        <button type="button" id="qs-export-pdf" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i> PDF
                        </button>
                        <span id="qs-rep-date" class="text-xs font-medium text-gray-400"></span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="group relative overflow-hidden rounded-xl border border-indigo-300 bg-indigo-500 p-4 shadow-md transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                        <span class="pointer-events-none absolute -right-3 -top-3 text-indigo-300"><i data-lucide="inbox" class="h-16 w-16"></i></span>
                        <div class="relative flex items-start justify-between gap-2">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Requests Today</div>
                                <div id="qs-rep-today" class="mt-1 text-3xl font-extrabold leading-none text-white">—</div>
                            </div>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-700 text-white shadow-md shadow-indigo-900"><i data-lucide="inbox" class="h-5 w-5"></i></span>
                        </div>
                        <div class="relative mt-3 space-y-1 border-t border-indigo-400 pt-2 text-[10px] font-medium">
                            @foreach (['Found' => 'qs-rep-today-found', 'Not Found' => 'qs-rep-today-notfound', 'Awaiting' => 'qs-rep-today-awaiting'] as $l => $id)
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-indigo-100"><span class="h-1.5 w-1.5 rounded-full bg-white"></span>{{ $l }}</span>
                                    <span id="{{ $id }}" class="font-bold text-white">—</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @foreach ($cards as [$label, $id, $c, $icon])
                        @php [$box, $ghost, $lbl, $chip] = $tone[$c]; @endphp
                        <div class="group relative overflow-hidden rounded-xl border {{ $box }} p-4 shadow-md transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <span class="pointer-events-none absolute -right-3 -top-3 {{ $ghost }}"><i data-lucide="{{ $icon }}" class="h-16 w-16"></i></span>
                            <div class="relative flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide {{ $lbl }}">{{ $label }}</div>
                                    <div id="{{ $id }}" class="mt-1 text-3xl font-extrabold leading-none text-white">—</div>
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $chip }} text-white shadow-md"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Left: Search + Result -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="{{ $panel }} p-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 dark:text-slate-300">Enter File Number</label>
                        <div class="flex gap-2">
                            <input id="qs-input" type="text" readonly autocomplete="off" placeholder="Click “Select” to choose a file number"
                                class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm uppercase cursor-pointer focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <button type="button" id="qs-pick" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-900 whitespace-nowrap dark:bg-slate-700 dark:hover:bg-slate-600">
                                <i data-lucide="folder-search" class="h-4 w-4"></i><span>Select</span>
                            </button>
                            <button type="button" id="qs-btn" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                <i data-lucide="search" class="h-4 w-4"></i><span>Search</span>
                            </button>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 flex-wrap">
                            <p class="text-xs text-gray-500 dark:text-slate-400">Every search returns a clear outcome and next action. A tracking ID scanned from a QR label works too.</p>
                        </div>
                    </div>

                    <div id="qs-result" class="hidden"></div>

                    <!-- Registry Monitor Feedback table (KLAES: SCB Feedback) -->
                    <div class="{{ $panel }} overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2 dark:text-slate-100">
                                <i data-lucide="message-square-reply" class="h-4 w-4 text-indigo-600"></i> Registry Monitor Feedback
                            </h3>
                            <button type="button" id="qs-tbl-refresh" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i> Refresh
                            </button>
                        </div>
                        <div class="px-6 py-2.5 border-b border-gray-100 dark:border-slate-800">
                            <div class="relative">
                                <i data-lucide="search" class="h-3.5 w-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input id="qs-tbl-search" type="text" placeholder="Search file no, title, requester…" class="w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            </div>
                        </div>
                        <div id="qs-feedback-table" class="overflow-x-auto"></div>
                    </div>
                </div>

                <!-- Right: File Search History -->
                <aside class="lg:col-span-1 lg:sticky lg:top-20">
                    <div class="{{ $panel }} overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2 dark:text-slate-100">
                                    <i data-lucide="clipboard-list" class="h-4 w-4 text-indigo-600"></i> File Search History <span id="qs-log-total" class="text-gray-400 font-semibold"></span>
                                </h3>
                                <button type="button" id="qs-fb-refresh" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i> Refresh
                                </button>
                            </div>
                            <div id="qs-log-filters" class="mt-3 flex flex-wrap items-center gap-1.5"></div>
                            <div class="mt-2.5 relative">
                                <i data-lucide="search" class="h-3.5 w-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input id="qs-log-search" type="text" placeholder="Search file no, title, requester…" class="w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            </div>
                        </div>
                        <div id="qs-feedback" class="max-h-[70vh] overflow-y-auto"></div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    {{-- File number selector (the KLAES "Select" picker): KAMMA files only, never typed into the search box. --}}
    <div id="qs-picker" class="fixed inset-0 z-50 hidden items-start justify-center bg-gray-900/60 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="qs-picker-title">
        <div class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-slate-800">
                <h3 id="qs-picker-title" class="flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-slate-100"><i data-lucide="folder-search" class="h-4 w-4 text-indigo-600"></i> Select File Number</h3>
                <button type="button" id="qs-picker-close" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <div class="p-5">
                <div class="relative">
                    <i data-lucide="search" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input id="qs-picker-q" type="search" autocomplete="off" placeholder="Land file number (e.g. RES-2016-452), KMA-TRK tracking ID or file title"
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div id="qs-picker-list" class="mt-3 max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-800"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.1/jspdf.plugin.autotable.min.js"></script>
        @include('tracking.partials.quick-search-js')
    @endpush
</x-app-layout>
