{{-- GIS > GIS: the map viewer (KLAES GIS layout: map with base layers, a file search beside it and the picked file's
     particulars in KLAES order). Land files come from the KLAES selector (read-only); KLAES land files carry no geometry,
     so the viewer centres on Kano until the KANGIS parcel fabric source is agreed. --}}
<x-app-layout title="GIS">
    <div class="space-y-6">
        <x-ui.page-head icon="globe-2" title="GIS" subtitle="Map viewer: base layers, imagery and land-file lookup" tone="teal" section="GIS">
            <a href="{{ route('records.show', 'gis-records') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="table" class="h-4 w-4"></i> GIS Records</a>
        </x-ui.page-head>

        <div class="grid gap-6 xl:grid-cols-4">
            <div class="space-y-6 xl:col-span-1">
                <x-ui.panel title="Find a land file"><x-ui.file-picker name="file_id" label="Land file (KLAES)" :required="false" /></x-ui.panel>
                <x-ui.panel title="Layers">
                    <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-sky-500"></span> Street map (OpenStreetMap)</li>
                        <li class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-600"></span> Satellite imagery (Esri)</li>
                        <li class="flex items-center gap-2 text-slate-400 dark:text-slate-500"><span class="h-3 w-3 rounded-sm border border-dashed border-slate-400"></span> KANGIS parcel fabric (source to be agreed)</li>
                        <li class="flex items-center gap-2 text-slate-400 dark:text-slate-500"><span class="h-3 w-3 rounded-sm border border-dashed border-slate-400"></span> KSI site visit points (after a site visit is saved)</li>
                    </ul>
                </x-ui.panel>
            </div>
            <x-ui.panel class="xl:col-span-3" :padded="false">
                <div id="kamma-gis-map" class="h-[36rem] w-full rounded-2xl" role="application" aria-label="GIS map viewer"></div>
            </x-ui.panel>
        </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (! window.L) return;
                const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' });
                const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '&copy; Esri' });
                const map = L.map('kamma-gis-map', { layers: [streets] }).setView([12.0022, 8.5920], 11);
                L.control.layers({ 'Street map': streets, 'Satellite imagery': imagery }).addTo(map);
                L.control.scale({ metric: true, imperial: false }).addTo(map);
                const coords = L.control({ position: 'bottomleft' });
                coords.onAdd = () => { const d = L.DomUtil.create('div'); d.style.cssText = 'background:rgba(255,255,255,.85);padding:2px 6px;border-radius:4px;font:11px monospace;color:#0f172a'; return d; };
                coords.addTo(map);
                map.on('mousemove', (e) => coords.getContainer().textContent = e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6));
            });
        </script>
    @endpush
</x-app-layout>
