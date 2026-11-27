{{-- KAMMA > GIS > Charting: chart a BP file's plot on the base map from its beacon coordinates (as KLAES Cadastral/GIS Digital
     Plotting: beacon rows Point / X / Y, coordinate system, the polygon drawn and its area). Fresh layout in KLAES style.
     proj4 converts Minna / UTM 32N and WGS 84 / UTM 32N to lat/lng in the browser. Saves post to the preview stub. --}}
<x-app-layout title="Charting">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="pen-tool" title="Charting" subtitle="Chart a BP plot from its beacon coordinates" tone="teal" section="KAMMA › GIS">
            <a href="{{ route('bpa.basemap') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="map" class="h-4 w-4"></i> BaseMap</a>
        </x-ui.page-head>
        <x-ui.preview-note what="Saving a charted plot" />

        <form method="POST" action="{{ route('ui.preview', 'gis-charting') }}" x-data="chartingForm()" class="grid gap-6 xl:grid-cols-5">
            @csrf
            <div class="space-y-6 xl:col-span-2">
                <x-ui.panel title="BP file"><x-ui.file-picker name="bpa_application_id" source="bp" label="BP file" /></x-ui.panel>

                <x-ui.panel title="Beacon coordinates" subtitle="In order round the plot; at least 3 beacons">
                    <div class="mb-4">
                        <label for="ch-crs" class="{{ $label }}">Coordinate system <span class="text-rose-500">*</span></label>
                        <select id="ch-crs" name="crs" x-model="crs" @change="draw()" class="{{ $field }}">
                            <option value="EPSG:26332">Minna / UTM zone 32N (EPSG:26332)</option>
                            <option value="EPSG:32632">WGS 84 / UTM zone 32N (EPSG:32632)</option>
                            <option value="EPSG:4326">WGS 84 latitude / longitude (EPSG:4326)</option>
                        </select>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <tr><th class="pb-2 pr-2">S/N</th><th class="pb-2 pr-2">Beacon No.</th><th class="pb-2 pr-2" x-text="crs === 'EPSG:4326' ? 'Longitude' : 'X (Easting)'"></th><th class="pb-2 pr-2" x-text="crs === 'EPSG:4326' ? 'Latitude' : 'Y (Northing)'"></th><th></th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(b, i) in beacons" :key="b.key">
                                <tr>
                                    <td class="py-1 pr-2 text-slate-500 dark:text-slate-400" x-text="i + 1"></td>
                                    <td class="py-1 pr-2"><input :name="`beacons[${i}][no]`" x-model="b.no" required class="{{ $field }}"></td>
                                    <td class="py-1 pr-2"><input :name="`beacons[${i}][x]`" x-model="b.x" @input.debounce.400ms="draw()" type="number" step="any" required class="{{ $field }}"></td>
                                    <td class="py-1 pr-2"><input :name="`beacons[${i}][y]`" x-model="b.y" @input.debounce.400ms="draw()" type="number" step="any" required class="{{ $field }}"></td>
                                    <td class="py-1"><button type="button" @click="beacons.splice(i, 1); draw()" x-show="beacons.length > 3" class="rounded p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="Remove beacon">&times;</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button type="button" @click="add()" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-teal-700 hover:underline dark:text-teal-300">+ Add beacon</button>
                    <dl class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">
                        <div><dt class="{{ $label }}">Computed area</dt><dd class="font-semibold tabular-nums" x-text="area ? area.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' m²' : '—'"></dd></div>
                        <div><dt class="{{ $label }}">Perimeter</dt><dd class="font-semibold tabular-nums" x-text="perimeter ? perimeter.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' m' : '—'"></dd></div>
                    </dl>
                    <input type="hidden" name="area_sqm" :value="area ? area.toFixed(2) : ''">
                    <div class="mt-4"><label for="ch-rem" class="{{ $label }}">Remarks</label><textarea id="ch-rem" name="remarks" rows="2" class="{{ $field }}"></textarea></div>
                    <div class="mt-4 flex justify-end"><button class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-500">Save charting</button></div>
                </x-ui.panel>
            </div>
            <x-ui.panel class="xl:col-span-3" :padded="false">
                <div id="bpa-chart-map" class="h-[36rem] w-full rounded-2xl" role="application" aria-label="Charting map"></div>
            </x-ui.panel>
        </form>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.11.0/proj4.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            function chartingForm() {
                let map = null, layer = null;
                const blank = (n) => ({ key: Date.now() + Math.random(), no: 'PB ' + n, x: '', y: '' });
                return {
                    crs: 'EPSG:26332', beacons: [blank(1), blank(2), blank(3), blank(4)], area: 0, perimeter: 0,
                    init() {
                        if (window.proj4) {
                            proj4.defs('EPSG:26332', '+proj=utm +zone=32 +ellps=clrk80 +towgs84=-92,-93,122,0,0,0,0 +units=m +no_defs');
                            proj4.defs('EPSG:32632', '+proj=utm +zone=32 +datum=WGS84 +units=m +no_defs');
                        }
                        if (! window.L) return;
                        const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20, attribution: '&copy; OpenStreetMap' });
                        const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: '&copy; Esri' });
                        map = L.map('bpa-chart-map', { layers: [imagery] }).setView([12.0022, 8.5920], 13);
                        L.control.layers({ 'Satellite imagery': imagery, 'Street map': streets }).addTo(map);
                    },
                    add() { this.beacons.push(blank(this.beacons.length + 1)); },
                    toLatLng(b) {
                        const x = parseFloat(b.x), y = parseFloat(b.y);
                        if (isNaN(x) || isNaN(y)) return null;
                        if (this.crs === 'EPSG:4326') return [y, x];
                        if (! window.proj4) return null;
                        const [lng, lat] = proj4(this.crs, 'EPSG:4326', [x, y]);
                        return [lat, lng];
                    },
                    draw() {
                        const pts = this.beacons.map((b) => this.toLatLng(b)).filter(Boolean);
                        this.measure();
                        if (! map) return;
                        if (layer) { map.removeLayer(layer); layer = null; }
                        if (pts.length < 3) return;
                        layer = L.featureGroup([L.polygon(pts, { color: '#0d9488', weight: 2, fillOpacity: .25 }),
                            ...pts.map((p, i) => L.circleMarker(p, { radius: 4, color: '#f59e0b' }).bindTooltip(this.beacons[i]?.no || ''))]).addTo(map);
                        map.fitBounds(layer.getBounds(), { padding: [40, 40] });
                    },
                    measure() {
                        // Area and perimeter on the projected grid (metres); lat/lng is projected to UTM 32N first.
                        const xy = this.beacons.map((b) => {
                            const x = parseFloat(b.x), y = parseFloat(b.y);
                            if (isNaN(x) || isNaN(y)) return null;
                            if (this.crs !== 'EPSG:4326') return [x, y];
                            return window.proj4 ? proj4('EPSG:4326', 'EPSG:32632', [x, y]) : null;
                        }).filter(Boolean);
                        if (xy.length < 3) { this.area = 0; this.perimeter = 0; return; }
                        let a = 0, p = 0;
                        xy.forEach(([x1, y1], i) => { const [x2, y2] = xy[(i + 1) % xy.length]; a += x1 * y2 - x2 * y1; p += Math.hypot(x2 - x1, y2 - y1); });
                        this.area = Math.abs(a) / 2; this.perimeter = p;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
