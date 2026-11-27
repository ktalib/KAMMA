{{-- Building Plan Files > BP File Register (user 2026-11-25), and the two list pages that come before each form (user
     2026-11-25, "a table first ... and the dashboard on both pages"): $scope 'indexed' = DMS > Indexing (existing BP
     files), 'commissioned' = Building Plan Files > Commissioning (new BP files), null = the register. One table in two bands,
     like Survey's "Computation & Field Observations" report table in KLAES: the Land File band (the KLAES indexing record)
     and the Building Plan band (the BP file's own indexing). --}}
@php
    $page = match ($scope) {
        'indexed' => ['route' => 'bpa.bp-files.indexed', 'eyebrow' => 'DMS · Indexing', 'title' => 'Indexed BP Files',
            'intro' => 'Existing BP files indexed so far: each under its number from the grouping (BP-year-serial), beside its land file. Use “Index Existing BP File” to add one.'],
        'commissioned' => ['route' => 'bpa.bp-files.commissioned', 'eyebrow' => 'Building Plan Files', 'title' => 'Commissioned BP Files',
            'intro' => 'New BP files commissioned so far: each given the next serial of its year, beside its land file. Use “Commission New BP File” to add one.'],
        default => ['route' => 'bpa.bp-files.index', 'eyebrow' => 'Building Plan Files', 'title' => 'BP File Register',
            'intro' => 'Every BP file with its land file. Existing files are indexed under their number from the grouping; new files are commissioned with the next serial. From here a file starts its application.'],
    };
@endphp
<x-app-layout :title="$page['title']">
    @php
        $th = 'whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider';
        $td = 'whitespace-nowrap px-3 py-2.5 text-sm text-slate-700 dark:text-slate-300';
        $landHead = $th.' bg-indigo-50/70 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300';
        $bpHead = $th.' bg-emerald-50/70 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
        $split = 'border-l-2 border-slate-200 dark:border-slate-700';
        $input = 'rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100';
        $saved = session('bp_file_saved');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ $page['eyebrow'] }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $page['title'] }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">{{ $page['intro'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($scope !== 'commissioned')
                    <a href="{{ route('bpa.bp-files.create-indexed') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700">
                        <i data-lucide="folder-input" class="h-4 w-4"></i> {{ $scope ? 'Index Existing BP File' : 'Index existing file' }}
                    </a>
                @endif
                @if ($scope !== 'indexed')
                    <a href="{{ route('bpa.bp-files.create-commissioned') }}" class="inline-flex items-center gap-2 rounded-xl {{ $scope ? 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700' : 'border border-emerald-600 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-300 dark:hover:bg-emerald-500/10' }} px-4 py-2.5 text-sm font-bold">
                        <i data-lucide="folder-plus" class="h-4 w-4"></i> {{ $scope ? 'Commission New BP File' : 'Commission new file' }}
                    </a>
                @endif
                {{-- BP File Register button hidden for now (user 2026-11-25), like its menu item; the page still works. --}}
                @if ($scope && false)
                    <a href="{{ route('bpa.bp-files.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        <i data-lucide="table-2" class="h-4 w-4"></i> BP File Register
                    </a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                <span>{{ session('status') }}</span>
                @if ($saved)
                    <a href="{{ route('bpa.applications.create', ['bp_file' => $saved]) }}" class="font-semibold underline">Start its application</a>
                @endif
            </div>
        @endif

        {{-- Cards --}}
        <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($scope ? [
                [$scope === 'indexed' ? 'Indexed today' : 'Commissioned today', $stats['today'], 'calendar-check', 'text-emerald-700 dark:text-emerald-300', null],
                ['This month ('.now()->format('M Y').')', $stats['month'], 'calendar-range', 'text-indigo-700 dark:text-indigo-300', null],
                [$scope === 'indexed' ? 'Total indexed' : 'Total commissioned', $stats['total'], 'folders', 'text-slate-800 dark:text-white', null],
                ['Zone: Other (to set)', $stats['zone_other'], 'map-pin-off', 'text-amber-700 dark:text-amber-300', $otherIds->isNotEmpty() ? ['zone' => $otherIds->first()] : null],
                ['No application yet', $stats['no_application'], 'file-clock', 'text-slate-700 dark:text-slate-200', null],
                ['Land file not in KLAES', $stats['not_in_klaes'], 'alert-triangle', 'text-rose-700 dark:text-rose-300', null],
            ] : [
                ['Total BP files', $stats['total'], 'folders', 'text-slate-800 dark:text-white', null],
                ['Indexed (existing)', $stats['indexed'], 'folder-input', 'text-emerald-700 dark:text-emerald-300', ['origin' => 'indexed']],
                ['Commissioned (new)', $stats['commissioned'], 'folder-plus', 'text-indigo-700 dark:text-indigo-300', ['origin' => 'commissioned']],
                ['Zone: Other (to set)', $stats['zone_other'], 'map-pin-off', 'text-amber-700 dark:text-amber-300', $otherIds->isNotEmpty() ? ['zone' => $otherIds->first()] : null],
                ['No application yet', $stats['no_application'], 'file-clock', 'text-slate-700 dark:text-slate-200', null],
                ['Land file not in KLAES', $stats['not_in_klaes'], 'alert-triangle', 'text-rose-700 dark:text-rose-300', null],
            ] as [$label, $count, $icon, $tone, $filter])
                @php $tag = $filter ? 'a' : 'div'; @endphp
                <{{ $tag }} @if ($filter) href="{{ route($page['route'], $filter) }}" @endif class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 {{ $filter ? 'hover:border-emerald-300 dark:hover:border-emerald-500/40' : '' }}">
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400"><i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i> {{ $label }}</div>
                    <p class="mt-2 text-2xl font-black {{ $tone }}">{{ number_format($count) }}</p>
                </{{ $tag }}>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-[16rem] flex-1">
                <label for="f-search" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Search</label>
                <input id="f-search" type="search" name="search" value="{{ $search }}" placeholder="BP no., land file no., title, plot, district, LGA…" class="{{ $input }} w-full">
            </div>
            <div>
                <label for="f-zone" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Zone</label>
                <select id="f-zone" name="zone" class="{{ $input }}">
                    <option value="">All zones</option>
                    @foreach ($zones as $z)
                        <option value="{{ $z->id }}" @selected($zone === (string) $z->id)>{{ $z->name }}</option>
                    @endforeach
                    <option value="none" @selected($zone === 'none')>Not set</option>
                </select>
            </div>
            @unless ($scope)
                <div>
                    <label for="f-origin" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Origin</label>
                    <select id="f-origin" name="origin" class="{{ $input }}">
                        <option value="">Indexed and commissioned</option>
                        <option value="indexed" @selected($origin === 'indexed')>Indexed (existing)</option>
                        <option value="commissioned" @selected($origin === 'commissioned')>Commissioned (new)</option>
                    </select>
                </div>
            @endunless
            <div>
                <label for="f-year" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">BP year</label>
                <input id="f-year" type="number" name="year" min="1900" max="2100" value="{{ $year ?: '' }}" placeholder="Any" class="{{ $input }} w-28">
            </div>
            <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">Filter</button>
            @if ($search !== '' || $zone !== '' || (! $scope && $origin !== '') || $year)
                <a href="{{ route($page['route']) }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        {{-- The register: Building Plan band | Land File band (BP file first, user 2026-11-26) --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-slate-100">{{ $page['title'] }}</h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($files->total()) }} {{ $files->total() === 1 ? 'file' : 'files' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th rowspan="2" class="{{ $th }} bg-slate-50 align-bottom text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">S/N</th>
                            <th colspan="7" class="{{ $split }} bg-emerald-600 px-3 py-2 text-center text-xs font-black uppercase tracking-widest text-white dark:bg-emerald-500/80">Building Plan File</th>
                            <th colspan="7" class="{{ $split }} bg-indigo-600 px-3 py-2 text-center text-xs font-black uppercase tracking-widest text-white dark:bg-indigo-500/80">Land File · KLAES indexing</th>
                            <th rowspan="2" class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                        </tr>
                        <tr>
                            <th class="{{ $bpHead }} {{ $split }}">BP File No.</th>
                            <th class="{{ $bpHead }}">Origin</th>
                            <th class="{{ $bpHead }}">Zone</th>
                            <th class="{{ $bpHead }}">BP File Title</th>
                            <th class="{{ $bpHead }}">Registry / Batch / Shelf</th>
                            <th class="{{ $bpHead }}">Indexed By / Date</th>
                            <th class="{{ $bpHead }}">Application</th>
                            <th class="{{ $landHead }} {{ $split }}">File No.</th>
                            <th class="{{ $landHead }}">File Title</th>
                            <th class="{{ $landHead }}">Plot No.</th>
                            <th class="{{ $landHead }}">District</th>
                            <th class="{{ $landHead }}">LGA</th>
                            <th class="{{ $landHead }}">Location</th>
                            <th class="{{ $landHead }}">Land Use</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($files as $f)
                            <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40 {{ (int) $saved === $f->id ? 'bg-emerald-50/60 dark:bg-emerald-500/5' : '' }}">
                                <td class="{{ $td }} text-slate-400">{{ $files->firstItem() + $loop->index }}</td>
                                <td class="{{ $td }} {{ $split }} font-mono text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ $f->bp_file_no }}</td>
                                <td class="{{ $td }}">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $f->origin === 'indexed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' }}">{{ $f->origin === 'indexed' ? 'Indexed' : 'Commissioned' }}</span>
                                </td>
                                <td class="{{ $td }}">
                                    @if ($otherIds->contains($f->bp_zone_id))
                                        <span class="rounded bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300" title="Zone not known yet: set it from the row menu">Other</span>
                                    @else
                                        {{ $f->zone_name ?? '—' }}
                                    @endif
                                </td>
                                <td class="{{ $td }} max-w-[14rem] truncate" title="{{ $f->bp_file_title }}">{{ $f->bp_file_title ?? '—' }}</td>
                                <td class="{{ $td }} text-xs">{{ collect([$f->registry, $f->batch_no ? 'Batch '.$f->batch_no : null, $f->shelf_location ? 'Shelf '.$f->shelf_location : null])->filter()->implode(' · ') ?: '—' }}</td>
                                <td class="{{ $td }} text-xs">{{ $f->indexed_by_name ?? '—' }} <span class="text-slate-400">{{ $f->indexed_at?->format('d M Y') }}</span></td>
                                <td class="{{ $td }} text-xs">
                                    @if ($f->application)
                                        <a href="{{ route('bpa.applications.show', $f->application) }}" class="font-semibold text-indigo-700 hover:underline dark:text-indigo-300">{{ $f->application->application_no }}</a>
                                        <span class="block text-slate-400">{{ $f->application->application_type ?? 'Fresh Application' }} · {{ $f->application->status }}</span>
                                    @else
                                        <span class="text-slate-400">Not started</span>
                                    @endif
                                </td>
                                <td class="{{ $td }} {{ $split }} font-mono text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                    {{ $f->land_file_number }}
                                    @unless ($f->land_in_klaes)
                                        <span class="ml-1 rounded bg-rose-50 px-1.5 py-0.5 font-sans text-[10px] font-bold uppercase text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" title="The land file was typed: it is not in KLAES indexing">Not in KLAES</span>
                                    @endunless
                                </td>
                                <td class="{{ $td }} max-w-[14rem] truncate" title="{{ $f->land_file_title }}">{{ $f->land_file_title ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->plot_no ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->district ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->lga ?? '—' }}</td>
                                <td class="{{ $td }} max-w-[12rem] truncate text-xs" title="{{ $f->location }}">{{ $f->location ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->land_use ?? '—' }}</td>
                                <td class="{{ $td }} sticky right-0 bg-white text-right dark:bg-slate-900">
                                    <x-row-actions>
                                        @if ($f->application)
                                            <x-row-action :href="route('bpa.applications.show', $f->application)"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> Open application</x-row-action>
                                        @else
                                            <x-row-action :href="route('bpa.applications.create', ['bp_file' => $f->id])"><x-slot:icon><i data-lucide="file-plus-2" class="h-4 w-4"></i></x-slot:icon> Start application</x-row-action>
                                        @endif
                                        <x-row-action :href="route('bpa.bp-files.edit', $f)"><x-slot:icon><i data-lucide="pencil" class="h-4 w-4"></i></x-slot:icon> Edit BP indexing / zone</x-row-action>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="px-4 py-14 text-center">
                                    <i data-lucide="folder-search" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"></i>
                                    <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $search !== '' || $zone !== '' || (! $scope && $origin !== '') || $year ? 'No BP file fits those filters.' : ['indexed' => 'No existing BP file has been indexed yet.', 'commissioned' => 'No new BP file has been commissioned yet.'][$scope] ?? 'No BP file has been indexed or commissioned yet.' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($files->hasPages())
                <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-800">{{ $files->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
