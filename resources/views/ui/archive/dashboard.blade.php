{{-- Digital File Archive > Dashboard: the DMS pipeline (indexed → scanned → page typed → archived) for KAMMA's shadow files. --}}
<x-app-layout title="Digital File Archive">
    @php
        $n = fn ($v) => number_format((int) $v);
        $size = function (int $b) { foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $u) { if ($b < 1024) return round($b, 1).' '.$u; $b /= 1024; } return round($b, 1).' PB'; };
        $funnel = ['type' => 'bar', 'labels' => ['Indexed', 'Scanned', 'Page typed', 'Archived'],
            'series' => [['label' => 'Files', 'data' => [$indexing['total'] ?? 0, $scan['files'] ?? 0, $indexing['typed'] ?? 0, $archive['files'] ?? 0]]]];
        $typeChart = ['type' => 'doughnut', 'labels' => array_keys($types), 'series' => [['label' => 'Pages', 'data' => array_values($types)]]];
        $ptLabels = ['pending' => 'Awaiting typing', 'progress' => 'In progress', 'completed' => 'Completed', 'more' => 'PageType More'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="archive" title="Digital File Archive" subtitle="Indexing, scanning, page typing and the archive at a glance" tone="emerald" section="Digital File Archive">
            <a href="{{ route('archive.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="library" class="h-4 w-4"></i> Doc-WARE</a>
            <a href="{{ route('folio.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="folder-tree" class="h-4 w-4"></i> Folio</a>
        </x-ui.page-head>
        @if ($error)<x-ui.klaes-note :error="$error" />@endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Indexed files" :value="$n($indexing['total'] ?? 0)" :hint="$n($indexing['today'] ?? 0).' today · '.$n($indexing['month'] ?? 0).' this month'" icon="folder-input" tone="blue" :href="route('dms.indexing.index')" />
            <x-ui.stat title="Scanned pages" :value="$n($scan['pages'] ?? 0)" :hint="$n($scan['files'] ?? 0).' files scanned · '.$n($scan['today'] ?? 0).' pages today'" icon="scan-line" tone="cyan" :href="route('dms.scanning.index')" />
            <x-ui.stat title="Typed pages" :value="$n($archive['pages'] ?? 0)" :hint="$n($scan['pending'] ?? 0).' files with untyped pages'" icon="type" tone="violet" :href="route('dms.pagetyping.index')" />
            <x-ui.stat title="Archive size" :value="$size((int) ($archive['bytes'] ?? 0))" :hint="$n($archive['files'] ?? 0).' archived files · '.$n($archive['recent'] ?? 0).' in 30 days'" icon="hard-drive" tone="emerald" :href="route('archive.index')" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="DMS pipeline" subtitle="Files at each step" class="xl:col-span-2"><x-ui.chart :chart="$funnel" label="Files at each DMS step" /></x-ui.panel>
            <x-ui.panel title="Pages by document type"><x-ui.chart :chart="$typeChart" label="Pages by document type" /></x-ui.panel>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.panel title="Page Typing queue" :padded="false">
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($ptLabels as $k => $l)
                        <li class="flex items-center justify-between px-6 py-3 text-sm">
                            <span class="text-slate-700 dark:text-slate-300">{{ $l }}</span>
                            <span class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $n($pt[$k] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.panel>
            <x-ui.panel title="Files by registry" :padded="false">
                @forelse ($byRegistry as $k => $v)
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-3 text-sm last:border-0 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300">{{ $k }}</span><span class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $n($v) }}</span>
                    </div>
                @empty
                    <x-ui.empty icon="folders" title="No indexed files yet" />
                @endforelse
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
