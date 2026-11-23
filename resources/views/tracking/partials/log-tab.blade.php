{{-- File Tracker Log tab (KLAES create_file_tracker_page): handler cards, Control Panel, File Log Table. --}}
<div x-show="tab === 'log'" x-cloak class="p-6 space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100">All File Tracker Logs</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">Track and manage assigned files</p>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-2 dark:border-slate-700">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            <div><div class="text-sm font-semibold text-gray-900 dark:text-slate-100">Current Handler</div><div class="text-sm text-gray-500 dark:text-slate-400">{{ Auth::user()->name }} (You)</div></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['mine', 'My Files', 'Files currently assigned to you', 'folder', 'text-blue-600', 'bg-blue-100'], ['awaiting', 'Awaiting Acceptance', 'Files pending your acceptance', 'clock', 'text-green-600', 'bg-green-100'], ['others', "Other Handler's Files", 'Files assigned to other handlers', 'users', 'text-red-600', 'bg-red-100'], ['completed', 'Completed', 'Files marked as completed', 'check-circle', 'text-gray-700 dark:text-slate-200', 'bg-gray-100']] as [$k, $t, $s, $i, $tone, $chip])
            <button type="button" @click="group = (group === '{{ $k }}' ? '' : '{{ $k }}')" :class="group === '{{ $k }}' ? 'ring-2 ring-blue-500' : ''" class="rounded-lg border border-gray-200 bg-white p-5 text-left shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div><div class="text-sm text-gray-600 dark:text-slate-400">{{ $t }}</div><div class="mt-1 text-2xl font-bold {{ $tone }}" x-text="counts.{{ $k }}">0</div></div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $chip }} {{ $tone }}"><i data-lucide="{{ $i }}" class="h-6 w-6"></i></span>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-slate-400">{{ $s }}</div>
            </button>
        @endforeach
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Control Panel</h3><p class="text-sm text-gray-500 dark:text-slate-400">Manage your file trackers and filter results</p></div>
            <div class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400">From Date</label><input type="date" x-model="from" @change="loadList()" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></div>
                <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400">To Date</label><input type="date" x-model="to" @change="loadList()" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></div>
                <button type="button" @click="exportCsv()" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="download" class="h-4 w-4"></i> Export</button>
                <button type="button" @click="loadList()" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"><i data-lucide="refresh-cw" class="h-4 w-4"></i> Refresh</button>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200"><i data-lucide="user-check" class="h-4 w-4"></i> File Status Filter</div>
                <select x-model="group" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">All Files</option><option value="mine">My Files</option><option value="awaiting">Awaiting Acceptance</option><option value="others">Other Handler's Files</option><option value="completed">Completed</option>
                </select>
                <ul class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-slate-400">
                    <li>• <span class="font-semibold text-green-600">Green cards:</span> Files awaiting your acceptance</li>
                    <li>• <span class="font-semibold text-red-600">Red cards:</span> Files belonging to other handlers</li>
                    <li>• <span class="font-semibold text-blue-600">Blue cards:</span> Files assigned to you</li>
                    <li>• <span class="font-semibold text-gray-600">Gray cards:</span> Completed files</li>
                </ul>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200"><i data-lucide="filter" class="h-4 w-4"></i> Priority Filters</div>
                <div class="flex flex-wrap gap-2">
                    @foreach (['' => ['All', 'border-gray-300 text-gray-700'], 'Low' => ['Low Priority', 'bg-green-100 text-green-800'], 'Medium' => ['Medium', 'bg-yellow-100 text-yellow-800'], 'High' => ['High Priority', 'bg-red-100 text-red-800']] as $v => [$l, $c])
                        <button type="button" @click="priority = '{{ $v }}'" :class="priority === '{{ $v }}' ? 'ring-2 ring-blue-500' : ''" class="rounded-md border border-transparent px-2.5 py-1 text-xs font-medium {{ $c }}">{{ $l }}</button>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200"><i data-lucide="search" class="h-4 w-4"></i> Search Files</div>
                <input x-model="search" @input.debounce.400ms="loadList()" placeholder="Search by file name, number, or handler…" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-3 px-6 pt-5">
            <div>
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100"><i data-lucide="file-text" class="h-5 w-5"></i> File Log Table</h3>
                <p class="text-sm text-gray-500 dark:text-slate-400">All file trackers with handler assignment status</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-slate-400"><span class="font-semibold">Status Indicators:</span>
                    <span class="ml-2 inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-green-500"></span> Green — still within timeline</span>
                    <span class="ml-2 inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Amber — about to timeout</span>
                    <span class="ml-2 inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span> Red — overdue</span></p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-600 dark:text-slate-400"><span x-text="visible().length"></span> files found</span>
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-blue-700">My Files</span><span class="rounded-full bg-green-50 px-2 py-0.5 text-green-700">Awaiting</span><span class="rounded-full bg-red-50 px-2 py-0.5 text-red-700">Other's Files</span><span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">Completed</span>
            </div>
        </div>
        <div class="mt-4 flex gap-6 border-b border-gray-200 px-6 dark:border-slate-800">
            <button type="button" @click="listTab = 'active'" :class="listTab === 'active' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="-mb-px border-b-2 py-3 text-sm font-medium">Active Files <span class="ml-1 rounded-full bg-gray-100 px-2 text-xs text-gray-700" x-text="rows.filter(r => r.status !== 'Completed').length"></span></button>
            <button type="button" @click="listTab = 'completed'" :class="listTab === 'completed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="-mb-px border-b-2 py-3 text-sm font-medium">Completed Files <span class="ml-1 rounded-full bg-gray-100 px-2 text-xs text-gray-700" x-text="rows.filter(r => r.status === 'Completed').length"></span></button>
        </div>
        <div x-show="listTab === 'active'" class="flex gap-4 px-6 pt-3 text-sm">
            <button type="button" @click="transitOnly = ! transitOnly" :class="transitOnly ? 'text-blue-700 font-semibold' : 'text-gray-600 dark:text-slate-400'" class="inline-flex items-center gap-1.5"><i data-lucide="truck" class="h-4 w-4"></i> In-transit <span class="rounded-full bg-amber-100 px-2 text-xs text-amber-800" x-text="rows.filter(r => r.status === 'In-transit').length"></span></button>
        </div>
        <div class="overflow-x-auto p-6">
            <template x-if="loading"><div class="py-12 text-center text-sm text-gray-500"><i data-lucide="loader" class="mx-auto h-8 w-8 animate-spin text-blue-500"></i><div class="mt-2">Loading files…</div></div></template>
            <template x-if="! loading && visible().length === 0"><div class="py-12 text-center text-sm text-gray-500 dark:text-slate-400">No files to show.</div></template>
            <table x-show="! loading && visible().length" class="min-w-full divide-y divide-gray-200 text-sm dark:divide-slate-800">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr><th class="px-3 py-2">S/N</th><th class="px-3 py-2">File No.</th><th class="px-3 py-2">Tracking ID</th><th class="px-3 py-2">Priority</th><th class="px-3 py-2">Handler</th><th class="px-3 py-2">Location</th><th class="px-3 py-2">Purpose</th><th class="px-3 py-2">Logged Out</th><th class="px-3 py-2">Expected Return</th><th class="px-3 py-2">Pages</th><th class="px-3 py-2">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    <template x-for="(r, i) in visible()" :key="r.tracking_id">
                        <tr :class="{ 'bg-blue-50/50': r.group === 'mine', 'bg-green-50/50': r.group === 'awaiting', 'bg-red-50/40': r.group === 'others' }" class="dark:bg-transparent">
                            <td class="px-3 py-2 text-gray-600 dark:text-slate-400" x-text="i + 1"></td>
                            <td class="px-3 py-2"><div class="font-semibold text-gray-900 dark:text-slate-100" x-text="r.file_number"></div><div class="text-xs text-gray-500" x-text="r.file_title"></div></td>
                            <td class="px-3 py-2"><a :href="r.url" class="font-mono text-xs font-semibold text-blue-700 hover:underline dark:text-blue-300" x-text="r.tracking_id"></a></td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="{ 'bg-green-100 text-green-800': r.priority === 'Low', 'bg-yellow-100 text-yellow-800': r.priority === 'Medium', 'bg-red-100 text-red-800': r.priority === 'High' }" x-text="r.priority"></span></td>
                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300" x-text="r.handler || '—'"></td>
                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300" x-text="r.location"></td>
                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300" x-text="r.purpose || '—'"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-slate-300" x-text="r.logged_out || '—'"></td>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="inline-flex items-center gap-1.5"><span x-show="r.timeline" class="h-2 w-2 rounded-full" :class="{ 'bg-green-500': r.timeline === 'green', 'bg-amber-500': r.timeline === 'amber', 'bg-red-500': r.timeline === 'red' }"></span><span class="text-gray-700 dark:text-slate-300" x-text="r.expected_return || '—'"></span></span></td>
                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300" x-text="r.pages || '—'"></td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-amber-100 text-amber-800': r.status === 'In-transit', 'bg-blue-100 text-blue-800': r.status === 'Active', 'bg-gray-100 text-gray-700': r.status === 'Completed' }" x-text="r.status"></span></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
