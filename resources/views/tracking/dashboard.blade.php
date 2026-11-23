{{-- File Tracker Dashboard, copied from KLAES (file-tracker.dashboard): header with Create File Tracker / Refresh,
     four cards (Total Files, Active Files, High Priority, Offices Involved + Show Charts), the filter bar and the
     "Files in Transit" table with Customize columns. Data: KAMMA's tracked files and mails. --}}
<x-app-layout title="File Tracker Dashboard" :bare="true">
    @php $card = 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900'; @endphp

    <div x-data="{
            rows: @js($rows), q: '', priority: '', office: '', charts: false,
            cols: { file_number: true, file_title: true, office: true, since: true, priority: true, status: true, actions: true }, pick: false,
            list() { const q = this.q.toLowerCase(); return this.rows.filter(r => r.status !== 'Completed'
                && (! q || [r.file_number, r.file_title, r.tracking_id].join(' ').toLowerCase().includes(q))
                && (! this.priority || r.priority === this.priority) && (! this.office || String(r.office_id) === this.office)); },
         }"
         x-effect="list(); $nextTick(() => window.lucide && window.lucide.createIcons())">
        <div class="border-b border-gray-200 bg-white px-6 py-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">File Tracker Dashboard</h1>
                    <p class="text-gray-600 dark:text-slate-400">Monitor all files in transit across the organization</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('tracking.create') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Create File Tracker</a>
                    <a href="{{ route('tracking.dashboard') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"><i data-lucide="refresh-cw" class="h-4 w-4"></i> Refresh</a>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                <div class="{{ $card }} p-6">
                    <div class="flex items-start gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600"><i data-lucide="file-text" class="h-6 w-6"></i></span>
                        <div><div class="text-sm font-medium text-gray-600 dark:text-slate-400">Total Files</div><div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($figures['total']) }}</div></div></div>
                    <div class="mt-4 flex items-center gap-1 text-sm text-green-600"><i data-lucide="trending-up" class="h-4 w-4"></i> {{ number_format($figures['new_this_week']) }} new this week</div>
                </div>
                <div class="{{ $card }} p-6">
                    <div class="flex items-start gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600"><i data-lucide="activity" class="h-6 w-6"></i></span>
                        <div><div class="text-sm font-medium text-gray-600 dark:text-slate-400">Active Files</div><div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($figures['active']) }}</div></div></div>
                    <div class="mt-4 flex items-center gap-1 text-sm text-blue-600"><i data-lucide="clock" class="h-4 w-4"></i> Currently being processed</div>
                </div>
                <div class="{{ $card }} p-6">
                    <div class="flex items-start gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600"><i data-lucide="alert-triangle" class="h-6 w-6"></i></span>
                        <div><div class="text-sm font-medium text-gray-600 dark:text-slate-400">High Priority</div><div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($figures['high']) }}</div></div></div>
                    <div class="mt-4 flex items-center gap-1 text-sm text-red-600"><i data-lucide="alert-circle" class="h-4 w-4"></i> Requires immediate attention</div>
                </div>
                <div class="{{ $card }} p-6">
                    <div class="flex items-start gap-4"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-purple-600"><i data-lucide="building" class="h-6 w-6"></i></span>
                        <div><div class="text-sm font-medium text-gray-600 dark:text-slate-400">Offices Involved</div><div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($figures['offices']) }}</div></div></div>
                    <div class="mt-4 flex items-center justify-between gap-2 text-sm text-purple-600">
                        <span class="flex items-center gap-1"><i data-lucide="map-pin" class="h-4 w-4"></i> Across all departments</span>
                        <button type="button" @click="charts = ! charts" class="inline-flex items-center gap-1 rounded-md border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700"><i data-lucide="bar-chart-3" class="h-3.5 w-3.5"></i> <span x-text="charts ? 'Hide Charts' : 'Show Charts'"></span></button>
                    </div>
                </div>
            </div>

            <div x-show="charts" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="{{ $card }} p-6">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-slate-100">Files by Office</h3>
                    @forelse ($byOffice as $office => $n)
                        <div class="mb-2"><div class="flex justify-between text-xs text-gray-600 dark:text-slate-400"><span>{{ $office }}</span><span>{{ $n }}</span></div>
                            <div class="mt-1 h-2 rounded-full bg-gray-100 dark:bg-slate-800"><div class="h-2 rounded-full bg-purple-500" style="width: {{ max(4, round($n / max(1, $byOffice->max()) * 100)) }}%"></div></div></div>
                    @empty <p class="text-sm text-gray-500">No active files.</p> @endforelse
                </div>
                <div class="{{ $card }} p-6">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-slate-100">Files by Priority</h3>
                    @foreach (['High' => 'bg-red-500', 'Medium' => 'bg-yellow-500', 'Low' => 'bg-green-500'] as $p => $c)
                        @php $n = $byPriority[$p] ?? 0; @endphp
                        <div class="mb-2"><div class="flex justify-between text-xs text-gray-600 dark:text-slate-400"><span>{{ $p }}</span><span>{{ $n }}</span></div>
                            <div class="mt-1 h-2 rounded-full bg-gray-100 dark:bg-slate-800"><div class="h-2 rounded-full {{ $c }}" style="width: {{ $n ? max(4, round($n / max(1, $byPriority->max()) * 100)) : 0 }}%"></div></div></div>
                    @endforeach
                </div>
            </div>

            <div class="{{ $card }} p-6">
                <div class="flex flex-wrap gap-3">
                    <div class="relative min-w-[16rem] flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        <input x-model="q" placeholder="Search files by name, number, or tracking ID..." class="w-full rounded-md border border-gray-300 py-2 pl-10 pr-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <select x-model="priority" class="rounded-md border border-gray-300 py-2 pl-3 pr-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">All Priorities</option><option>High</option><option>Medium</option><option>Low</option>
                    </select>
                    <select x-model="office" class="rounded-md border border-gray-300 py-2 pl-3 pr-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">All Offices</option>
                        @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    <button type="button" @click="q = ''; priority = ''; office = ''" class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200"><i data-lucide="x" class="h-4 w-4"></i> Clear</button>
                </div>
            </div>

            <div class="{{ $card }}">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-slate-800">
                    <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100"><i data-lucide="files" class="h-5 w-5"></i> Files in Transit</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400">All files currently being tracked across offices</p>
                </div>
                <div class="relative flex justify-end px-6 pt-3">
                    <button type="button" @click="pick = ! pick" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white">Customize columns</button>
                    <div x-show="pick" x-cloak @click.outside="pick = false" class="absolute right-6 top-11 z-10 w-48 rounded-md border border-gray-200 bg-white p-3 text-sm shadow-lg dark:border-slate-700 dark:bg-slate-900">
                        @foreach (['file_number' => 'File Number', 'file_title' => 'File Title', 'office' => 'Current Office', 'since' => 'Time in Office', 'priority' => 'Priority', 'status' => 'Status', 'actions' => 'Actions'] as $k => $l)
                            <label class="flex items-center gap-2 py-0.5 text-gray-700 dark:text-slate-300"><input type="checkbox" x-model="cols.{{ $k }}" class="rounded border-gray-300"> {{ $l }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-6 py-3">S/N</th><th x-show="cols.file_number" class="px-6 py-3">File Number</th><th x-show="cols.file_title" class="px-6 py-3">File Title</th><th x-show="cols.office" class="px-6 py-3">Current Office</th><th x-show="cols.since" class="px-6 py-3">Time in Office</th><th x-show="cols.priority" class="px-6 py-3">Priority</th><th x-show="cols.status" class="px-6 py-3">Status</th><th x-show="cols.actions" class="px-6 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <template x-for="(r, i) in list()" :key="r.tracking_id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40">
                                    <td class="px-6 py-3 text-gray-500" x-text="i + 1"></td>
                                    <td x-show="cols.file_number" class="px-6 py-3"><div class="font-semibold text-gray-900 dark:text-slate-100" x-text="r.file_number"></div><div class="font-mono text-xs text-blue-700 dark:text-blue-300" x-text="r.tracking_id"></div></td>
                                    <td x-show="cols.file_title" class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.file_title"></td>
                                    <td x-show="cols.office" class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.office"></td>
                                    <td x-show="cols.since" class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.since"></td>
                                    <td x-show="cols.priority" class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="{ 'bg-red-100 text-red-800': r.priority === 'High', 'bg-yellow-100 text-yellow-800': r.priority === 'Medium', 'bg-green-100 text-green-800': r.priority === 'Low' }" x-text="r.priority"></span></td>
                                    <td x-show="cols.status" class="px-6 py-3"><span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold" :class="r.status === 'In Transit' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'" x-text="r.status"></span></td>
                                    <td x-show="cols.actions" class="px-6 py-3 text-right"><a :href="r.url" class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200"><i data-lucide="eye" class="h-3.5 w-3.5"></i> View</a></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="list().length === 0" class="py-16 text-center">
                        <span class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800"><i data-lucide="file-x" class="h-12 w-12"></i></span>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-slate-100">No files found</h3>
                        <p class="text-gray-500 dark:text-slate-400">Try adjusting your search or filter to find what you're looking for.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
