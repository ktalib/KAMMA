{{-- KAMMA > Site Visits: geocoded photographs on a shadow file (KAMMA Workflow v1.0 phase 2; the field app opens this page
     in its WebView). The camera photo is sent with the device's GPS position and capture time; no position, no save. --}}
<x-app-layout title="Site Visits">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-4 py-3';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="camera" title="Site Visits" section="KAMMA"
                        subtitle="Initial Site Visit and Site Assessment photographs, each with the GPS position and time taken on the device at the moment of capture.">
            @if ($file)
                <a href="{{ route('kamma.shadow-files.show', $file->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="folder-open" class="h-4 w-4"></i> {{ $file->file_number }}</a>
            @endif
        </x-ui.page-head>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <p class="font-semibold">The photo was not saved.</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Geocoded photos" :value="number_format($counts['photos'])" icon="image" tone="blue" />
            <x-ui.stat title="Shadow files visited" :value="number_format($counts['files'])" icon="folder-check" tone="emerald" />
            <x-ui.stat title="Taken today" :value="number_format($counts['today'])" icon="calendar-check" tone="amber" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Capture --}}
            <x-ui.panel title="Take a site photo" subtitle="Use the device camera on site; location must be allowed.">
                <form method="POST" action="{{ route('kamma.site-visits.store') }}" enctype="multipart/form-data" class="space-y-4"
                      x-data="{
                          lat: '', lng: '', acc: '', at: '', status: '', preview: null, picked: @js($file ? ['id' => $file->id, 'file_number' => $file->file_number] : null),
                          capture(e) {
                              const f = e.target.files[0];
                              if (! f) return;
                              this.preview = URL.createObjectURL(f);
                              this.at = new Date().toISOString();
                              if (! navigator.geolocation) { this.status = 'This device cannot give a GPS position.'; return; }
                              this.status = 'Getting the GPS position…';
                              navigator.geolocation.getCurrentPosition(
                                  (p) => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7); this.acc = Math.round(p.coords.accuracy); this.status = ''; },
                                  () => { this.status = 'Location was refused or unavailable. Allow location and take the photo again.'; },
                                  { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
                          },
                      }" @file-picked="picked = $event.detail">
                    @csrf
                    @if ($file)
                        <input type="hidden" name="file_indexing_id" value="{{ $file->id }}">
                        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm dark:bg-emerald-500/10">
                            <p class="font-mono font-bold text-emerald-800 dark:text-emerald-300">{{ $file->file_number }}</p>
                            <p class="text-xs text-emerald-700 dark:text-emerald-400">{{ collect([$file->plot_number ? 'Plot '.$file->plot_number : null, $file->district, $file->lga, $file->location])->filter()->implode(' · ') }}</p>
                        </div>
                    @else
                        <x-ui.file-picker name="file_indexing_id" source="shadow" label="Shadow file" />
                    @endif
                    <div>
                        <label for="sv-stage" class="{{ $label }}">Visit <span class="text-rose-500">*</span></label>
                        <select id="sv-stage" name="stage" required class="{{ $field }}">
                            @foreach ($stages as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sv-photo" class="{{ $label }}">Photo <span class="text-rose-500">*</span></label>
                        <input id="sv-photo" type="file" name="photo" accept="image/*" capture="environment" required @change="capture($event)"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-500 dark:text-slate-300">
                    </div>
                    <template x-if="preview"><img :src="preview" alt="Photo preview" class="max-h-48 w-full rounded-lg object-cover"></template>
                    <input type="hidden" name="latitude" :value="lat"><input type="hidden" name="longitude" :value="lng">
                    <input type="hidden" name="accuracy_m" :value="acc"><input type="hidden" name="taken_at" :value="at">
                    <dl class="grid grid-cols-3 gap-2 rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800/60">
                        <div><dt class="font-semibold text-slate-500 dark:text-slate-400">Latitude</dt><dd class="font-mono text-slate-800 dark:text-slate-100" x-text="lat || '—'"></dd></div>
                        <div><dt class="font-semibold text-slate-500 dark:text-slate-400">Longitude</dt><dd class="font-mono text-slate-800 dark:text-slate-100" x-text="lng || '—'"></dd></div>
                        <div><dt class="font-semibold text-slate-500 dark:text-slate-400">Accuracy</dt><dd class="font-mono text-slate-800 dark:text-slate-100" x-text="acc ? acc + ' m' : '—'"></dd></div>
                    </dl>
                    <p x-show="status" x-text="status" class="text-xs text-amber-700 dark:text-amber-300"></p>
                    <button :disabled="! lat || ! preview" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">Save geocoded photo</button>
                </form>
            </x-ui.panel>

            {{-- Register --}}
            <x-ui.panel :title="$file ? 'Photos on '.$file->file_number : 'All site photos'" class="xl:col-span-2" :padded="false">
                @if ($photos->isEmpty())
                    <x-ui.empty icon="camera" title="No geocoded photos yet">Take the first photo on site, or open the KLAES-METRO field app.</x-ui.empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Photo</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">Visit</th><th class="{{ $th }}">GPS</th><th class="{{ $th }}">Accuracy</th><th class="{{ $th }}">Taken</th><th class="{{ $th }}">Officer</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($photos as $p)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-500">{{ $photos->firstItem() + $loop->index }}</td>
                                        <td class="px-4 py-2"><img src="{{ route('kamma.site-visits.photo', $p->id) }}" alt="Site photo" class="h-12 w-16 rounded object-cover"></td>
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $p->file?->file_number ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">{{ $stages[$p->stage] ?? $p->stage }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ number_format((float) $p->latitude, 6) }}, {{ number_format((float) $p->longitude, 6) }}</td>
                                        <td class="px-4 py-3">{{ $p->accuracy_m !== null ? round((float) $p->accuracy_m).' m' : '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $p->taken_at?->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3">{{ $p->uploaded_by_name }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <x-row-actions>
                                                <x-row-action :href="route('kamma.site-visits.photo', $p->id)" target="_blank"><x-slot:icon><i data-lucide="image" class="h-4 w-4"></i></x-slot:icon> Open photo</x-row-action>
                                                <x-row-action :href="'https://www.openstreetmap.org/?mlat='.$p->latitude.'&mlon='.$p->longitude.'#map=18/'.$p->latitude.'/'.$p->longitude" target="_blank" rel="noopener"><x-slot:icon><i data-lucide="map" class="h-4 w-4"></i></x-slot:icon> Show on map</x-row-action>
                                                @if ($p->file)
                                                    <x-row-action :href="route('kamma.shadow-files.show', $p->file_indexing_id)"><x-slot:icon><i data-lucide="folder-open" class="h-4 w-4"></i></x-slot:icon> Open shadow file</x-row-action>
                                                @endif
                                            </x-row-actions>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $photos->links() }}</div>
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
