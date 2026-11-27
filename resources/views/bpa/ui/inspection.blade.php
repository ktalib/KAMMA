{{-- KAMMA > Architecture > Zonal Officer > Inspection. The field officer's Site Inspection Report on a BP file at the Zonal
     Office: Initial Site Visit with geocoded photos (GPS + time taken by the browser / the KLAES-METRO WebView app), then the
     Site Analysis Report of the KAMMA application form (p.3, Development Permit Division, items 1.0 and 2.0 word for word), the
     site assessment measurements (briefing: "Inspection / Site Assessment (measurements)") and the officer's recommendation.
     Fresh design in KLAES style (KLAES has no BPA inspection); a wizard because the form is long. Saves through
     Bpa\ZonalController::storeInspection; the file then goes to the Head of Zonal Office for review. --}}
<x-app-layout title="Site Inspection">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $radio = 'text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800';
        $choice = 'flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200';
        $steps = ['BP file', 'Initial site visit', '1.0 Location details', '2.0 Site details', 'Site assessment', 'Review'];
        $filesJs = $files->map(fn ($a) => ['id' => $a->id, 'bp' => $a->bp_file_no, 'land' => $a->file_number, 'developer' => $a->developer_name,
            'plot_no' => $a->plot_no, 'district' => $a->district, 'lga' => $a->lga, 'location' => $a->location, 'zone' => $a->cadastral_zone,
            'land_use' => $a->file_land_use, 'plot_size' => $a->plot_size, 'street' => $a->street_name, 'use' => $a->proposed_use])->values();
        $user = auth()->user();
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="camera" title="Site Inspection Report" subtitle="Initial site visit with geocoded photos, site analysis and assessment" tone="violet" section="KAMMA › Architecture › Zonal Officer" />
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <p class="font-semibold">The report was not filed. Correct these and add the photos again:</p>
                <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bpa.inspection.store') }}" enctype="multipart/form-data" novalidate
              x-data="formWizard(inspectionForm(@js($filesJs), @js((int) old('bpa_application_id', $selected?->id) ?: null)), { steps: {{ count($steps) }} })" @submit="submitAll($event)" class="space-y-6">
            @csrf
            <x-wizard-steps :steps="$steps" />

            {{-- 1. BP file --}}
            <x-ui.panel title="BP file" subtitle="Files the Head of Zonal Office has assigned for inspection" data-step="1" x-show="step === 1">
                @if ($files->isEmpty())
                    <x-ui.empty icon="map-pinned" title="No file assigned for inspection">A file can be inspected once the Head of Zonal Office assigns it to an officer.</x-ui.empty>
                @else
                    <label for="in-file" class="{{ $label }}">BP file <span class="text-rose-500">*</span></label>
                    <select id="in-file" name="bpa_application_id" x-model.number="fileId" required class="{{ $field }}">
                        <option value="">Select a BP file…</option>
                        @foreach ($files as $a)
                            <option value="{{ $a->id }}">{{ $a->bp_file_no }} · {{ $a->developer_name }} · land file {{ $a->file_number }}{{ ($as = $a->assignments->first()) ? ' · '.$as->instruction_no.', '.$as->officer_name : '' }}</option>
                        @endforeach
                    </select>
                    <div x-show="file" x-cloak class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <template x-for="[k, v] in [['Plot No.', file?.plot_no], ['District', file?.district], ['LGA', file?.lga], ['Location', file?.location]]" :key="k">
                            <div><span class="{{ $label }}" x-text="k"></span><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200" x-text="v || '—'"></div></div>
                        </template>
                    </div>
                @endif
            </x-ui.panel>

            {{-- 2. Initial site visit --}}
            <x-ui.panel title="Initial site visit" subtitle="Each photo carries the GPS position and time it was taken" data-step="2" x-show="step === 2" x-cloak>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label for="in-vdate" class="{{ $label }}">Date of visit <span class="text-rose-500">*</span></label><input id="in-vdate" type="date" name="visit_date" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="{{ $field }}"></div>
                    <div><label for="in-vtime" class="{{ $label }}">Time of arrival</label><input id="in-vtime" type="time" name="visit_time" class="{{ $field }}"></div>
                    <div><label for="in-met" class="{{ $label }}">Person met on site</label><input id="in-met" name="person_met" class="{{ $field }}" placeholder="Owner, developer, site engineer…"></div>
                </div>

                <div class="mt-5 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Geocoded site photographs <span class="text-rose-500">*</span></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Take them on site: the phone's location must be on. At least one photo.</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                            <i data-lucide="camera" class="h-4 w-4"></i> Take / add photo
                            <input type="file" accept="image/*" capture="environment" multiple class="sr-only" @change="addPhotos($event)">
                        </label>
                    </div>
                    <input type="file" name="photos[]" multiple x-ref="photos" class="hidden">
                    <p x-show="geoError" x-cloak class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300" x-text="geoError"></p>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <template x-for="(p, i) in photos" :key="p.key">
                            <li class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                                <img :src="p.url" alt="" class="h-32 w-full object-cover">
                                <div class="space-y-0.5 p-2 text-[11px] text-slate-600 dark:text-slate-300">
                                    <p><span class="font-semibold">Lat/Lng:</span> <span x-text="p.lat ? p.lat.toFixed(6) + ', ' + p.lng.toFixed(6) : 'no position'"></span></p>
                                    <p><span class="font-semibold">Accuracy:</span> <span x-text="p.acc ? Math.round(p.acc) + ' m' : '—'"></span></p>
                                    <p><span class="font-semibold">Taken:</span> <span x-text="p.at"></span></p>
                                    <input type="hidden" name="photo_lat[]" :value="p.lat ?? ''"><input type="hidden" name="photo_lng[]" :value="p.lng ?? ''"><input type="hidden" name="photo_taken_at[]" :value="p.iso">
                                    <button type="button" @click="removePhoto(i)" class="mt-1 text-rose-600 hover:underline dark:text-rose-400">Remove</button>
                                </div>
                            </li>
                        </template>
                    </ul>
                    <p x-show="photos.length === 0" class="mt-3 text-xs text-slate-400 dark:text-slate-500">No photo yet.</p>
                </div>
                <div class="mt-4"><label for="in-vnote" class="{{ $label }}">Visit notes</label><textarea id="in-vnote" name="visit_notes" rows="3" class="{{ $field }}"></textarea></div>
            </x-ui.panel>

            {{-- 3. 1.0 Location details (Site Analysis Report) --}}
            <x-ui.panel title="1.0 Location details" subtitle="Site Analysis Report, Development Permit Division" data-step="3" x-show="step === 3" x-cloak>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="in-plot" class="{{ $label }}">Plot No <span class="text-rose-500">*</span></label><input id="in-plot" name="plot_no" x-model="loc.plot_no" required class="{{ $field }}"></div>
                    <div><label for="in-dist" class="{{ $label }}">District <span class="text-rose-500">*</span></label><input id="in-dist" name="district" x-model="loc.district" required class="{{ $field }}"></div>
                    <div><label for="in-zone" class="{{ $label }}">Cadastral Zone</label><input id="in-zone" name="cadastral_zone" x-model="loc.zone" class="{{ $field }}"></div>
                    <div><label for="in-lu" class="{{ $label }}">Land use <span class="text-rose-500">*</span></label><input id="in-lu" name="land_use" x-model="loc.land_use" required class="{{ $field }}"></div>
                    <div class="sm:col-span-2"><label for="in-pc" class="{{ $label }}">Purpose clause</label><input id="in-pc" name="purpose_clause" class="{{ $field }}"></div>
                    <div><label for="in-size" class="{{ $label }}">Plot size</label><input id="in-size" name="plot_size" x-model="loc.plot_size" class="{{ $field }}"></div>
                    <div><label for="in-den" class="{{ $label }}">Density</label><input id="in-den" name="density" class="{{ $field }}"></div>
                    <div class="sm:col-span-2"><label for="in-street" class="{{ $label }}">Street Name (if any)</label><input id="in-street" name="street_name" x-model="loc.street" class="{{ $field }}"></div>
                </div>
            </x-ui.panel>

            {{-- 4. 2.0 Site details --}}
            <x-ui.panel title="2.0 Site details" data-step="4" x-show="step === 4" x-cloak>
                <div class="space-y-5">
                    <fieldset>
                        <legend class="{{ $label }}">2.1a Nature of plot <span class="text-rose-500">*</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Well drained', 'Rocky', 'Water logged / marshy', 'Other'] as $o)
                                <label class="{{ $choice }}"><input type="radio" name="nature_of_plot" value="{{ $o }}" x-model="nature" required class="{{ $radio }}"> {{ $o === 'Other' ? 'Others (specify)' : $o }}</label>
                            @endforeach
                        </div>
                        <div x-show="nature === 'Other'" x-cloak class="mt-2"><label for="in-nat-o" class="{{ $label }}">Specify nature of plot <span class="text-rose-500">*</span></label><input id="in-nat-o" name="nature_of_plot_other" :required="nature === 'Other'" :disabled="nature !== 'Other'" class="{{ $field }}"></div>
                    </fieldset>
                    <fieldset>
                        <legend class="{{ $label }}">2.1b Estimated slope <span class="text-rose-500">*</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Below 5% (relatively flat)', '5%-8% (sloppy)', '8%-12% (steep)', '12%-18% (very steep)', 'Above 18% (hilly)'] as $o)
                                <label class="{{ $choice }}"><input type="radio" name="slope" value="{{ $o }}" required class="{{ $radio }}"> {{ $o }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="{{ $label }}">2.2 Vacancy of plot <span class="text-rose-500">*</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Vacant', 'Not vacant'] as $o)
                                <label class="{{ $choice }}"><input type="radio" name="vacancy" value="{{ $o }}" x-model="vacancy" required class="{{ $radio }}"> {{ $o }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset x-show="vacancy === 'Not vacant'" x-cloak class="space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                        <legend class="sr-only">2.3 If not vacant</legend>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">2.3 If not vacant</p>
                        <div><label for="in-dev" class="{{ $label }}">[a] Describe the development on site <span class="text-rose-500">*</span></label><textarea id="in-dev" name="existing_development" rows="2" :required="vacancy === 'Not vacant'" :disabled="vacancy !== 'Not vacant'" class="{{ $field }}"></textarea></div>
                        <div>
                            <span class="{{ $label }}">[b] Is the development approved earlier? <span class="text-rose-500">*</span></span>
                            <div class="flex gap-2">
                                @foreach (['Yes', 'No'] as $o)
                                    <label class="{{ $choice }}"><input type="radio" name="approved_earlier" value="{{ $o }}" :required="vacancy === 'Not vacant'" :disabled="vacancy !== 'Not vacant'" class="{{ $radio }}"> {{ $o }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div><label for="in-conf" class="{{ $label }}">[c] State conformity of development with submission</label><select id="in-conf" name="conformity" x-model="conformity" :disabled="vacancy !== 'Not vacant'" class="{{ $field }}"><option value="">Select…</option><option>Conforms</option><option>Does not conform</option></select></div>
                        <div x-show="conformity === 'Does not conform'" x-cloak><label for="in-nonconf" class="{{ $label }}">[d] If no conformity, specify the nature of the non-conformity <span class="text-rose-500">*</span></label><textarea id="in-nonconf" name="non_conformity" rows="2" :required="conformity === 'Does not conform'" :disabled="conformity !== 'Does not conform' || vacancy !== 'Not vacant'" class="{{ $field }}"></textarea></div>
                    </fieldset>
                    <fieldset>
                        <legend class="{{ $label }}">2.4 Level of service <span class="text-rose-500">*</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Fully serviced', 'Not serviced', 'Partially serviced'] as $o)
                                <label class="{{ $choice }}"><input type="radio" name="level_of_service" value="{{ $o }}" x-model="service" required class="{{ $radio }}"> {{ $o }}</label>
                            @endforeach
                        </div>
                        <div x-show="service === 'Partially serviced'" x-cloak class="mt-2"><label for="in-svc" class="{{ $label }}">Partially serviced: give details <span class="text-rose-500">*</span></label><input id="in-svc" name="level_of_service_details" :required="service === 'Partially serviced'" :disabled="service !== 'Partially serviced'" class="{{ $field }}"></div>
                    </fieldset>
                </div>
            </x-ui.panel>

            {{-- 5. Site assessment (measurements) + recommendation --}}
            <x-ui.panel title="Site assessment" subtitle="Measurements on site and the officer's recommendation" data-step="5" x-show="step === 5" x-cloak>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (['front_setback' => 'Front setback (m)', 'rear_setback' => 'Rear setback (m)', 'left_setback' => 'Left side setback (m)', 'right_setback' => 'Right side setback (m)', 'plot_frontage' => 'Plot frontage (m)', 'plot_depth' => 'Plot depth (m)', 'building_footprint' => 'Building footprint (m²)', 'building_height' => 'Building height (m)'] as $n => $l)
                        <div><label for="in-{{ $n }}" class="{{ $label }}">{{ $l }}</label><input id="in-{{ $n }}" type="number" step="0.01" min="0" name="{{ $n }}" class="{{ $field }}"></div>
                    @endforeach
                    <div><label for="in-floors" class="{{ $label }}">Number of floors</label><input id="in-floors" type="number" min="0" max="60" name="floors" class="{{ $field }}"></div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="{{ $label }}">Recommendation <span class="text-rose-500">*</span></span>
                        <div class="flex flex-col gap-2">
                            @foreach ($recommendations as $o)
                                <label class="{{ $choice }}"><input type="radio" name="recommendation" value="{{ $o }}" x-model="recommendation" required class="{{ $radio }}"> {{ $o }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div><label for="in-rem" class="{{ $label }}">Remarks <span x-show="recommendation && recommendation !== 'Recommended for approval (conveyance)'" class="text-rose-500">*</span></label><textarea id="in-rem" name="remarks" rows="5" :required="recommendation && recommendation !== 'Recommended for approval (conveyance)'" class="{{ $field }}"></textarea></div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div><span class="{{ $label }}">Inspecting officer</span><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/60">{{ $user->name ?? $user->username ?? '—' }}</div></div>
                    <div><label for="in-rank" class="{{ $label }}">Rank</label><input id="in-rank" name="officer_rank" value="{{ $user->rank ?? '' }}" class="{{ $field }}"></div>
                    <div><span class="{{ $label }}">Date</span><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/60">{{ now()->format('d M Y') }}</div></div>
                </div>
            </x-ui.panel>

            {{-- 6. Review --}}
            <x-ui.panel title="Review" subtitle="Check before filing the report" data-step="6" x-show="step === 6" x-cloak>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div><dt class="{{ $label }}">BP file</dt><dd x-text="file ? file.bp + ' · ' + file.developer : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Photos</dt><dd x-text="photos.length + ' geocoded photo(s), ' + photos.filter(p => p.lat).length + ' with a position'"></dd></div>
                    <div><dt class="{{ $label }}">Plot No., District</dt><dd x-text="[loc.plot_no, loc.district].filter(Boolean).join(', ') || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Land use</dt><dd x-text="loc.land_use || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Vacancy</dt><dd x-text="vacancy || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Recommendation</dt><dd x-text="recommendation || '—'"></dd></div>
                </dl>
            </x-ui.panel>

            <x-wizard-nav :cancel="route('bpa.zonal-approval')">File inspection report</x-wizard-nav>
        </form>

        <x-ui.panel title="Filed reports" subtitle="The latest Site Inspection Reports" :padded="false">
            @if ($filed->isEmpty())
                <x-ui.empty icon="clipboard-list" title="No report filed yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400"><tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">BP File No.</th><th class="px-4 py-3">Plot No.</th><th class="px-4 py-3">District</th><th class="px-4 py-3">Officer</th><th class="px-4 py-3">Recommendation</th><th class="px-4 py-3">Filed</th><th class="px-4 py-3">Head of Zonal Office</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($filed as $r)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $r->application?->bp_file_no }}</td>
                                    <td class="px-4 py-3">{{ $r->plot_no }}</td><td class="px-4 py-3">{{ $r->district }}</td>
                                    <td class="px-4 py-3">{{ $r->officer_name }}</td><td class="px-4 py-3">{{ $r->recommendation }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $r->filed_at?->format('d M Y') }}</td>
                                    <td class="px-4 py-3">{{ ['approved' => 'Approved', 'returned' => 'Returned to officer', 'referred' => 'Referred back to DD (Arch.)'][$r->review_decision] ?? 'Awaiting review' }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900"><x-row-actions><x-row-action :href="route('bpa.inspection.show', $r)"><x-slot:icon><i data-lucide="clipboard-list" class="h-4 w-4"></i></x-slot:icon> Open report</x-row-action></x-row-actions></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>

    @push('scripts')
        <script>
            function inspectionForm(files, preselect) {
                return {
                    files, fileId: preselect || '', photos: [], geoError: '', nature: '', vacancy: '', conformity: '', service: '', recommendation: '',
                    loc: { plot_no: '', district: '', zone: '', land_use: '', plot_size: '', street: '' },
                    get file() { return this.files.find((f) => f.id === Number(this.fileId)) || null; },
                    init() {
                        this.$watch('fileId', () => this.fill());
                        this.fill();
                    },
                    fill() {
                        const f = this.file;
                        this.loc = f ? { plot_no: f.plot_no || '', district: f.district || '', zone: f.zone || '', land_use: f.land_use || '', plot_size: f.plot_size || '', street: f.street || '' }
                                     : { plot_no: '', district: '', zone: '', land_use: '', plot_size: '', street: '' };
                    },
                    position() {
                        return new Promise((resolve) => {
                            if (! navigator.geolocation) { this.geoError = 'This device cannot give a location.'; return resolve(null); }
                            navigator.geolocation.getCurrentPosition((p) => resolve(p.coords), (e) => { this.geoError = 'Location not available: ' + e.message + '. Turn on location and take the photo again.'; resolve(null); },
                                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
                        });
                    },
                    async addPhotos(e) {
                        const picked = [...e.target.files];
                        e.target.value = '';
                        if (! picked.length) return;
                        const c = await this.position();
                        const now = new Date();
                        picked.forEach((file) => this.photos.push({ key: now.getTime() + '-' + Math.random(), file, url: URL.createObjectURL(file),
                            lat: c ? c.latitude : null, lng: c ? c.longitude : null, acc: c ? c.accuracy : null, iso: now.toISOString(), at: now.toLocaleString() }));
                        this.sync();
                    },
                    removePhoto(i) { URL.revokeObjectURL(this.photos[i].url); this.photos.splice(i, 1); this.sync(); },
                    sync() {
                        const dt = new DataTransfer();
                        this.photos.forEach((p) => dt.items.add(p.file));
                        this.$refs.photos.files = dt.files;
                    },
                    guard(n) {
                        if (n === 1 && ! this.file) { alert('Pick the BP file being inspected.'); return false; }
                        if (n === 2 && this.photos.length === 0) { alert('Add at least one geocoded site photograph.'); return false; }
                        return true;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
