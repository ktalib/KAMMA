{{-- KAMMA > Development Permit > Dashboard: the Building Plan Approval pipeline in the order of the briefing diagram (Application →
     Architecture → Director → Deputy Director → Zonal Office → site visit / inspection → Conveyance), with the files now at each desk. --}}
<x-app-layout title="Development Permit Dashboard">
    @php
        $pipeline = \App\Http\Controllers\Bpa\BpaUiController::PIPELINE;
        $link = function (string $spec) {
            [$name, $param] = array_pad(explode(':', $spec, 2), 2, null);
            return $param ? route($name, $param) : route($name);
        };
        $stageChart = ['type' => 'bar', 'labels' => array_map(fn ($p) => $p[0], array_slice($pipeline, 0, 5)),
            'series' => [['label' => 'Files', 'data' => array_map(fn ($p) => (int) ($byStage[$p[3]] ?? 0), array_slice($pipeline, 0, 5))]]];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="drafting-compass" title="KAMMA Dashboard" subtitle="Building Plan Approval: where every file is in the approval flow" tone="indigo" section="KAMMA">
            <a href="{{ route('bpa.applications.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="plus" class="h-4 w-4"></i> New application</a>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Applications" :value="number_format($total)" hint="Received at the Assessment Stage" icon="file-text" tone="blue" :href="route('bpa.applications.index')" />
            <x-ui.stat title="BP files commissioned" :value="number_format($commissioned)" hint="BP-YYYY-n numbers issued" icon="folder-plus" tone="emerald" :href="route('bpa.building-plans.index')" />
            <x-ui.stat title="At the Zonal Office" :value="number_format($byStage[\App\Support\Bpa\BpaStage::ZONAL_OFFICE] ?? 0)" hint="Awaiting inspection" icon="map-pinned" tone="violet" :href="route('bpa.zonal-approval')" />
            <x-ui.stat title="Land-use mismatches" :value="number_format($mismatch)" hint="Flagged, not blocked" icon="flag" tone="rose" :href="route('bpa.applications.index', ['land_use_check' => 'Mismatch'])" />
        </div>

        <x-ui.panel title="Approval flow" subtitle="Click a step to open its desk">
            <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($pipeline as $i => [$label, $icon, $route, $stage])
                    <li>
                        <a href="{{ $link($route) }}" class="flex h-full flex-col rounded-xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-slate-700 dark:hover:border-indigo-500/50">
                            <span class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span>
                                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Step {{ $i + 1 }}</span>
                            </span>
                            <span class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $label }}</span>
                            <span class="mt-auto pt-2 text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ $stage ? number_format($byStage[$stage] ?? 0) : '—' }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </x-ui.panel>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Files per desk" class="xl:col-span-1"><x-ui.chart :chart="$stageChart" label="Files per desk" /></x-ui.panel>
            <x-ui.panel title="Latest files at the Zonal Office" class="xl:col-span-2" :padded="false">
                @if ($zonal->isEmpty())
                    <x-ui.empty icon="map-pinned" title="No file at the Zonal Office">Files arrive here when the Deputy Director (Arch.) recommends them.</x-ui.empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">BP File No.</th><th class="px-4 py-3">Developer</th><th class="px-4 py-3">Plot No.</th><th class="px-4 py-3">District</th><th class="px-4 py-3">LGA</th><th class="px-4 py-3">Location</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($zonal as $a)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $a->bp_file_no }}</td>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $a->developer_name }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $a->plot_no ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $a->district ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $a->lga ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $a->location ?: '—' }}</td>
                                        <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                            <x-row-actions>
                                                <x-row-action :href="route('bpa.applications.show', $a)"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> Open application</x-row-action>
                                                <x-row-action :href="route('bpa.inspection', ['file' => $a->id])"><x-slot:icon><i data-lucide="camera" class="h-4 w-4"></i></x-slot:icon> Inspection report</x-row-action>
                                            </x-row-actions>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
