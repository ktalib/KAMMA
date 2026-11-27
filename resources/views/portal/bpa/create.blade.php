@extends('portal.layouts.portal')

@section('title', 'New application')

@section('content')
@php
    $field = 'block w-full rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $locked = 'block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200';
    $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900';
    $h2 = 'text-base font-semibold text-slate-900 dark:text-slate-100';
    $hint = 'mt-1 text-sm text-slate-500 dark:text-slate-400';
    $check = 'h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800';
    $radio = 'h-5 w-5 border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800';
    $dt = 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $dd = 'mt-0.5 break-words text-sm text-slate-900 dark:text-slate-100';
    $oldDocs = (array) old('documents', []);
    $oldDrawings = (array) old('drawings', []);

    // The staff form's steps (bpa/applications/create), in applicant wording.
    $steps = [1 => 'Land file', 2 => 'Proposed building', 3 => 'Developer', 4 => 'Submission & documents', 5 => 'Building plan', 6 => 'Fee & review'];

    // After a failed check, open the first step that has an error.
    $stepFields = [
        1 => ['klaes_file_indexing_id'],
        2 => ['application_type', 'proposed_use', 'proposed_use_other'],
        3 => ['developer_name', 'cadastral_zone', 'cadastral_zone_plot_no', 'street_name', 'dwelling_plot_floor', 'dwelling_unit',
            'contact_address', 'developer_phone', 'developer_email', 'representative_phone', 'representative_email'],
        4 => ['submission_type', 'submission_type_other', 'documents', 'documents_other', 'drawings'],
        5 => ['plan_received_as', 'plan_files'],
        6 => ['number_of_plots', 'submitted_by_name', 'declaration'],
    ];
    $initialStep = 1;
    foreach ($stepFields as $n => $names) {
        if (collect($names)->contains(fn ($f) => $errors->has($f) || $errors->has($f.'.*'))) {
            $initialStep = $n;
            break;
        }
    }
@endphp

<script>
    // Pick your KLAES land file (never typed), fill what KLAES holds, compare the proposed use with the file's land use,
    // and show the bill as the number of plots changes. Same behaviour as the staff form, on the portal's file search.
    window.portalApplicationForm = function (cfg) {
        return {
            q: '', results: [], searching: false, searched: false, loading: false, error: '',
            file: null,
            appType: cfg.old.application_type || '',
            proposed: cfg.old.proposed_use || '',
            plots: cfg.old.number_of_plots || 1,
            developer_name: cfg.old.developer_name || '',
            street_name: cfg.old.street_name || '',
            developer_phone: cfg.old.developer_phone || cfg.applicantPhone || '',
            submissionType: cfg.old.submission_type || '',
            docOther: cfg.old.doc_other,
            planNames: [],

            async init() {
                if (cfg.old.file_id) await this.load(cfg.old.file_id, false);
            },

            guard(n) {
                if (n === 1 && ! this.file) {
                    this.error = 'Find and pick your land file first.';
                    return false;
                }
                return true;
            },

            async search() {
                const term = this.q.trim();
                this.error = '';
                if (term.length < 3) { this.results = []; this.searched = false; return; }
                this.searching = true;
                try {
                    const r = await fetch(cfg.searchUrl + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' } });
                    if (! r.ok) throw new Error();
                    this.results = (await r.json()).files;
                    this.searched = true;
                } catch (e) {
                    this.error = 'The land file search did not respond. Try again.';
                } finally {
                    this.searching = false;
                }
            },

            async load(id, backfill = true) {
                this.loading = true;
                this.error = '';
                try {
                    const r = await fetch(cfg.showUrl.replace('__ID__', id), { headers: { Accept: 'application/json' } });
                    if (! r.ok) throw new Error();
                    this.file = (await r.json()).file;
                    if (backfill) {
                        // A newly picked file replaces what the previous one filled in.
                        this.developer_name = this.file.file_title || '';
                        this.street_name = this.file.street_name || '';
                    }
                } catch (e) {
                    this.file = null;
                    this.error = 'That land file could not be loaded. Pick it again.';
                } finally {
                    this.loading = false;
                }
            },

            pick(f) {
                this.results = [];
                this.searched = false;
                this.q = '';
                this.load(f.id);
            },

            changeFile() {
                this.file = null;
                this.developer_name = this.street_name = '';
                this.$nextTick(() => document.getElementById('file-search')?.focus());
            },

            get check() {
                if (! this.proposed) return null;
                const needed = cfg.codes[this.proposed];
                if (! needed) return { kind: 'Not checked', text: 'The use is "Other", so it is not compared with your land file.' };
                if (! this.file) return null;
                const code = this.file.land_use_code;
                if (! code) return { kind: 'Not checked', text: 'Your land file has no recorded land use to compare with.' };
                return needed === code
                    ? { kind: 'Match', text: this.proposed + ' building on a ' + cfg.labels[code] + ' land file.' }
                    : { kind: 'Mismatch', text: this.proposed + ' building on a ' + cfg.labels[code] + ' land file. You can still apply; KAMMA will review it.' };
            },

            get total() {
                return (parseInt(this.plots, 10) || 0) * cfg.fee * (this.appType === 'Registration' ? cfg.multiplier : 1);
            },

            naira(n) {
                return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    };
</script>

<form method="POST" action="{{ route('portal.bpa.applications.store') }}" enctype="multipart/form-data" novalidate
      x-data="formWizard(portalApplicationForm(@js([
          'searchUrl' => route('portal.files.search'),
          'showUrl' => route('portal.files.show', ['id' => '__ID__']),
          'codes' => $proposedCodes,
          'labels' => $landUseLabels,
          'fee' => $feePerPlot,
          'multiplier' => $multiplier,
          'applicantPhone' => $applicant['phone'] ?? null,
          'old' => [
              'file_id' => old('klaes_file_indexing_id'),
              'proposed_use' => old('proposed_use'),
              'application_type' => old('application_type'),
              'number_of_plots' => old('number_of_plots', 1),
              'developer_name' => old('developer_name'),
              'street_name' => old('street_name'),
              'developer_phone' => old('developer_phone'),
              'submission_type' => old('submission_type'),
              'doc_other' => in_array('Other', $oldDocs, true),
          ],
      ])), { steps: {{ count($steps) }}, initial: {{ $initialStep }} })"
      @submit="submitAll($event)"
      class="mx-auto max-w-4xl scroll-mt-20 space-y-6">
    @csrf

    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">New building plan application</h1>
        <p class="{{ $hint }}">Building Plan Processing and Approval. When you submit, your application gets its number and its bill.</p>
    </div>

    <x-wizard-steps :steps="$steps" />

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            Please correct the {{ $errors->count() === 1 ? 'item' : $errors->count().' items' }} marked in red. The form has opened at the first one.
            Choose your building plan file(s) again in step 5: files are not kept when the form comes back.
        </div>
    @endif

    {{-- Step 1: Land file --}}
    <section data-step="1" x-show="step === 1" class="{{ $card }}">
        <h2 class="{{ $h2 }}">Your land file</h2>
        <p class="{{ $hint }}">The plot must have an approved land file in KLAES. Type its file number, then pick it from the list; its details fill in and lock.</p>
        <input type="hidden" name="klaes_file_indexing_id" :value="file ? file.id : ''">

        <div x-show="! file" class="relative mt-4">
            <label for="file-search" class="{{ $label }}">File number</label>
            <input id="file-search" type="search" x-model="q" @input.debounce.400ms="search()" @keydown.escape="results = []" @keydown.enter.prevent
                   placeholder="e.g. RES-2016-452" autocomplete="off" autocapitalize="characters" class="{{ $field }}">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">At least 3 characters. Search is by file number only.</p>
            <p x-show="searching" x-cloak class="mt-2 text-sm text-slate-500 dark:text-slate-400">Searching…</p>
            <p x-show="searched && ! searching && results.length === 0" x-cloak class="mt-2 text-sm text-slate-500 dark:text-slate-400">No land file matches that number. Check it against your title documents.</p>

            <ul x-show="results.length" x-cloak class="absolute z-20 mt-1 max-h-80 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <template x-for="f in results" :key="f.id">
                    <li>
                        <button type="button" @click="pick(f)" class="block w-full px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800">
                            <span class="block text-sm font-medium text-slate-900 dark:text-slate-100" x-text="f.file_number"></span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400"
                                  x-text="[f.plot_no ? 'Plot ' + f.plot_no : null, f.district, f.lga, f.land_use].filter(Boolean).join(' · ')"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <p x-show="loading" x-cloak class="mt-3 text-sm text-slate-500 dark:text-slate-400">Loading your land file…</p>
        <p x-show="error" x-cloak x-text="error" class="mt-3 text-sm text-rose-600 dark:text-rose-400"></p>
        <x-input-error :messages="$errors->get('klaes_file_indexing_id')" class="mt-2" />

        <template x-if="file">
            <div class="mt-4 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10">
                    <div>
                        <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200" x-text="file.file_number"></p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300" x-text="file.file_title || 'No title recorded'"></p>
                    </div>
                    <button type="button" @click="changeFile()" class="rounded-lg border border-emerald-200 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-slate-900 dark:text-emerald-300 dark:hover:bg-slate-800">Change file</button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="{{ $label }}">Plot No.</span><div class="{{ $locked }}" x-text="file.plot_no || '—'"></div></div>
                    <div><span class="{{ $label }}">District</span><div class="{{ $locked }}" x-text="file.district || '—'"></div></div>
                    <div><span class="{{ $label }}">LGA</span><div class="{{ $locked }}" x-text="file.lga || '—'"></div></div>
                    <div><span class="{{ $label }}">Location</span><div class="{{ $locked }}" x-text="file.location || '—'"></div></div>
                    <div><span class="{{ $label }}">Plot size</span><div class="{{ $locked }}" x-text="file.plot_size || '—'"></div></div>
                    <div><span class="{{ $label }}">Land use (land file)</span><div class="{{ $locked }}" x-text="file.land_use || 'Not recorded'"></div></div>
                </div>
                <p x-show="file.title_status && file.title_status.startsWith('Recorded')" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300">
                    A status is recorded on this title. You can still apply; KAMMA will check it.
                </p>
            </div>
        </template>
    </section>

    {{-- Step 2: Proposed building and land-use check --}}
    <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Proposed building</h2>
        <p class="{{ $hint }}">What the building will be used for. It should agree with your land file's land use; if it does not, KAMMA reviews it.</p>
        <fieldset class="mt-4">
            <legend class="{{ $label }}">Application type <span class="text-rose-500">*</span></legend>
            <div class="mt-1 grid gap-3 sm:grid-cols-2">
                @foreach ($applicationTypes as $t)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3"
                           :class="appType === @js($t) ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500/60 dark:bg-emerald-500/10' : 'border-slate-200 dark:border-slate-700'">
                        <input type="radio" name="application_type" value="{{ $t }}" x-model="appType" required class="mt-0.5 {{ $radio }}">
                        <span>
                            <span class="block text-sm font-medium text-slate-900 dark:text-slate-100">{{ $t }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $t === 'Registration' ? 'My building is already up without approval. The fee is '.$multiplier.' × the normal fee.' : 'I am applying for approval before I build.' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('application_type')" class="mt-2" />
        </fieldset>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="proposed_use" class="{{ $label }}">Proposed use <span class="text-rose-500">*</span></label>
                <select id="proposed_use" name="proposed_use" x-model="proposed" required class="{{ $field }}">
                    <option value="">Select…</option>
                    @foreach ($proposedUses as $use)
                        <option value="{{ $use }}">{{ $use }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('proposed_use')" class="mt-2" />
            </div>
            <div x-show="proposed === 'Other'" x-cloak>
                <label for="proposed_use_other" class="{{ $label }}">Specify the use <span class="text-rose-500">*</span></label>
                <input id="proposed_use_other" name="proposed_use_other" value="{{ old('proposed_use_other') }}" :disabled="proposed !== 'Other'" :required="proposed === 'Other'" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('proposed_use_other')" class="mt-2" />
            </div>
        </div>
        <template x-if="check">
            <div class="mt-4 flex items-start gap-2 rounded-lg px-4 py-3 text-sm"
                 :class="{
                    'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300': check.kind === 'Match',
                    'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300': check.kind === 'Mismatch',
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': check.kind === 'Not checked',
                 }">
                <span class="font-semibold" x-text="check.kind === 'Mismatch' ? 'Will be reviewed:' : check.kind + ':'"></span>
                <span x-text="check.text"></span>
            </div>
        </template>
    </section>

    {{-- Step 3: Developer (paper form, page 1) --}}
    <section data-step="3" x-show="step === 3" x-cloak class="space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Developer and property</h2>
            <p class="{{ $hint }}">Filled from your land file where KLAES has it; correct anything that has changed. Saved in capital letters, as the form asks.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="developer_name" class="{{ $label }}">Name of developer <span class="text-slate-400">(name on title documents)</span> <span class="text-rose-500">*</span></label>
                    <input id="developer_name" name="developer_name" x-model="developer_name" required maxlength="300" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('developer_name')" class="mt-2" />
                </div>
                <div>
                    <label for="cadastral_zone" class="{{ $label }}">Cadastral zone</label>
                    <input id="cadastral_zone" name="cadastral_zone" value="{{ old('cadastral_zone') }}" maxlength="100" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('cadastral_zone')" class="mt-2" />
                </div>
                <div>
                    <label for="cadastral_zone_plot_no" class="{{ $label }}">Cadastral zone / Plot No.</label>
                    <input id="cadastral_zone_plot_no" name="cadastral_zone_plot_no" value="{{ old('cadastral_zone_plot_no') }}" maxlength="150" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('cadastral_zone_plot_no')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <label for="street_name" class="{{ $label }}">Street name</label>
                    <input id="street_name" name="street_name" x-model="street_name" maxlength="200" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('street_name')" class="mt-2" />
                </div>
                <div>
                    <label for="dwelling_plot_floor" class="{{ $label }}">Dwelling plot No. / Floor</label>
                    <input id="dwelling_plot_floor" name="dwelling_plot_floor" value="{{ old('dwelling_plot_floor') }}" maxlength="100" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('dwelling_plot_floor')" class="mt-2" />
                </div>
                <div>
                    <label for="dwelling_unit" class="{{ $label }}">Dwelling unit</label>
                    <input id="dwelling_unit" name="dwelling_unit" value="{{ old('dwelling_unit') }}" maxlength="100" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('dwelling_unit')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Contact</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="contact_address" class="{{ $label }}">Current contact address <span class="text-rose-500">*</span></label>
                    <input id="contact_address" name="contact_address" value="{{ old('contact_address') }}" required maxlength="500" autocomplete="street-address" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('contact_address')" class="mt-2" />
                </div>
                <div>
                    <label for="developer_phone" class="{{ $label }}">Developer's mobile No. <span class="text-rose-500">*</span></label>
                    <input id="developer_phone" name="developer_phone" x-model="developer_phone" required inputmode="tel" autocomplete="tel" pattern="\+?[0-9 ()\-]{7,20}" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('developer_phone')" class="mt-2" />
                </div>
                <div>
                    <label for="developer_email" class="{{ $label }}">Developer's email</label>
                    <input id="developer_email" type="email" name="developer_email" value="{{ old('developer_email', $applicant['email'] ?? '') }}" autocapitalize="none" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('developer_email')" class="mt-2" />
                </div>
                <div>
                    <label for="representative_phone" class="{{ $label }}">Representative's mobile No.</label>
                    <input id="representative_phone" name="representative_phone" value="{{ old('representative_phone') }}" inputmode="tel" pattern="\+?[0-9 ()\-]{7,20}" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('representative_phone')" class="mt-2" />
                </div>
                <div>
                    <label for="representative_email" class="{{ $label }}">Representative's email</label>
                    <input id="representative_email" type="email" name="representative_email" value="{{ old('representative_email') }}" autocapitalize="none" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('representative_email')" class="mt-2" />
                </div>
            </div>
        </div>
    </section>

    {{-- Step 4: Submission and documents --}}
    <section data-step="4" x-show="step === 4" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Submission and documents</h2>

        <fieldset class="mt-4">
            <legend class="{{ $label }}">Type of submission <span class="text-rose-500">*</span></legend>
            <div class="mt-2 grid gap-3 sm:flex sm:flex-wrap sm:gap-x-6">
                @foreach ($submissionTypes as $type)
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300">
                        <input type="radio" name="submission_type" value="{{ $type }}" x-model="submissionType" required class="{{ $radio }}">
                        {{ $type === 'Other' ? 'Other (specify)' : $type }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('submission_type')" class="mt-2" />
            <div x-show="submissionType === 'Other'" x-cloak class="mt-3 max-w-xl">
                <label for="submission_type_other" class="{{ $label }}">Specify the type <span class="text-rose-500">*</span></label>
                <input id="submission_type_other" name="submission_type_other" value="{{ old('submission_type_other') }}" :disabled="submissionType !== 'Other'" :required="submissionType === 'Other'" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('submission_type_other')" class="mt-2" />
            </div>
        </fieldset>

        <fieldset class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
            <legend class="sr-only">Documents you are submitting</legend>
            <p class="{{ $label }}">Documents you are submitting</p>
            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                @foreach ($documents as $doc)
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300">
                        @if ($doc === 'Other')
                            <input type="checkbox" name="documents[]" value="Other" x-model="docOther" class="{{ $check }}">
                            Other (specify)
                        @else
                            <input type="checkbox" name="documents[]" value="{{ $doc }}" @checked(in_array($doc, $oldDocs, true)) class="{{ $check }}">
                            {{ $doc }}
                        @endif
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('documents.*')" class="mt-2" />
            <div x-show="docOther" x-cloak class="mt-3 max-w-xl">
                <label for="documents_other" class="{{ $label }}">Specify the other document <span class="text-rose-500">*</span></label>
                <input id="documents_other" name="documents_other" value="{{ old('documents_other') }}" :disabled="! docOther" :required="docOther" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('documents_other')" class="mt-2" />
            </div>
        </fieldset>

        <fieldset class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
            <legend class="sr-only">Drawings / reports you are submitting</legend>
            <p class="{{ $label }}">Drawings / reports you are submitting</p>
            <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($drawings as $drawing)
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="drawings[]" value="{{ $drawing }}" @checked(in_array($drawing, $oldDrawings, true)) class="{{ $check }}">
                        {{ $drawing }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('drawings.*')" class="mt-2" />
        </fieldset>
    </section>

    {{-- Step 5: Building plan upload --}}
    <section data-step="5" x-show="step === 5" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Building plan</h2>
        <p class="{{ $hint }}">Your scanned booklet, a PDF soft copy, or the AutoCAD drawing (DXF or DWG). Up to 10 files, {{ (int) (config('kamma.portal.bpa.upload_max_kb') / 1024) }} MB each.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="plan_received_as" class="{{ $label }}">What you are uploading <span class="text-rose-500">*</span></label>
                <select id="plan_received_as" name="plan_received_as" required class="{{ $field }}">
                    <option value="">Select…</option>
                    @foreach ($receivedAs as $option)
                        <option value="{{ $option }}" @selected(old('plan_received_as') === $option)>{{ \Illuminate\Support\Str::replace(' from the applicant', '', $option) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('plan_received_as')" class="mt-2" />
            </div>
            <div>
                <label for="plan_files" class="{{ $label }}">Files <span class="text-rose-500">*</span></label>
                <input id="plan_files" type="file" name="plan_files[]" multiple required accept="{{ $accept }}"
                       @change="planNames = [...$event.target.files].map((f) => f.name)"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-slate-300 dark:file:bg-emerald-500/10 dark:file:text-emerald-300">
                <x-input-error :messages="$errors->get('plan_files')" class="mt-2" />
                <x-input-error :messages="$errors->get('plan_files.*')" class="mt-2" />
                <ul x-show="planNames.length" x-cloak class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                    <template x-for="n in planNames" :key="n"><li class="truncate" x-text="n"></li></template>
                </ul>
            </div>
        </div>
    </section>

    {{-- Step 6: Fee, declaration and review --}}
    <section data-step="6" x-show="step === 6" x-cloak class="space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Application fee</h2>
            <p class="{{ $hint }}">A non-refundable fee of ₦{{ number_format($feePerPlot, 2) }} per plot. The bill is raised when you submit; pay it to move your application on.
                <span x-show="appType === 'Registration'" x-cloak class="font-medium text-amber-700 dark:text-amber-300">Registration: {{ $multiplier }} × the normal fee (₦{{ number_format($feePerPlot * $multiplier, 2) }} per plot).</span></p>
            <div class="mt-4 grid items-end gap-4 sm:grid-cols-3">
                <div>
                    <label for="number_of_plots" class="{{ $label }}">Number of plots <span class="text-rose-500">*</span></label>
                    <input id="number_of_plots" type="number" min="1" max="100" name="number_of_plots" x-model="plots" required inputmode="numeric" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('number_of_plots')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <span class="{{ $label }}">Your application bill</span>
                    <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100" x-text="naira(total)"></div>
                </div>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="submitted_by_name" class="{{ $label }}">Submitted by (name)</label>
                    <input id="submitted_by_name" name="submitted_by_name" value="{{ old('submitted_by_name', $applicant['name'] ?? '') }}" maxlength="300" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('submitted_by_name')" class="mt-2" />
                </div>
                <div>
                    <span class="{{ $label }}">Date</span>
                    <div class="{{ $locked }}">{{ now()->format('j M Y') }}</div>
                </div>
            </div>
            <label class="mt-6 flex items-start gap-3 rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                <input type="checkbox" name="declaration" value="1" required @checked(old('declaration')) class="{{ $check }} mt-0.5">
                <span class="text-sm text-slate-700 dark:text-slate-300">
                    <span class="font-semibold">Signature.</span> I confirm that the information and drawings I am submitting are correct, and that I am the developer named above or act for them.
                </span>
            </label>
            <x-input-error :messages="$errors->get('declaration')" class="mt-2" />
        </div>

        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Review</h2>
            <p class="{{ $hint }}">Check before you submit. Use Back, or tap a step above, to change anything.</p>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Land file</dt><dd class="{{ $dd }}" x-text="file ? file.file_number : '—'"></dd></div>
                <div><dt class="{{ $dt }}">Plot No. · District · LGA</dt><dd class="{{ $dd }}" x-text="file ? [file.plot_no, file.district, file.lga].filter(Boolean).join(' · ') : '—'"></dd></div>
                <div><dt class="{{ $dt }}">Application type</dt><dd class="{{ $dd }}" x-text="appType || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Proposed use</dt><dd class="{{ $dd }}" x-text="proposed || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Land-use check</dt>
                    <dd class="{{ $dd }}" :class="check && check.kind === 'Mismatch' ? '!text-amber-600 dark:!text-amber-400' : ''" x-text="check ? (check.kind === 'Mismatch' ? 'Will be reviewed' : check.kind) : '—'"></dd>
                </div>
                <div><dt class="{{ $dt }}">Developer</dt><dd class="{{ $dd }} uppercase" x-text="developer_name || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Developer's mobile No.</dt><dd class="{{ $dd }}" x-text="developer_phone || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Type of submission</dt><dd class="{{ $dd }}" x-text="submissionType || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Building plan files</dt><dd class="{{ $dd }}" x-text="planNames.length ? planNames.join(', ') : 'None chosen'"></dd></div>
            </dl>
        </div>
    </section>

    <x-wizard-nav :cancel="route('portal.bpa.index')">Submit application</x-wizard-nav>
</form>
@endsection
