{{-- Building Plan Files > Grouping (user briefing 2026-11-25): the BP numbers set aside for existing BP files, one block per
     year from 1981 (10,000 a year for now). Made by `php artisan kamma:bp-grouping`. --}}
<x-app-layout title="BP Grouping">
    @php
        $th = 'whitespace-nowrap px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300';
        $totals = ['total' => $years->sum('total'), 'indexed' => $years->sum('indexed'), 'commissioned' => $years->sum('commissioned')];
    @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Building Plan Files</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Grouping</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">BP-year-serial numbers set aside for existing BP files, by year. Indexing an existing file uses its number here. New files are commissioned with the next serial of the current year.</p>
            </div>
            <a href="{{ route('bpa.bp-files.create-indexed') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700"><i data-lucide="folder-input" class="h-4 w-4"></i> Index existing file</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            @foreach ([['Years', $years->count()], ['Numbers', $totals['total']], ['Indexed', $totals['indexed']], ['Commissioned', $totals['commissioned']]] as [$l, $n])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $l }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($n) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50">
                        <tr>
                            <th class="{{ $th }}">S/N</th>
                            <th class="{{ $th }}">Year</th>
                            <th class="{{ $th }}">Range</th>
                            <th class="{{ $th }} text-right">Numbers</th>
                            <th class="{{ $th }} text-right">Indexed</th>
                            <th class="{{ $th }} text-right">Commissioned</th>
                            <th class="{{ $th }} text-right">Available</th>
                            <th class="{{ $th }}">Used</th>
                            <th class="{{ $th }} sticky right-0 bg-slate-50/95 dark:bg-slate-800/95"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($years as $y)
                            @php $used = (int) $y->indexed + (int) $y->commissioned; $pct = $y->total ? $used / $y->total * 100 : 0; @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="{{ $td }} text-slate-400">{{ $loop->iteration }}</td>
                                <td class="{{ $td }} font-bold">{{ $y->year }}</td>
                                <td class="{{ $td }} font-mono text-xs">{{ $numbers::format($y->year, $y->first_serial) }} – {{ $numbers::format($y->year, $y->last_serial) }}</td>
                                <td class="{{ $td }} text-right">{{ number_format($y->total) }}</td>
                                <td class="{{ $td }} text-right text-emerald-700 dark:text-emerald-300">{{ number_format($y->indexed) }}</td>
                                <td class="{{ $td }} text-right text-indigo-700 dark:text-indigo-300">{{ number_format($y->commissioned) }}</td>
                                <td class="{{ $td }} text-right">{{ number_format($y->total - $used) }}</td>
                                <td class="{{ $td }} w-40">
                                    <div class="h-1.5 w-32 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-emerald-500" style="width: {{ max($used ? 2 : 0, min(100, $pct)) }}%"></div></div>
                                </td>
                                <td class="{{ $td }} sticky right-0 bg-white text-right dark:bg-slate-900">
                                    <x-row-actions>
                                        <x-row-action :href="route('bpa.bp-files.index', ['year' => $y->year])"><x-slot:icon><i data-lucide="list" class="h-4 w-4"></i></x-slot:icon> BP files of {{ $y->year }}</x-row-action>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500 dark:text-slate-400">The grouping is empty. The System Admin runs <span class="font-mono">php artisan kamma:bp-grouping</span> (1981 to {{ $current }}, 10,000 a year).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
