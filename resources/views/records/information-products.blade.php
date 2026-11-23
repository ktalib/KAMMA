{{-- Information Products > Dashboard and Land Records > Dashboard (copied from KADCA): the group's KLAES datasets side by side, read live. --}}
<x-app-layout :title="$title">
    @php
        $n = fn ($v) => number_format((int) $v);
        $months = [];
        $series = [];
        foreach ($sets as $key => $s) {
            if (! $s['stats']) { continue; }
            $months = array_keys($s['stats']['trend']);
            $series[] = ['label' => $s['def']['title'], 'data' => array_values($s['stats']['trend'])];
        }
        $trendChart = ['type' => 'bar', 'labels' => array_map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M y'), $months), 'series' => $series];
        $shareChart = ['type' => 'doughnut', 'labels' => collect($sets)->map(fn ($s) => $s['def']['title'])->values()->all(),
            'series' => [['label' => 'Records', 'data' => collect($sets)->map(fn ($s) => $s['stats']['total'] ?? 0)->values()->all()]]];
        $tones = ['rofo' => 'emerald', 'op' => 'blue', 'site-plans' => 'cyan', 'cofo' => 'violet', 'file-records' => 'blue', 'letter-of-grant' => 'emerald', 'post-cofo' => 'violet', 'file-status' => 'amber'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head :icon="$icon" :title="$title" :subtitle="$subtitle" :tone="$tone" />

        @if ($unreadable)
            <x-ui.klaes-note :error="'Could not read: '.implode(', ', $unreadable).'.'" />
        @else
            <x-ui.klaes-note :tables="$tables" />
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($sets as $key => $s)
                <a href="{{ route('records.show', $key) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-300"><i data-lucide="{{ $s['def']['icon'] }}" class="h-5 w-5"></i></span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-slate-300 group-hover:text-indigo-500 dark:text-slate-600"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $s['def']['title'] }}</p>
                    <p class="mt-1 text-3xl font-black tabular-nums text-slate-900 dark:text-white">{{ $s['stats'] ? $n($s['stats']['total']) : '—' }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ $s['stats'] ? $n($s['stats']['year']).' this year · '.$n($s['stats']['recent']).' in the last 30 days' : 'Not available' }}
                    </p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Captured per month" subtitle="Last 12 months, by product" class="xl:col-span-2">
                <x-ui.chart :chart="$trendChart" label="Records captured per month by product" height="h-72" />
            </x-ui.panel>
            <x-ui.panel title="Share of records" subtitle="All time">
                <x-ui.chart :chart="$shareChart" label="Share of records by product" height="h-72" />
            </x-ui.panel>
        </div>

        <x-ui.panel title="Products at a glance" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Product</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">This year</th><th class="px-4 py-3 text-right">This month</th><th class="px-4 py-3">Top category</th><th class="px-4 py-3">KLAES source</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach (array_values($sets) as $i => $s)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold"><a href="{{ route('records.show', $s['def']['key']) }}" class="text-indigo-700 hover:underline dark:text-indigo-300">{{ $s['def']['title'] }}</a></td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ $s['stats'] ? $n($s['stats']['total']) : '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ $s['stats'] ? $n($s['stats']['year']) : '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ $s['stats'] ? $n($s['stats']['month']) : '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    @php $top = $s['stats']['breakdowns'][0]['rows'][0] ?? null; @endphp
                                    {{ $top ? $top['label'].' ('.$n($top['n']).')' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $s['def']['klaes'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.panel>
    </div>
</x-app-layout>
