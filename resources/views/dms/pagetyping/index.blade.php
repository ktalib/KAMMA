{{-- DMS > Document Page Types > PageTyping, copied from KLAES Page Typing (resources/views/pagetyping/index.blade.php):
     Pending Page Typing / In Progress / Completed / PageType More counters and tabs, then "Files Pending Page Typing"
     with a search. ?url=ptmore = DMS Update > More Pages (the PageType More tab). --}}
<x-app-layout :title="$tab === 'more' ? 'More Pages' : 'Page Typing'">
    @php
        $th = 'whitespace-nowrap px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300';
        $cards = [
            'pending' => ['Pending Page Typing', 'Files waiting for page typing', 'text-slate-900 dark:text-white', 'clock'],
            'progress' => ['In Progress', 'Files currently being typed', 'text-sky-600 dark:text-sky-400', 'loader'],
            'completed' => ['Completed', 'Total files completed', 'text-emerald-600 dark:text-emerald-400', 'check-circle-2'],
            'more' => ['PageType More', 'Files with new scans added', 'text-orange-600 dark:text-orange-400', 'file-plus-2'],
        ];
        $headings = ['pending' => 'Files Pending Page Typing', 'progress' => 'Files In Progress', 'completed' => 'Completed Files', 'more' => 'Files With New Scans (PageType More)'];
        $from = request('from');
        $tabUrl = fn ($t) => $t === 'more' && request('url') === 'ptmore'
            ? route('dms.pagetyping.index', array_filter(['url' => 'ptmore', 'from' => $from]))
            : route('dms.pagetyping.index', ['tab' => $t]);
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $tab === 'more' && request('url') === 'ptmore' ? 'More Pages' : 'Page Typing' }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $tab === 'more' && request('url') === 'ptmore' ? 'DMS Update: type the pages added by Scan More.' : 'Categorise and digitise file content' }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $key => [$title, $hint, $tone, $icon])
                <a href="{{ $tabUrl($key) }}" class="group rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900 {{ $tab === $key ? 'border-blue-400 ring-2 ring-blue-500/20 dark:border-blue-500' : 'border-slate-200 dark:border-slate-800' }}">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
                        <i data-lucide="{{ $icon }}" class="h-4 w-4 text-slate-300 group-hover:text-slate-500"></i>
                    </div>
                    <p class="mt-2 text-3xl font-black {{ $tone }}">{{ number_format($stats[$key]) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                </a>
            @endforeach
        </div>

        <div class="flex gap-1 overflow-x-auto border-b border-slate-200 dark:border-slate-800">
            @foreach (\App\Services\Dms\DmsFiles::PT_TABS as $key => $label)
                <a href="{{ $tabUrl($key) }}" class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-bold {{ $tab === $key ? 'border-blue-600 text-blue-700 dark:text-blue-300' : 'border-transparent text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">{{ $headings[$tab] }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Select a file to begin typing its content</p>
                </div>
                <form method="GET" class="relative">
                    @if (request('url') === 'ptmore')<input type="hidden" name="url" value="ptmore"><input type="hidden" name="from" value="{{ $from }}">@else<input type="hidden" name="tab" value="{{ $tab }}">@endif
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search files…" class="w-72 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="{{ $th }}">S/N</th><th class="{{ $th }}">File Number</th><th class="{{ $th }}">Title</th>
                            <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th>
                            <th class="{{ $th }}">Progress</th>
                            <th class="{{ $th }} sticky right-0 bg-slate-50/95 dark:bg-slate-800/95"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($files as $f)
                            @php
                                $pct = $f->pages_count ? (int) round($f->typed_count / $f->pages_count * 100) : 0;
                                $action = $f->typed_count ? ($f->untyped_count ? 'Continue typing' : 'Review typing') : 'Begin typing';
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="{{ $td }} text-slate-400">{{ $files->firstItem() + $loop->index }}</td>
                                <td class="{{ $td }}"><a href="{{ route('dms.pagetyping.type', $f) }}" class="font-mono text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400">{{ $f->file_number }}</a></td>
                                <td class="{{ $td }} max-w-[16rem] truncate font-medium" title="{{ $f->file_title }}">{{ $f->file_title ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->plot_number ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->district ?? '—' }}</td>
                                <td class="{{ $td }}">{{ $f->lga ?? '—' }}</td>
                                <td class="{{ $td }} min-w-[12rem]">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ $pct }}%"></div></div>
                                        <span class="text-xs font-semibold">{{ $f->typed_count }}/{{ $f->pages_count }}</span>
                                    </div>
                                    @if ($f->more_count)<p class="mt-0.5 text-[11px] font-semibold text-orange-600 dark:text-orange-400">{{ $f->more_count }} new page(s) from Scan More</p>@endif
                                </td>
                                <td class="{{ $td }} sticky right-0 bg-white text-right dark:bg-slate-900">
                                    <x-row-actions>
                                        <x-row-action :href="route('dms.pagetyping.type', $f)"><x-slot:icon><i data-lucide="type" class="h-4 w-4"></i></x-slot:icon>{{ $action }}</x-row-action>
                                        <x-row-action :href="route('dms.scanning.index', ['file' => $f->id])"><x-slot:icon><i data-lucide="upload-cloud" class="h-4 w-4"></i></x-slot:icon> Upload more pages</x-row-action>
                                        @if ($f->typed_count)
                                            <x-row-action :href="route('archive.show', $f)"><x-slot:icon><i data-lucide="library" class="h-4 w-4"></i></x-slot:icon> Open in Doc-WARE</x-row-action>
                                        @endif
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-14 text-center">
                                <i data-lucide="inbox" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"></i>
                                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $search !== '' ? 'No file fits that search.' : 'No files here.' }}</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <span>Showing {{ $files->firstItem() ?? 0 }} to {{ $files->lastItem() ?? 0 }} of {{ number_format($files->total()) }} entries</span>
                @if ($files->hasPages()){{ $files->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>
