{{-- GIS > GIS Reports. KLAES's GIS Reports item is a dead link, so this is a fresh design in the style of KLAES Survey
     Reports: one band per KLAES GIS source (GIS Records, E-Registry, Site Plans), totals, a 12-month trend and the
     breakdown tables, printable. Read-only from KLAES. --}}
<x-app-layout title="GIS Reports">
    @php $n = fn ($v) => $v === null ? '—' : number_format((int) $v); @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="file-bar-chart" title="GIS Reports" subtitle="Figures from KLAES GIS: captured parcels, e-registry and site plans" tone="teal" section="GIS">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 print:hidden"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
        </x-ui.page-head>
        <x-ui.klaes-note tables="gisCapture, eRegistry, site plans" :error="$unreadable ? implode(', ', $unreadable).' could not be read.' : null" />

        @foreach ($sets as $key => $s)
            @php
                $st = $s['stats'];
                $trend = $st['trend'] ?? [];
                $chart = ['type' => 'bar', 'labels' => array_map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M y'), array_keys($trend)), 'series' => [['label' => 'Records', 'data' => array_values($trend)]]];
            @endphp
            <x-ui.panel :title="$s['def']['title']" :subtitle="$s['def']['subtitle']">
                <x-slot:actions><a href="{{ route('records.show', $key) }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300 print:hidden">Open records</a></x-slot:actions>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-ui.stat title="Total" :value="$n($st['total'] ?? null)" icon="database" tone="blue" />
                    <x-ui.stat title="This year" :value="$n($st['year'] ?? null)" icon="calendar-range" tone="emerald" />
                    <x-ui.stat title="This month" :value="$n($st['month'] ?? null)" icon="calendar-days" tone="violet" />
                    <x-ui.stat title="Last 30 days" :value="$n($st['recent'] ?? null)" icon="clock-3" tone="amber" />
                </div>
                <div class="mt-6 grid gap-6 xl:grid-cols-3">
                    <div class="xl:col-span-1"><p class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Per month</p><x-ui.chart :chart="$chart" :label="$s['def']['title'].' per month'" height="h-56" /></div>
                    @foreach ($st['breakdowns'] ?? [] as $b)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $b['title'] }}</p>
                            <table class="w-full text-sm">
                                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th class="pb-1">S/N</th><th class="pb-1">Value</th><th class="pb-1 text-right">Records</th></tr></thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach (array_slice($b['rows'], 0, 8) as $r)
                                        <tr><td class="py-1.5 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td><td class="py-1.5">{{ $r['label'] }}</td><td class="py-1.5 text-right tabular-nums font-semibold">{{ number_format($r['n']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </x-ui.panel>
        @endforeach
    </div>
</x-app-layout>
