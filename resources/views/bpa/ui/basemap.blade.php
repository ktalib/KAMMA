{{-- KAMMA > GIS > BaseMap: the base map KAMMA works on (street map / satellite imagery over Kano), with the commissioned BP
     files listed beside it and the charted plots drawn once Charting is saved. Fresh design in KLAES style (KLAES GIS map
     pages use Leaflet too). Leaflet from cdnjs; tiles from OpenStreetMap and Esri World Imagery. --}}
<x-app-layout title="BaseMap">
    <div class="space-y-6">
        <x-ui.page-head icon="map" title="BaseMap" subtitle="Kano base map for Building Plan Approval: streets, imagery and charted BP plots" tone="teal" section="KAMMA › GIS">
            <a href="{{ route('bpa.charting') }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-500"><i data-lucide="pen-tool" class="h-4 w-4"></i> Chart a plot</a>
        </x-ui.page-head>

        <div class="grid gap-6 xl:grid-cols-4">
            <x-ui.panel class="xl:col-span-3" :padded="false">
                <div id="bpa-basemap" class="h-[32rem] w-full rounded-2xl" role="application" aria-label="Base map of Kano"></div>
            </x-ui.panel>
            <x-ui.panel title="BP files" :subtitle="number_format($files->count()).' commissioned'" :padded="false">
                @if ($files->isEmpty())
                    <x-ui.empty icon="folder" title="No BP file yet">Commissioned files appear here.</x-ui.empty>
                @else
                    <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto text-sm dark:divide-slate-800">
                        @foreach ($files as $a)
                            <li class="px-4 py-3">
                                <p class="font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $a->bp_file_no }}</p>
                                <p class="text-slate-800 dark:text-slate-200">{{ $a->developer_name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ collect(['Plot '.$a->plot_no, $a->district, $a->lga, $a->location])->filter(fn ($v) => $v && $v !== 'Plot ')->implode(' · ') }}</p>
                                <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">Not charted yet</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.panel>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">Map data &copy; OpenStreetMap contributors; imagery &copy; Esri. Parcel fabric from KANGIS will be added once its source is agreed.</p>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (! window.L) return;
                const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' });
                const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '&copy; Esri' });
                const map = L.map('bpa-basemap', { layers: [streets] }).setView([12.0022, 8.5920], 12);
                L.control.layers({ 'Street map': streets, 'Satellite imagery': imagery }).addTo(map);
                L.control.scale({ metric: true, imperial: false }).addTo(map);
            });
        </script>
    @endpush
</x-app-layout>
