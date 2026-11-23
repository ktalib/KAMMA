@extends('portal.layouts.portal')

@section('title', 'New request')

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

    $steps = [1 => 'Type of request', 2 => 'Land file', 3 => 'Proposal', 4 => 'Applicant', 5 => 'Documents', 6 => 'Fees & review'];

    // After a failed check, open the first step that has an error.
    $stepFields = [
        1 => ['category', 'request_type'],
        2 => ['klaes_file_indexing_id', 'merge_file_ids'],
        3 => ['proposed_plots', 'extension_area', 'proposed_use', 'proposed_use_other', 'description', 'building_exists', 'has_bpa'],
        4 => ['applicant_name', 'contact_address', 'phone', 'email'],
        5 => ['documents', 'documents_other', 'files'],
        6 => ['declaration'],
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
    // Pick your KLAES land file (never typed) and, for a Merger, the other files; show only the fields the chosen type
    // needs; warn when a building without a Building Plan Approval will make the betterment fee x4.
    window.portalRequestForm = function (cfg) {
        return {
            q: '', results: [], searching: false, searched: false, loading: false, error: '',
            mq: '', mresults: [], msearching: false, msearched: false,
            file: null, merges: [],
            category: cfg.old.category || '',
            type: cfg.old.request_type || '',
            proposed: cfg.old.proposed_use || '',
            building: cfg.old.building_exists || '',
            bpa: cfg.old.has_bpa || '',
            applicant_name: cfg.old.applicant_name || cfg.applicantName || '',
            phone: cfg.old.phone || cfg.applicantPhone || '',
            docOther: cfg.old.doc_other,
            fileNames: [],

            async init() {
                if (cfg.old.file_id) await this.load(cfg.old.file_id);
                for (const id of cfg.old.merge_ids || []) {
                    const f = await this.fetchFile(id);
                    if (f) this.merges.push(f);
                }
            },

            get typesForCategory() {
                return cfg.types[this.category] || {};
            },

            setCategory(c) {
                this.category = c;
                if (! (this.type in (cfg.types[c] || {}))) this.type = '';
            },

            guard(n) {
                this.error = '';
                if (n === 2 && ! this.file) {
                    this.error = 'Find and pick your land file first.';
                    return false;
                }
                if (n === 2 && this.type === 'Merger' && this.merges.length === 0) {
                    this.error = 'Pick the other land file(s) to merge with.';
                    return false;
                }
                return true;
            },

            async fetchFile(id) {
                try {
                    const r = await fetch(cfg.showUrl.replace('__ID__', id), { headers: { Accept: 'application/json' } });
                    if (! r.ok) throw new Error();
                    return (await r.json()).file;
                } catch (e) {
                    return null;
                }
            },

            async find(term) {
                const r = await fetch(cfg.searchUrl + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' } });
                if (! r.ok) throw new Error();
                return (await r.json()).files;
            },

            async search() {
                const term = this.q.trim();
                this.error = '';
                if (term.length < 3) { this.results = []; this.searched = false; return; }
                this.searching = true;
                try { this.results = await this.find(term); this.searched = true; }
                catch (e) { this.error = 'The land file search did not respond. Try again.'; }
                finally { this.searching = false; }
            },

            async msearch() {
                const term = this.mq.trim();
                if (term.length < 3) { this.mresults = []; this.msearched = false; return; }
                this.msearching = true;
                try {
                    const taken = [this.file?.id, ...this.merges.map((m) => m.id)];
                    this.mresults = (await this.find(term)).filter((f) => ! taken.includes(f.id));
                    this.msearched = true;
                } catch (e) { this.error = 'The land file search did not respond. Try again.'; }
                finally { this.msearching = false; }
            },

            async load(id) {
                this.loading = true;
                this.error = '';
                this.file = await this.fetchFile(id);
                if (! this.file) this.error = 'That land file could not be loaded. Pick it again.';
                this.loading = false;
            },

            pick(f) { this.results = []; this.searched = false; this.q = ''; this.load(f.id); },

            addMerge(f) { this.merges.push(f); this.mresults = []; this.msearched = false; this.mq = ''; },

            removeMerge(id) { this.merges = this.merges.filter((m) => m.id !== id); },

            changeFile() {
                // A new land file clears what the previous one brought in.
                this.file = null;
                this.merges = [];
                this.$nextTick(() => document.getElementById('file-search')?.focus());
            },

            get penalty() {
                return this.building === 'yes' && this.bpa === 'no';
            },

            get feeText() {
                return cfg.fee === null ? 'Set by KAMMA when your Shadow File is opened' : this.naira(cfg.fee);
            },

            naira(n) {
                return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    };
</script>

<form method="POST" action="{{ route('portal.applications.store') }}" enctype="multipart/form-data" novalidate
      x-data="formWizard(portalRequestForm(@js([
          'searchUrl' => route('portal.files.search'),
          'showUrl' => route('portal.files.show', ['id' => '__ID__']),
          'types' => $types,
          'fee' => $processingFee,
          'applicantName' => $applicant['name'] ?? null,
          'applicantPhone' => $applicant['phone'] ?? null,
          'old' => [
              'category' => old('category'),
              'request_type' => old('request_type'),
              'file_id' => old('klaes_file_indexing_id'),
              'merge_ids' => array_values((array) old('merge_file_ids', [])),
              'proposed_use' => old('proposed_use'),
              'building_exists' => old('building_exists'),
              'has_bpa' => old('has_bpa'),
              'applicant_name' => old('applicant_name'),
              'phone' => old('phone'),
              'doc_other' => in_array('Other', $oldDocs, true),
          ],
      ])), { steps: {{ count($steps) }}, initial: {{ $initialStep }} })"
      @submit="submitAll($event)"
      class="mx-auto max-w-4xl scroll-mt-20 space-y-6">
    @csrf

    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">New request for a Planning Recommendation</h1>
        <p class="{{ $hint }}">For land inside the 10 km metropolitan / urban buffer. When you submit, KAMMA opens a Shadow File on your land file and raises the processing fee.</p>
    </div>

    <x-wizard-steps :steps="$steps" />

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            Please correct the {{ $errors->count() === 1 ? 'item' : $errors->count().' items' }} marked in red. The form has opened at the first one.
            Choose your document file(s) again in step 5: files are not kept when the form comes back.
        </div>
    @endif

    {{-- Step 1: Type of request --}}
    <section data-step="1" x-show="step === 1" class="{{ $card }}">
        <h2 class="{{ $h2 }}">What are you asking for?</h2>
        <p class="{{ $hint }}">KAMMA gives the Planning Recommendation on land applications and on Post-CofO applications inside the buffer.</p>

        <fieldset class="mt-4">
            <legend class="{{ $label }}">Group <span class="text-rose-500">*</span></legend>
            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                @foreach ($categories as $key => $name)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700"
                           :class="category === '{{ $key }}' ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-500 dark:bg-emerald-500/10' : ''">
                        <input type="radio" name="category" value="{{ $key }}" :checked="category === '{{ $key }}'" @change="setCategory('{{ $key }}')" required class="{{ $radio }} mt-0.5">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ $name }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ implode(', ', array_keys($types[$key])) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </fieldset>

        <div class="mt-5 max-w-md" x-show="category" x-cloak>
            <label for="request_type" class="{{ $label }}">Type of request <span class="text-rose-500">*</span></label>
            <select id="request_type" name="request_type" x-model="type" required class="{{ $field }}">
                <option value="">Select…</option>
                <template x-for="(name, value) in typesForCategory" :key="value">
                    <option :value="value" x-text="name" :selected="value === type"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('request_type')" class="mt-2" />
        </div>
    </section>

    {{-- Step 2: Land file (and the files to merge) --}}
    <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Your land file</h2>
        <p class="{{ $hint }}">Type its file number, then pick it from the list; its details fill in and lock. Your land file itself is never changed: KAMMA works on a Shadow File.</p>
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
                            <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="[f.plot_no ? 'Plot ' + f.plot_no : null, f.district, f.lga, f.land_use].filter(Boolean).join(' · ')"></span>
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

        {{-- Merger: the other land files, picked the same way (never typed) --}}
        <div x-show="type === 'Merger' && file" x-cloak class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
            <p class="{{ $label }}">Land files to merge with <span class="text-rose-500">*</span></p>
            <template x-for="m in merges" :key="m.id">
                <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-4 py-2.5 dark:border-slate-700">
                    <input type="hidden" name="merge_file_ids[]" :value="m.id">
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-slate-900 dark:text-white" x-text="m.file_number"></span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="[m.plot_no ? 'Plot ' + m.plot_no : null, m.district, m.lga].filter(Boolean).join(' · ')"></span>
                    </span>
                    <button type="button" @click="removeMerge(m.id)" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-slate-800" aria-label="Remove">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
            </template>
            <div class="relative mt-3">
                <label for="merge-search" class="sr-only">Find another land file</label>
                <input id="merge-search" type="search" x-model="mq" @input.debounce.400ms="msearch()" @keydown.enter.prevent placeholder="Find another file by its number" autocomplete="off" class="{{ $field }}">
                <p x-show="msearched && ! msearching && mresults.length === 0" x-cloak class="mt-2 text-sm text-slate-500 dark:text-slate-400">No other land file matches that number.</p>
                <ul x-show="mresults.length" x-cloak class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <template x-for="f in mresults" :key="f.id">
                        <li><button type="button" @click="addMerge(f)" class="block w-full px-4 py-3 text-left text-sm text-slate-900 hover:bg-slate-50 dark:text-slate-100 dark:hover:bg-slate-800" x-text="f.file_number"></button></li>
                    </template>
                </ul>
            </div>
            <x-input-error :messages="$errors->get('merge_file_ids')" class="mt-2" />
            <x-input-error :messages="$errors->get('merge_file_ids.*')" class="mt-2" />
        </div>
    </section>

    {{-- Step 3: Proposal --}}
    <section data-step="3" x-show="step === 3" x-cloak class="space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Your proposal</h2>
            <p class="{{ $hint }}" x-text="type ? 'Details for ' + type + '.' : 'Choose the type of request in step 1.'"></p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div x-show="type === 'Sub-Division'" x-cloak>
                    <label for="proposed_plots" class="{{ $label }}">Number of proposed plots <span class="text-rose-500">*</span></label>
                    <input id="proposed_plots" type="number" min="2" max="200" name="proposed_plots" value="{{ old('proposed_plots') }}" inputmode="numeric" :disabled="type !== 'Sub-Division'" :required="type === 'Sub-Division'" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('proposed_plots')" class="mt-2" />
                </div>
                <div x-show="type === 'Extension'" x-cloak>
                    <label for="extension_area" class="{{ $label }}">Extension requested (m²) <span class="text-rose-500">*</span></label>
                    <input id="extension_area" type="number" min="1" step="0.01" name="extension_area" value="{{ old('extension_area') }}" inputmode="decimal" :disabled="type !== 'Extension'" :required="type === 'Extension'" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('extension_area')" class="mt-2" />
                </div>
                <div x-show="type === 'Change of Purpose'" x-cloak>
                    <span class="{{ $label }}">Current use (land file)</span>
                    <div class="{{ $locked }}" x-text="file && file.land_use ? file.land_use : 'Not recorded'"></div>
                </div>
                <div x-show="type === 'Change of Purpose'" x-cloak>
                    <label for="proposed_use" class="{{ $label }}">Proposed use <span class="text-rose-500">*</span></label>
                    <select id="proposed_use" name="proposed_use" x-model="proposed" :disabled="type !== 'Change of Purpose'" :required="type === 'Change of Purpose'" class="{{ $field }}">
                        <option value="">Select…</option>
                        @foreach ($proposedUses as $use)
                            <option value="{{ $use }}">{{ $use }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('proposed_use')" class="mt-2" />
                </div>
                <div x-show="type === 'Change of Purpose' && proposed === 'Other'" x-cloak>
                    <label for="proposed_use_other" class="{{ $label }}">Specify the use <span class="text-rose-500">*</span></label>
                    <input id="proposed_use_other" name="proposed_use_other" value="{{ old('proposed_use_other') }}" maxlength="100" :disabled="type !== 'Change of Purpose' || proposed !== 'Other'" :required="type === 'Change of Purpose' && proposed === 'Other'" class="{{ $field }} uppercase">
                    <x-input-error :messages="$errors->get('proposed_use_other')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="{{ $label }}">Describe what you want <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" class="{{ $field }}">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Building on the land</h2>
            <p class="{{ $hint }}">The KSI checks this on site. A building put up without a Building Plan Approval makes the betterment fee {{ $penalty }} times the standard fee.</p>
            <fieldset class="mt-4">
                <legend class="{{ $label }}">Is there a building on the land? <span class="text-rose-500">*</span></legend>
                <div class="mt-2 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="building_exists" value="yes" x-model="building" required class="{{ $radio }}"> Yes</label>
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="building_exists" value="no" x-model="building" required class="{{ $radio }}"> No</label>
                </div>
                <x-input-error :messages="$errors->get('building_exists')" class="mt-2" />
            </fieldset>
            <fieldset class="mt-4" x-show="building === 'yes'" x-cloak>
                <legend class="{{ $label }}">Does it have a Building Plan Approval (BPA)? <span class="text-rose-500">*</span></legend>
                <div class="mt-2 flex flex-wrap gap-6">
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="has_bpa" value="yes" x-model="bpa" :disabled="building !== 'yes'" :required="building === 'yes'" class="{{ $radio }}"> Yes</label>
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="has_bpa" value="no" x-model="bpa" :disabled="building !== 'yes'" :required="building === 'yes'" class="{{ $radio }}"> No</label>
                </div>
                <x-input-error :messages="$errors->get('has_bpa')" class="mt-2" />
            </fieldset>
            <p x-show="penalty" x-cloak class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
                <span>Without a Building Plan Approval, the betterment fee will be <span class="font-bold">×{{ $penalty }}</span> the standard fee if the KSI confirms the building. Upload the BPA in step 5 if you have one.</span>
            </p>
        </div>
    </section>

    {{-- Step 4: Applicant --}}
    <section data-step="4" x-show="step === 4" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Applicant and contact</h2>
        <p class="{{ $hint }}">The name as on your title documents. Updates go to the mobile number by SMS.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="applicant_name" class="{{ $label }}">Applicant name <span class="text-rose-500">*</span></label>
                <input id="applicant_name" name="applicant_name" x-model="applicant_name" required minlength="3" maxlength="300" autocomplete="name" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('applicant_name')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <label for="contact_address" class="{{ $label }}">Contact address <span class="text-rose-500">*</span></label>
                <input id="contact_address" name="contact_address" value="{{ old('contact_address') }}" required maxlength="500" autocomplete="street-address" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('contact_address')" class="mt-2" />
            </div>
            <div>
                <label for="phone" class="{{ $label }}">Mobile number <span class="text-rose-500">*</span></label>
                <input id="phone" name="phone" x-model="phone" required inputmode="tel" autocomplete="tel" pattern="(\+?234|0)[789][01][0-9]{8}" placeholder="08031234567" class="{{ $field }}">
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <label for="email" class="{{ $label }}">Email <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="email" type="email" name="email" value="{{ old('email', $applicant['email'] ?? '') }}" maxlength="150" autocapitalize="none" class="{{ $field }}">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>
    </section>

    {{-- Step 5: Documents --}}
    <section data-step="5" x-show="step === 5" x-cloak class="{{ $card }}">
        <h2 class="{{ $h2 }}">Documents</h2>
        <fieldset class="mt-4">
            <legend class="sr-only">Documents you are sending</legend>
            <p class="{{ $label }}">Documents you are sending</p>
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
                <input id="documents_other" name="documents_other" value="{{ old('documents_other') }}" :disabled="! docOther" :required="docOther" maxlength="200" class="{{ $field }} uppercase">
                <x-input-error :messages="$errors->get('documents_other')" class="mt-2" />
            </div>
        </fieldset>

        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
            <label for="files" class="{{ $label }}">Upload <span class="text-rose-500">*</span></label>
            <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">PDF, JPG or PNG. Up to {{ \App\Support\Portal\PortalForm::MAX_FILES }} files, {{ (int) (config('kamma.upload_max_kb') / 1024) }} MB each.</p>
            <input id="files" type="file" name="files[]" multiple required accept="{{ $accept }}"
                   @change="fileNames = [...$event.target.files].map((f) => f.name)"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-slate-300 dark:file:bg-emerald-500/10 dark:file:text-emerald-300">
            <x-input-error :messages="$errors->get('files')" class="mt-2" />
            <x-input-error :messages="$errors->get('files.*')" class="mt-2" />
            <ul x-show="fileNames.length" x-cloak class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                <template x-for="n in fileNames" :key="n"><li class="truncate" x-text="n"></li></template>
            </ul>
        </div>
    </section>

    {{-- Step 6: Fees, declaration and review --}}
    <section data-step="6" x-show="step === 6" x-cloak class="space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Fees</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                    <dt class="{{ $dt }}">Application processing fee</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900 dark:text-white" x-text="feeText"></dd>
                    <dd class="mt-1 text-xs text-slate-500 dark:text-slate-400">Raised when KAMMA opens your Shadow File. Pay it to move your request on.</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                    <dt class="{{ $dt }}">Betterment</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">After the KSI</dd>
                    <dd class="mt-1 text-xs text-slate-500 dark:text-slate-400">Worked out on the area the KSI recommends. <span x-show="penalty" x-cloak class="font-semibold text-rose-600 dark:text-rose-400">×{{ $penalty }} if the building has no BPA.</span></dd>
                </div>
            </dl>
            <label class="mt-6 flex items-start gap-3 rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                <input type="checkbox" name="declaration" value="1" required @checked(old('declaration')) class="{{ $check }} mt-0.5">
                <span class="text-sm text-slate-700 dark:text-slate-300">
                    <span class="font-semibold">Declaration.</span> I confirm that the information and documents I am sending are correct, and that I am the holder of the land file or act for them.
                </span>
            </label>
            <x-input-error :messages="$errors->get('declaration')" class="mt-2" />
        </div>

        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Review</h2>
            <p class="{{ $hint }}">Check before you submit. Use Back, or tap a step above, to change anything.</p>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Type of request</dt><dd class="{{ $dd }}" x-text="type || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Land file</dt><dd class="{{ $dd }}" x-text="file ? file.file_number : '—'"></dd></div>
                <div><dt class="{{ $dt }}">Plot No. · District · LGA</dt><dd class="{{ $dd }}" x-text="file ? [file.plot_no, file.district, file.lga].filter(Boolean).join(' · ') : '—'"></dd></div>
                <div x-show="type === 'Merger'"><dt class="{{ $dt }}">Merge with</dt><dd class="{{ $dd }}" x-text="merges.map((m) => m.file_number).join(', ') || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Building on the land</dt><dd class="{{ $dd }}" x-text="building === 'yes' ? (bpa === 'yes' ? 'Yes, with BPA' : (bpa === 'no' ? 'Yes, no BPA' : 'Yes')) : (building === 'no' ? 'No' : '—')"></dd></div>
                <div><dt class="{{ $dt }}">Applicant</dt><dd class="{{ $dd }} uppercase" x-text="applicant_name || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Mobile number</dt><dd class="{{ $dd }}" x-text="phone || '—'"></dd></div>
                <div><dt class="{{ $dt }}">Files</dt><dd class="{{ $dd }}" x-text="fileNames.length ? fileNames.join(', ') : 'None chosen'"></dd></div>
            </dl>
        </div>
    </section>

    <x-wizard-nav :cancel="route('portal.dashboard')">Submit request</x-wizard-nav>
</form>
@endsection
