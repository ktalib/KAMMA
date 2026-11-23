{{-- KSI (KAMMA Site Inspection) report, copied from KLAES physical_planning/sltr/partials/jsi_form (the JSI wizard: Step 1
     Application Details, Step 2 Boundary & Inspection, Step 3 Measurements & Utilities, Step 4 Land Use & Review), with
     KAMMA's review step and the two penalty questions (building on site, BPA approved). Property details come from the
     shadow file (Plot No., District, LGA, Location) and lock; dropdowns are KLAES's JSI lookups with one "Other". --}}
<x-app-layout :title="'KSI Report · '.($kind === 'master' ? 'Master KSI' : $k['short'])">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $ro = 'rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200';
        $card = 'space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $req = '<span class="text-rose-500">*</span>';
        $action = $kind === 'master' ? route('kamma.master-ksi.store') : route('kamma.pr.store', $kind);
        $back = $kind === 'master' ? route('kamma.master-ksi.index') : route('kamma.pr.index', $kind);
        $steps = ['Application Details', 'Boundary & Inspection', 'Measurements & Utilities', 'Land Use & Compliance', 'Review'];
        $start = $file ? ['id' => $file->id, 'file_number' => $file->file_number, 'file_title' => $file->file_title, 'plot_no' => $file->plot_number, 'district' => $file->district, 'lga' => $file->lga, 'location' => $file->location] : null;
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="clipboard-plus" :title="'KAMMA Site Inspection (KSI) Report'" :subtitle="$kind === 'master' ? 'Master KSI · post-CofO application' : $k['label'].' · Planning Recommendation'" :section="'KAMMA › '.($kind === 'master' ? 'Parcel/Title Management' : $k['label'])">
            <a href="{{ $back }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Back to the list</a>
        </x-ui.page-head>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <p class="font-semibold">Please check the report.</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" novalidate class="space-y-6"
              x-data="formWizard({
                  file: @js($start), applicant: @js(old('applicant_name', $file?->file_title)),
                  purpose: @js(old('purpose')), road: @js(old('road_category')), structure: @js(old('existing_structure')), reservation: @js(old('reservation_required')),
                  recRes: @js(old('recommended_reservation')), extension: @js(old('has_extension')), building: @js(old('building_exists')), bpa: @js(old('bpa_approved')),
                  compliance: @js(old('compliance_status')), recArea: @js(old('recommended_site_area')), exArea: @js(old('existing_site_area')),
                  get penalty() { return this.building === '1' && this.bpa === '0' },
                  pick(f) { this.file = f; if (f && ! this.applicant) this.applicant = f.file_title || ''; },
                  guard(n) { if (n === 1 && ! this.file) { alert('Select the shadow file inspected.'); return false } },
              }, { steps: 5 })"
              @submit="submitAll($event)" @file-picked="pick($event.detail)">
            @csrf
            <x-wizard-steps :steps="$steps" />

            {{-- Step 1: Application Details --}}
            <section data-step="1" x-show="step === 1" class="{{ $card }}">
                <p class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><strong>Step 1 of 5 — Application Details.</strong> Pick the shadow file; its property details fill in from KLAES.</p>
                @if ($file)
                    <input type="hidden" name="file_indexing_id" value="{{ $file->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10">
                        <div><p class="font-mono text-sm font-bold text-emerald-900 dark:text-emerald-200">{{ $file->file_number }}</p><p class="text-xs text-emerald-700 dark:text-emerald-300">{{ $file->file_title ?: 'No title recorded' }}</p></div>
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-700 dark:text-emerald-300"><i data-lucide="lock" class="h-3.5 w-3.5"></i> From the shadow file</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div><span class="{{ $label }}">Plot No.</span><div class="{{ $ro }}">{{ $file->plot_number ?: '—' }}</div></div>
                        <div><span class="{{ $label }}">District</span><div class="{{ $ro }}">{{ $file->district ?: '—' }}</div></div>
                        <div><span class="{{ $label }}">LGA</span><div class="{{ $ro }}">{{ $file->lga ?: '—' }}</div></div>
                        <div><span class="{{ $label }}">Location</span><div class="{{ $ro }}">{{ $file->location ?: '—' }}</div></div>
                    </div>
                @else
                    <x-ui.file-picker name="file_indexing_id" source="shadow" label="Shadow file inspected" hint="KAMMA shadow files (Shadow File Commissioning). The file number is never typed." />
                @endif

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($kind === 'master')
                        <div>
                            <label for="k-post" class="{{ $label }}">Post-CofO application {!! $req !!}</label>
                            <select id="k-post" name="post_cofo_type" required class="{{ $field }}"><option value="">Select…</option>@foreach ($postCofo as $p)<option @selected(old('post_cofo_type') === $p)>{{ $p }}</option>@endforeach</select>
                        </div>
                    @endif
                    <div><label for="k-date" class="{{ $label }}">Inspection Date {!! $req !!}</label><input id="k-date" type="date" name="inspection_date" value="{{ old('inspection_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $field }}"></div>
                    <div><label for="k-app" class="{{ $label }}">Applicant Name {!! $req !!}</label><input id="k-app" name="applicant_name" x-model="applicant" required maxlength="300" class="{{ $field }}"></div>
                    <div><label for="k-lpkn" class="{{ $label }}">LPKN Number</label><input id="k-lpkn" name="lpkn_no" value="{{ old('lpkn_no') }}" maxlength="100" class="{{ $field }}"></div>
                    <div><label for="k-tp" class="{{ $label }}">TP Number</label><input id="k-tp" name="tp_no" value="{{ old('tp_no') }}" maxlength="100" class="{{ $field }}"></div>
                    <div><label for="k-scheme" class="{{ $label }}">Scheme Number</label><input id="k-scheme" name="scheme_no" value="{{ old('scheme_no') }}" maxlength="100" class="{{ $field }}"></div>
                </div>
                <div><label for="k-addr" class="{{ $label }}">Applicant Address {!! $req !!}</label><input id="k-addr" name="applicant_address" value="{{ old('applicant_address') }}" required maxlength="500" placeholder="House no., street, district, LGA" class="{{ $field }}"></div>
            </section>

            {{-- Step 2: Boundary & Inspection --}}
            <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
                <p class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><strong>Step 2 of 5 — Boundary &amp; Inspection.</strong> Describe the boundaries and what the officer found on site.</p>
                <fieldset>
                    <legend class="{{ $label }}">Boundary Description {!! $req !!}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['north' => 'North', 'east' => 'East', 'south' => 'South', 'west' => 'West'] as $b => $t)
                            <div><label for="k-b-{{ $b }}" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t }} {!! $req !!}</label><input id="k-b-{{ $b }}" name="boundary_{{ $b }}" value="{{ old('boundary_'.$b) }}" required maxlength="300" class="{{ $field }}"></div>
                        @endforeach
                    </div>
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.yes-no name="existing_structure" label="Existing structure on the site?" model="structure" />
                    <div x-show="structure === '1'" x-cloak><label for="k-sdims" class="{{ $label }}">Existing Structure Dimensions (L x B x H) {!! $req !!}</label><input id="k-sdims" name="existing_structure_dims" value="{{ old('existing_structure_dims') }}" :required="structure === '1'" :disabled="structure !== '1'" class="{{ $field }}"></div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label for="k-svc" class="{{ $label }}">Service {!! $req !!}</label><select id="k-svc" name="service" required class="{{ $field }}"><option value="">Select…</option>@foreach ($services as $s)<option @selected(old('service') === $s)>{{ $s }}</option>@endforeach</select></div>
                    <div><label for="k-pl" class="{{ $label }}">Private Layout Number</label><input id="k-pl" name="private_layout_no" value="{{ old('private_layout_no') }}" maxlength="100" class="{{ $field }}"></div>
                    <div><label for="k-dlu" class="{{ $label }}">Detailed Land Use {!! $req !!}</label><select id="k-dlu" name="detailed_land_use" required class="{{ $field }}"><option value="">Select…</option>@foreach ($landUses as $u)<option @selected(old('detailed_land_use') === $u['name'])>{{ $u['name'] }}</option>@endforeach</select></div>
                    <div>
                        <label for="k-purpose" class="{{ $label }}">Purpose {!! $req !!}</label>
                        <select id="k-purpose" name="purpose" x-model="purpose" required class="{{ $field }}"><option value="">Select…</option>@foreach (collect($purposes)->pluck('name')->unique(fn ($v) => mb_strtolower(trim($v))) as $p)<option>{{ $p }}</option>@endforeach<option>Other</option></select>
                        <input x-show="purpose === 'Other'" x-cloak name="purpose_other" value="{{ old('purpose_other') }}" :required="purpose === 'Other'" :disabled="purpose !== 'Other'" placeholder="Other purpose (specify)" class="{{ $field }} mt-2">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="k-road" class="{{ $label }}">Road Category {!! $req !!}</label>
                        <select id="k-road" name="road_category" x-model="road" required class="{{ $field }}"><option value="">Select…</option>@foreach ($roads as $r)<option>{{ $r['name'] }}</option>@endforeach<option>Other</option></select>
                        <input x-show="road === 'Other'" x-cloak name="road_category_other" value="{{ old('road_category_other') }}" :required="road === 'Other'" :disabled="road !== 'Other'" placeholder="Observed road category (specify)" class="{{ $field }} mt-2">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.yes-no name="conformity" label="Conformity" />
                    <x-ui.yes-no name="accessibility" label="Accessibility" />
                    <x-ui.yes-no name="reservation_required" label="Reservation required?" model="reservation" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2" x-show="reservation === '1'" x-cloak>
                    <div><label for="k-exres" class="{{ $label }}">Existing Reservation</label><input id="k-exres" name="existing_reservation" value="{{ old('existing_reservation') }}" maxlength="1000" class="{{ $field }}"></div>
                    <div>
                        <label for="k-recres" class="{{ $label }}">Recommended Reservation {!! $req !!}</label>
                        <select id="k-recres" name="recommended_reservation" x-model="recRes" :required="reservation === '1'" :disabled="reservation !== '1'" class="{{ $field }}"><option value="">Select…</option>@foreach ($reservations as $r)<option>{{ $r['name'] }}</option>@endforeach<option>Other</option></select>
                        <input x-show="recRes === 'Other'" x-cloak name="recommended_reservation_other" value="{{ old('recommended_reservation_other') }}" :required="recRes === 'Other'" :disabled="recRes !== 'Other'" placeholder="Recommended reservation (specify)" class="{{ $field }} mt-2">
                    </div>
                    <div><label for="k-meters" class="{{ $label }}">How many meters</label><input id="k-meters" name="reservation_meters" value="{{ old('reservation_meters') }}" maxlength="50" class="{{ $field }}"></div>
                </div>
            </section>

            {{-- Step 3: Measurements & Utilities --}}
            <section data-step="3" x-show="step === 3" x-cloak class="{{ $card }}">
                <p class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><strong>Step 3 of 5 — Measurements &amp; Utilities.</strong> The betterment fee is charged on the <strong>recommended</strong> site measurement (KLAES rule).</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.yes-no name="adequate_size_requirement" label="Adequate size requirement?" />
                    <x-ui.yes-no name="land_traversing_utility" label="Land traversed by a utility (power line, pipe…)?" />
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Existing site measurement</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label for="k-exl" class="{{ $label }}">Length (m)</label><input id="k-exl" type="number" step="0.01" min="0" name="existing_site_length" value="{{ old('existing_site_length') }}" class="{{ $field }}"></div>
                            <div><label for="k-exw" class="{{ $label }}">Width (m)</label><input id="k-exw" type="number" step="0.01" min="0" name="existing_site_width" value="{{ old('existing_site_width') }}" class="{{ $field }}"></div>
                        </div>
                        <div><label for="k-exa" class="{{ $label }}">Total Area Covered (m²) {!! $req !!}</label><input id="k-exa" type="number" step="0.01" min="0" name="existing_site_area" x-model="exArea" required class="{{ $field }}"></div>
                    </fieldset>
                    <fieldset class="space-y-3 rounded-xl border border-emerald-200 p-4 dark:border-emerald-500/30">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Recommended site measurement</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label for="k-rl" class="{{ $label }}">Length (m)</label><input id="k-rl" type="number" step="0.01" min="0" name="recommended_site_length" value="{{ old('recommended_site_length') }}" class="{{ $field }}"></div>
                            <div><label for="k-rw" class="{{ $label }}">Width (m)</label><input id="k-rw" type="number" step="0.01" min="0" name="recommended_site_width" value="{{ old('recommended_site_width') }}" class="{{ $field }}"></div>
                        </div>
                        <div><label for="k-ra" class="{{ $label }}">Total Area Covered (m²) {!! $req !!}</label><input id="k-ra" type="number" step="0.01" min="0" name="recommended_site_area" x-model="recArea" required class="{{ $field }}"></div>
                    </fieldset>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.yes-no name="has_extension" label="Extension?" model="extension" />
                    <div x-show="extension === '1'" x-cloak><label for="k-extd" class="{{ $label }}">Extension Dimensions {!! $req !!}</label><input id="k-extd" name="extension_dims" value="{{ old('extension_dims') }}" :required="extension === '1'" :disabled="extension !== '1'" class="{{ $field }}"></div>
                    <div x-show="extension === '1'" x-cloak><label for="k-exta" class="{{ $label }}">Extension Area (m²) {!! $req !!}</label><input id="k-exta" type="number" step="0.01" min="0" name="extension_site_area" value="{{ old('extension_site_area') }}" :required="extension === '1'" :disabled="extension !== '1'" class="{{ $field }}"></div>
                </div>
                @if ($kind === 'st')
                    <div class="max-w-xs"><label for="k-units" class="{{ $label }}">Number of units {!! $req !!}</label><input id="k-units" type="number" min="1" name="units_count" value="{{ old('units_count') }}" required class="{{ $field }}"></div>
                @endif
                <fieldset class="space-y-3 rounded-xl border border-rose-200 bg-rose-50/40 p-4 dark:border-rose-500/30 dark:bg-rose-500/5">
                    <legend class="px-1 text-xs font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">KAMMA betterment penalty (Site Assessment)</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.yes-no name="building_exists" label="Is there a building on the land?" model="building" />
                        <div x-show="building === '1'" x-cloak>
                            <fieldset>
                                <legend class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Did the building get Building Plan Approval (KADCA)? {!! $req !!}</legend>
                                <div class="flex gap-4">
                                    @foreach (['1' => 'Yes, BPA approved', '0' => 'No BPA'] as $v => $t)
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="bpa_approved" value="{{ $v }}" x-model="bpa" :required="building === '1'" :disabled="building !== '1'" class="border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800"> {{ $t }}</label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    <p x-show="penalty" x-cloak class="text-sm font-semibold text-rose-700 dark:text-rose-300">A building stands without Building Plan Approval: the betterment fee will be the standard fee × 4.</p>
                </fieldset>
            </section>

            {{-- Step 4: Land Use & Compliance --}}
            <section data-step="4" x-show="step === 4" x-cloak class="{{ $card }}">
                <p class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><strong>Step 4 of 5 — Land Use &amp; Compliance.</strong> Finalise land use, compliance and the inspecting officer.</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label for="k-prev" class="{{ $label }}">Prevailing Land Use {!! $req !!}</label><select id="k-prev" name="prevailing_land_use" required class="{{ $field }}"><option value="">Select…</option>@foreach ($landUseTypes as $u)<option @selected(old('prevailing_land_use') === $u['name'])>{{ $u['name'] }}</option>@endforeach</select></div>
                    <div><label for="k-appl" class="{{ $label }}">Applied Land Use {!! $req !!}</label><select id="k-appl" name="applied_land_use" required class="{{ $field }}"><option value="">Select…</option>@foreach ($landUseTypes as $u)<option @selected(old('applied_land_use') === $u['name'])>{{ $u['name'] }}</option>@endforeach</select></div>
                    <div>
                        <label for="k-comp" class="{{ $label }}">Compliance Status {!! $req !!}</label>
                        <select id="k-comp" name="compliance_status" x-model="compliance" required class="{{ $field }}"><option value="">Select…</option>@foreach ($compliance as $c)<option>{{ $c }}</option>@endforeach<option>Other</option></select>
                        <input x-show="compliance === 'Other'" x-cloak name="compliance_status_other" value="{{ old('compliance_status_other') }}" :required="compliance === 'Other'" :disabled="compliance !== 'Other'" placeholder="Compliance status (specify)" class="{{ $field }} mt-2">
                    </div>
                    <x-ui.yes-no name="need_for_eia" label="Need for EIA?" />
                    <x-ui.yes-no name="available_on_ground" label="Available on ground?" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="k-off" class="{{ $label }}">Inspection Officer {!! $req !!}</label><input id="k-off" name="inspection_officer" value="{{ old('inspection_officer', auth()->user()->name) }}" required maxlength="300" class="{{ $field }}"></div>
                    <div><label for="k-rank" class="{{ $label }}">Rank</label><input id="k-rank" name="officer_rank" value="{{ old('officer_rank', auth()->user()->rank ?? '') }}" maxlength="150" class="{{ $field }}"></div>
                </div>
                <div><label for="k-obs" class="{{ $label }}">Additional Observation Details {!! $req !!}</label><textarea id="k-obs" name="additional_observations" rows="4" required maxlength="5000" class="{{ $field }}">{{ old('additional_observations') }}</textarea></div>
            </section>

            {{-- Step 5: Review --}}
            <section data-step="5" x-show="step === 5" x-cloak class="{{ $card }}">
                <p class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><strong>Step 5 of 5 — Review.</strong> Check the report, then save it for the MD KAMMA's approval.</p>
                <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="{{ $label }}">File No.</dt><dd class="font-mono font-semibold text-slate-900 dark:text-white" x-text="file ? file.file_number : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Applicant</dt><dd class="text-slate-800 dark:text-slate-200" x-text="applicant || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Plot No.</dt><dd class="text-slate-800 dark:text-slate-200" x-text="file && file.plot_no ? file.plot_no : '—'"></dd></div>
                    <div><dt class="{{ $label }}">District</dt><dd class="text-slate-800 dark:text-slate-200" x-text="file && file.district ? file.district : '—'"></dd></div>
                    <div><dt class="{{ $label }}">LGA</dt><dd class="text-slate-800 dark:text-slate-200" x-text="file && file.lga ? file.lga : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Location</dt><dd class="text-slate-800 dark:text-slate-200" x-text="file && file.location ? file.location : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Existing area</dt><dd class="text-slate-800 dark:text-slate-200" x-text="exArea ? exArea + ' m²' : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Recommended area</dt><dd class="font-semibold text-emerald-700 dark:text-emerald-400" x-text="recArea ? recArea + ' m²' : '—'"></dd></div>
                    <div><dt class="{{ $label }}">Compliance</dt><dd class="text-slate-800 dark:text-slate-200" x-text="compliance || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Building on the land</dt><dd class="text-slate-800 dark:text-slate-200" x-text="building === '1' ? (bpa === '1' ? 'Yes, with BPA' : 'Yes, no BPA') : 'No'"></dd></div>
                    <div class="sm:col-span-2"><dt class="{{ $label }}">Betterment</dt><dd :class="penalty ? 'font-semibold text-rose-700 dark:text-rose-300' : 'text-slate-800 dark:text-slate-200'" x-text="penalty ? 'Standard fee × 4 (no-BPA penalty)' : 'Standard fee'"></dd></div>
                </dl>
            </section>

            <x-wizard-nav :cancel="$back">Save KSI report</x-wizard-nav>
        </form>
    </div>
</x-app-layout>
