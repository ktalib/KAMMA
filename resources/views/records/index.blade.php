{{-- A read-only KLAES record screen: dashboard (figures, trend, breakdowns), search and the paginated table.
     Used by Information Products, Land Records, the DMS registers, GIS and CRM (RecordsController). --}}
<x-app-layout :title="$def['title']">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $n = fn ($v) => number_format((int) $v);
        $trend = $stats['trend'] ?? [];
        $trendChart = [
            'type' => 'line',
            'labels' => array_map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M y'), array_keys($trend)),
            'series' => [['label' => 'Records', 'data' => array_values($trend)]],
        ];
        $breakdownCharts = [];
        foreach ($stats['breakdowns'] ?? [] as $i => $b) {
            $rowsB = $b['rows'];
            $top = array_slice($rowsB, 0, $i === 0 ? 6 : 8);
            $rest = array_sum(array_column(array_slice($rowsB, count($top)), 'n'));
            if ($i === 0 && $rest > 0) { $top[] = ['label' => 'Other', 'n' => $rest]; }
            $breakdownCharts[] = ['title' => $b['title'], 'rows' => $rowsB, 'chart' => [
                'type' => $i === 0 ? 'doughnut' : 'hbar',
                'labels' => array_column($top, 'label'),
                'series' => [['label' => 'Records', 'data' => array_column($top, 'n')]],
            ]];
        }
        $badge = 'inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300';
        $statusTone = function (?string $v) {
            $v = mb_strtolower((string) $v);
            return match (true) {
                str_contains($v, 'approv') || str_contains($v, 'found') || str_contains($v, 'complet') || str_contains($v, 'generat') || str_contains($v, 'issued') || $v === 'yes' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                str_contains($v, 'reject') || str_contains($v, 'declin') || str_contains($v, 'missing') || str_contains($v, 'revok') || $v === 'no' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                str_contains($v, 'pend') || str_contains($v, 'progress') || str_contains($v, 'await') || str_contains($v, 'draft') => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            };
        };
        $activeFilters = collect($filters)->filter()->count() + ($q !== '' ? 1 : 0);
    @endphp

    <div class="space-y-6" x-data="{ record: null }" @show-record.window="record = $event.detail">
        <x-ui.page-head :icon="$def['icon']" :title="$def['title']" :subtitle="$def['subtitle']" :tone="$def['tone']" :section="$def['section']">
            @if ($def['section'] === 'Information Products')
                <a href="{{ route('records.information-products') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="layout-dashboard" class="h-4 w-4"></i> Products dashboard</a>
            @elseif ($def['section'] === 'Land Records')
                <a href="{{ route('records.land') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="layout-dashboard" class="h-4 w-4"></i> Land Records dashboard</a>
            @endif
            {{-- A screen-specific action, e.g. Title Status Update's "Create Title Status" pop-up. --}}
            @isset($def['partial'])
                @include($def['partial'])
            @endisset
        </x-ui.page-head>

        <x-ui.klaes-note :tables="$def['klaes']" :error="$error" />

        {{-- Dashboard --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Total records" :value="$stats ? $n($stats['total']) : '—'" hint="All records in KLAES" icon="database" tone="blue" />
            <x-ui.stat title="This year" :value="$stats ? $n($stats['year']) : '—'" :hint="'Captured since 1 January '.now()->year" icon="calendar-range" tone="emerald" />
            <x-ui.stat title="This month" :value="$stats ? $n($stats['month']) : '—'" :hint="'Captured in '.now()->format('F Y')" icon="calendar-days" tone="violet" />
            <x-ui.stat title="Last 30 days" :value="$stats ? $n($stats['recent']) : '—'" hint="Recently added" icon="clock-3" tone="amber" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Monthly trend" subtitle="Records captured per month, last 12 months" class="xl:col-span-1">
                <x-ui.chart :chart="$trendChart" label="Records captured per month" />
            </x-ui.panel>
            @foreach ($breakdownCharts as $b)
                <x-ui.panel :title="$b['title']" :subtitle="count($b['rows']).' value'.(count($b['rows']) === 1 ? '' : 's').' recorded'">
                    <x-ui.chart :chart="$b['chart']" :label="$b['title']" />
                </x-ui.panel>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Search records</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Any word: file number, name, plot, district… (not case-sensitive)</p>
                </div>
                @if ($activeFilters)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $activeFilters }} filter{{ $activeFilters === 1 ? '' : 's' }} on</span>
                @endif
            </div>
            <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-3">
                    <label for="r-q" class="{{ $label }}">Search term</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="r-q" type="search" name="q" value="{{ $q }}" placeholder="Enter search term…" class="{{ $field }} pl-9">
                    </div>
                </div>
                @foreach ($def['filters'] as $name => [$flabel])
                    <div>
                        <label for="r-{{ $name }}" class="{{ $label }}">{{ $flabel }}</label>
                        <select id="r-{{ $name }}" name="{{ $name }}" class="{{ $field }}">
                            <option value="">All</option>
                            @foreach ($options[$name] ?? [] as $opt)
                                <option value="{{ $opt }}" @selected(mb_strtolower($filters[$name]) === mb_strtolower($opt))>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="flex items-end gap-2 lg:col-start-6">
                    <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="search" class="h-4 w-4"></i> Search</button>
                    <a href="{{ url()->current() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Reset</a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <x-ui.panel :title="$def['title'].' records'" :subtitle="$rows ? 'Showing '.$n($rows->firstItem() ?? 0).'–'.$n($rows->lastItem() ?? 0).' of '.$n($rows->total()) : null" :padded="false">
            @if (! $rows || $rows->isEmpty())
                <x-ui.empty icon="search-x" :title="$q !== '' || $activeFilters ? 'No record matches the search' : 'No records yet'">
                    {{ $q !== '' || $activeFilters ? 'Try fewer words or clear the filters.' : 'KLAES has no records of this kind on this database yet.' }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">S/N</th>
                                @foreach ($def['columns'] as $col)
                                    <th class="whitespace-nowrap px-4 py-3">{{ $col[0] }}</th>
                                @endforeach
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 text-right dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $row)
                                @php
                                    $cells = [];
                                    foreach ($def['columns'] as $col) { $cells[$col[0]] = $records->cell($col, $row); }
                                    $sn = $rows->firstItem() + $loop->index;
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 tabular-nums text-slate-500 dark:text-slate-400">{{ $sn }}</td>
                                    @foreach ($def['columns'] as $col)
                                        @php $v = $cells[$col[0]]; $kind = $col[2] ?? 'text'; @endphp
                                        <td @class(['px-4 py-3 align-top', 'whitespace-nowrap' => in_array($kind, ['file', 'date', 'badge', 'status'], true)])>
                                            @if ($v === null)
                                                <span class="text-slate-300 dark:text-slate-600">—</span>
                                            @elseif ($kind === 'file')
                                                <span class="font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $v }}</span>
                                            @elseif ($kind === 'badge')
                                                <span class="{{ $badge }}">{{ $v }}</span>
                                            @elseif ($kind === 'status')
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusTone($v) }}">{{ $v }}</span>
                                            @else
                                                <span class="text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($v, 60) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action href="#" x-on:click.prevent="$dispatch('show-record', {{ \Illuminate\Support\Js::from(['title' => $def['title'].' — record '.$sn, 'fields' => $cells]) }})">
                                                <x-slot:icon><i data-lucide="eye" class="h-4 w-4"></i></x-slot:icon>
                                                View record
                                            </x-row-action>
                                            @php $fileValue = collect($def['columns'])->first(fn ($c) => ($c[2] ?? null) === 'file'); @endphp
                                            @if ($fileValue && $cells[$fileValue[0]])
                                                <x-row-action href="#" x-on:click.prevent="navigator.clipboard && navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($cells[$fileValue[0]]) }})">
                                                    <x-slot:icon><i data-lucide="clipboard-copy" class="h-4 w-4"></i></x-slot:icon>
                                                    Copy {{ mb_strtolower($fileValue[0]) }}
                                                </x-row-action>
                                                <x-row-action :href="url()->current().'?q='.urlencode($cells[$fileValue[0]])">
                                                    <x-slot:icon><i data-lucide="list-filter" class="h-4 w-4"></i></x-slot:icon>
                                                    All records of this file
                                                </x-row-action>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $rows->onEachSide(1)->links() }}</div>
            @endif
        </x-ui.panel>

        {{-- Record pop-up --}}
        <template x-teleport="body">
            <div x-show="record" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="record = null">
                <div @click.outside="record = null" class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h3 class="font-bold text-slate-900 dark:text-white" x-text="record && record.title"></h3>
                        <button type="button" @click="record = null" aria-label="Close" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <dl class="grid gap-x-6 gap-y-3 p-6 sm:grid-cols-2">
                        <template x-for="[k, v] in Object.entries(record ? record.fields : {})" :key="k">
                            <div>
                                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400" x-text="k"></dt>
                                <dd class="text-sm text-slate-800 dark:text-slate-200" x-text="v || '—'"></dd>
                            </div>
                        </template>
                    </dl>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
