{{-- GIS > Approvals. KLAES's own GIS Approvals item is a dead link, so this is a fresh design in KLAES approval-page style
     (Awaiting / Approved / Declined tabs, three-dots row menu): KSI plots and extracted survey plans waiting for the
     GIS unit to confirm them against the base map. Nothing is charted yet (Charting saves are preview only). --}}
<x-app-layout title="GIS Approvals">
    <div class="space-y-6" x-data="{ tab: 'awaiting' }">
        <x-ui.page-head icon="map-pinned" title="GIS Approvals" subtitle="Confirm charted plots and extracted survey plans" tone="teal" section="GIS" />
        <x-ui.preview-note what="Approving or declining a GIS submission" />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Awaiting approval" value="0" icon="hourglass" tone="amber" />
            <x-ui.stat title="Approved" value="0" icon="check-circle-2" tone="emerald" />
            <x-ui.stat title="Declined" value="0" icon="x-circle" tone="rose" />
        </div>

        <div class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @foreach (['awaiting' => 'Awaiting', 'approved' => 'Approved', 'declined' => 'Declined'] as $k => $l)
                <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'" class="flex-1 rounded-lg px-3 py-2 font-medium">{{ $l }}</button>
            @endforeach
        </div>

        <x-ui.panel :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>@foreach (['S/N', 'Submission', 'File No.', 'Plot No.', 'District', 'LGA', 'Location', 'Area (m²)', 'Source', 'Submitted By', 'Date'] as $h)<th class="whitespace-nowrap px-4 py-3">{{ $h }}</th>@endforeach<th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="map-pinned" title="Nothing here yet">Plots saved under KAMMA › GIS › Charting and plans saved under Survey Plan Extraction come here for the GIS unit's decision.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
