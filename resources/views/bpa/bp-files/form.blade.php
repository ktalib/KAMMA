{{-- Building Plan Files: Indexing (existing file), Commissioning (new file) and Edit (user briefing 2026-11-25).
     Two columns (BP file first since 2026-11-26). Left: the BP file's own indexing. Right: pick the land file; its KLAES indexing record shows, locked. The BP side: the
     plot, district, LGA, location and land use are carried over from the land file and locked ("those will not change").
     Tracking IDs are display only, fetched by number (user 2026-11-26): the land file's from the KLAES grouping, the BP
     file's from the BP grouping. --}}
@php
    $titles = ['indexed' => 'Index Existing BP File', 'commissioned' => 'Commission New BP File', 'edit' => 'Edit BP File'];
    $action = match ($mode) {
        'indexed' => route('bpa.bp-files.store-indexed'),
        'commissioned' => route('bpa.bp-files.store-commissioned'),
        default => route('bpa.bp-files.update', $file),
    };
    $label = 'mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $ro = 'min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-100';
    $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $card = 'rounded-2xl border bg-white shadow-sm dark:bg-slate-900';
    $err = fn ($k) => $errors->first($k);
    $back = match ($mode) {
        'indexed' => ['bpa.bp-files.indexed', 'Indexed BP Files'],
        'commissioned' => ['bpa.bp-files.commissioned', 'Commissioned BP Files'],
        default => [$file->origin === 'indexed' ? 'bpa.bp-files.indexed' : 'bpa.bp-files.commissioned', 'Back to the list'],
    };
    $notInKlaes = (bool) old('land_not_in_klaes', false);
@endphp
<x-app-layout :title="$titles[$mode]">
    <form method="POST" action="{{ $action }}" novalidate class="space-y-6" @submit="saving = true"
          x-data="bpFileForm(@js([
              'mode' => $mode,
              'landUrl' => route('bpa.bp-files.land', ['id' => '__ID__']),
              'searchUrl' => route('klaes.files.search'),
              'checkUrl' => route('bpa.bp-files.check-number'),
              'zones' => $zones,
              'saved' => $file ? $file->landFile() + ['bp_files' => [], 'tracking_id' => $landTrackingId] : null,
              'old' => [
                  'file_id' => old('klaes_file_indexing_id'),
                  'not_in_klaes' => $notInKlaes,
                  'zone' => old('bp_zone_id', $file?->bp_zone_id),
                  'title' => old('bp_file_title', $file?->bp_file_title),
                  'year' => old('bp_year'),
                  'serial' => old('bp_serial'),
                  'typed' => [
                      'land_file_number' => old('land_file_number'), 'land_file_title' => old('land_file_title'), 'plot_no' => old('plot_no'),
                      'district' => old('district'), 'lga' => old('lga'), 'location' => old('location'), 'land_use' => old('land_use'), 'plot_size' => old('plot_size'),
                  ],
              ],
          ]))">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ $mode === 'indexed' ? 'DMS · Indexing' : 'Building Plan Files' }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $titles[$mode] }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                    @if ($mode === 'indexed')
                        An existing BP file: index the BP file under its own number (BP-year-serial), which must be in the grouping, then pick its land file on the right.
                    @elseif ($mode === 'commissioned')
                        A new BP file: pick its land file on the right; saving gives the BP file the next serial number of {{ now()->format('Y') }}.
                    @else
                        Correct the BP file's own indexing, e.g. set the zone of a file saved as "Other". The BP number and the land file stay as they are.
                    @endif
                </p>
            </div>
            <a href="{{ route($back[0]) }}" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ $back[1] }}</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                Please correct the {{ $errors->count() === 1 ? 'item' : $errors->count().' items' }} marked in red.
                @if ($errors->has('bp_serial') || $errors->has('klaes_file_indexing_id'))<span class="font-semibold">{{ $errors->first('bp_serial') ?: $errors->first('klaes_file_indexing_id') }}</span>@endif
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- FIRST: the Building Plan file (BP file before the land file, user 2026-11-26) --}}
            <section class="{{ $card }} border-emerald-200 dark:border-emerald-500/30">
                <div class="rounded-t-2xl bg-emerald-600 px-5 py-3 text-white dark:bg-emerald-500/80">
                    <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-widest"><i data-lucide="building-2" class="h-4 w-4"></i> Building Plan File</h2>
                    <p class="text-xs text-emerald-100">BP-year-serial, the zone and the BP file's indexing.</p>
                </div>
                <div class="space-y-4 p-5">
                    {{-- The BP number --}}
                    @if ($mode === 'indexed')
                        <div>
                            <span class="{{ $label }}">BP File Number <span class="text-rose-500">*</span></span>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-slate-100 px-3 py-2 font-mono text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">BP</span>
                                <span class="text-slate-400">-</span>
                                <select name="bp_year" x-model="year" @change="checkNumber()" aria-label="BP year" class="{{ $field }} w-28 font-mono">
                                    <option value="">Year</option>
                                    @foreach ($years as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                                <span class="text-slate-400">-</span>
                                <input name="bp_serial" type="number" min="1" x-model="serial" @input.debounce.400ms="checkNumber()" placeholder="Serial" aria-label="BP serial number" class="{{ $field }} w-32 font-mono">
                            </div>
                            <p x-show="numberMsg" x-cloak class="mt-2 text-xs font-semibold"
                               :class="numberState === 'available' ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400'" x-text="numberMsg"></p>
                            @if ($err('bp_year') || $err('bp_serial'))<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $err('bp_year') ?: $err('bp_serial') }}</p>@endif
                            @if ($years->isEmpty())
                                <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">The grouping is empty: the System Admin runs <span class="font-mono">php artisan kamma:bp-grouping</span> first.</p>
                            @endif
                        </div>
                    @elseif ($mode === 'commissioned')
                        <div>
                            <span class="{{ $label }}">BP File Number</span>
                            <div class="{{ $ro }} font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $next }} <span class="font-sans text-xs font-medium text-slate-400">(next; given on save)</span></div>
                        </div>
                    @else
                        <div>
                            <span class="{{ $label }}">BP File Number</span>
                            <div class="{{ $ro }} font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $file->bp_file_no }} <span class="font-sans text-xs font-medium text-slate-400">({{ $file->origin }})</span></div>
                        </div>
                    @endif

                    {{-- The BP number's tracking ID, from the BP grouping by BP number (display only, user 2026-11-26) --}}
                    <div>
                        <span class="{{ $label }}">BP Tracking ID</span>
                        @if ($mode === 'indexed')
                            <div class="{{ $ro }} font-mono font-bold text-emerald-700 dark:text-emerald-300" x-text="bpTrackingId || '—'"></div>
                        @else
                            <div class="{{ $ro }} font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $bpTrackingId ?? '—' }}@if ($mode === 'commissioned' && ! $bpTrackingId) <span class="font-sans text-xs font-medium text-slate-400">(given on save)</span>@endif</div>
                        @endif
                    </div>

                    {{-- Zone (lookup; Other = not known yet, set it later) --}}
                    <div>
                        <label for="bp-zone" class="{{ $label }}">Zone <span class="text-rose-500">*</span></label>
                        <select id="bp-zone" name="bp_zone_id" x-model="zone" class="{{ $field }}">
                            <option value="">Select the zone…</option>
                            @foreach ($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="zoneHint" x-cloak class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="zoneHint"></p>
                        @if ($err('bp_zone_id'))<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $err('bp_zone_id') }}</p>@endif
                    </div>

                    <div>
                        <label for="bp-title" class="{{ $label }}">BP File Title</label>
                        <input id="bp-title" name="bp_file_title" x-model="title" maxlength="500" class="{{ $field }} uppercase" placeholder="Filled from the land file title">
                        @if ($err('bp_file_title'))<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $err('bp_file_title') }}</p>@endif
                    </div>

                    {{-- Carried over from the land file and locked --}}
                    <div class="rounded-xl border border-dashed border-slate-200 p-4 dark:border-slate-700">
                        <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-400">From the land file (locked)</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div><span class="{{ $label }}">Plot No.</span><div class="{{ $ro }}" x-text="land.plot_no || '—'"></div></div>
                            <div><span class="{{ $label }}">District</span><div class="{{ $ro }}" x-text="land.district || '—'"></div></div>
                            <div><span class="{{ $label }}">LGA</span><div class="{{ $ro }}" x-text="land.lga || '—'"></div></div>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2"><span class="{{ $label }}">Location</span><div class="{{ $ro }}" x-text="land.location || '—'"></div></div>
                            <div><span class="{{ $label }}">Land Use</span><div class="{{ $ro }}" x-text="land.land_use || '—'"></div></div>
                        </div>
                    </div>

                    {{-- Archive details (KLAES indexing fields) --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="bp-registry" class="{{ $label }}">Registry</label><input id="bp-registry" name="registry" value="{{ old('registry', $file?->registry) }}" maxlength="150" class="{{ $field }}"></div>
                        <div><label for="bp-batch" class="{{ $label }}">Batch No.</label><input id="bp-batch" name="batch_no" value="{{ old('batch_no', $file?->batch_no) }}" maxlength="100" class="{{ $field }}"></div>
                        <div><label for="bp-shelf" class="{{ $label }}">Shelf / Rack No.</label><input id="bp-shelf" name="shelf_location" value="{{ old('shelf_location', $file?->shelf_location) }}" maxlength="150" class="{{ $field }}"></div>
                        <div><label for="bp-serialno" class="{{ $label }}">Serial No.</label><input id="bp-serialno" name="serial_no" value="{{ old('serial_no', $file?->serial_no) }}" maxlength="100" class="{{ $field }}"></div>
                    </div>
                    <div>
                        <label for="bp-remarks" class="{{ $label }}">Remarks</label>
                        <textarea id="bp-remarks" name="remarks" rows="2" maxlength="1000" class="{{ $field }}">{{ old('remarks', $file?->remarks) }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><span class="{{ $label }}">{{ $mode === 'edit' ? 'Indexed By' : 'Indexed By (you)' }}</span><div class="{{ $ro }}">{{ $mode === 'edit' ? $file->indexed_by_name : auth()->user()->name }}</div></div>
                        <div><span class="{{ $label }}">Date</span><div class="{{ $ro }}">{{ $mode === 'edit' ? $file->indexed_at?->format('d M Y, g:i a') : now()->format('d M Y') }}</div></div>
                    </div>
                </div>
            </section>

            {{-- SECOND: the land file (KLAES indexing record) --}}
            <section class="{{ $card }} border-indigo-200 dark:border-indigo-500/30">
                <div class="rounded-t-2xl bg-indigo-600 px-5 py-3 text-white dark:bg-indigo-500/80">
                    <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-widest"><i data-lucide="landmark" class="h-4 w-4"></i> Land File</h2>
                    <p class="text-xs text-indigo-100">The existing KLAES indexing record. Nothing here is edited.</p>
                </div>
                <div class="space-y-4 p-5">
                    @if ($mode !== 'edit')
                        <input type="hidden" name="klaes_file_indexing_id" :value="! notInKlaes && file ? file.id : ''">
                        <input type="hidden" name="land_not_in_klaes" :value="notInKlaes ? 1 : 0">

                        <div x-show="! file && ! notInKlaes" class="relative">
                            <label for="land-q" class="{{ $label }}">Select the land file number <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input id="land-q" type="search" x-model="q" @input.debounce.350ms="search()" @keydown.enter.prevent @keydown.escape="results = []" autocomplete="off"
                                       placeholder="File number or holder's name, e.g. RES-2016-452" class="{{ $field }} pl-9">
                            </div>
                            <p x-show="searching" x-cloak class="mt-2 text-xs text-slate-500">Searching KLAES indexing…</p>
                            <p x-show="searched && ! searching && ! results.length" x-cloak class="mt-2 text-xs text-slate-500">No indexed KLAES file matches that.</p>
                            <ul x-show="results.length" x-cloak class="absolute z-20 mt-1 max-h-72 w-full divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg dark:divide-slate-800 dark:border-slate-700 dark:bg-slate-900">
                                <template x-for="f in results" :key="f.id">
                                    <li>
                                        <button type="button" @click="pick(f)" class="block w-full px-4 py-2.5 text-left hover:bg-indigo-50 dark:hover:bg-slate-800">
                                            <span class="block font-mono text-sm font-bold text-indigo-700 dark:text-indigo-300" x-text="f.file_number"></span>
                                            <span class="block text-xs text-slate-600 dark:text-slate-300" x-text="[f.file_title, f.plot_no && ('Plot ' + f.plot_no), f.district, f.lga].filter(Boolean).join(' · ')"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            @if ($err('klaes_file_indexing_id'))<p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $err('klaes_file_indexing_id') }}</p>@endif
                        </div>

                        <label x-show="! file" class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" x-model="notInKlaes" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
                            <span>Land file is not in KLAES indexing: type its details (the BP file is flagged).</span>
                        </label>
                    @endif

                    <p x-show="loading" x-cloak class="text-sm text-slate-500">Loading the KLAES indexing record…</p>
                    <p x-show="error" x-cloak x-text="error" class="text-sm text-rose-600 dark:text-rose-400"></p>

                    {{-- The record from KLAES --}}
                    <template x-if="file && ! notInKlaes">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <span class="{{ $label }}">File Number</span>
                                    <div class="{{ $ro }} font-mono font-bold text-indigo-700 dark:text-indigo-300" x-text="file.file_number"></div>
                                </div>
                                @if ($mode !== 'edit')
                                    <button type="button" @click="changeFile()" class="mt-5 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-rose-500 dark:hover:bg-slate-800" title="Pick another land file" aria-label="Pick another land file"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></button>
                                @endif
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="sm:col-span-2"><span class="{{ $label }}">File Title</span><div class="{{ $ro }}" x-text="file.file_title || '—'"></div></div>
                                <div><span class="{{ $label }}">Tracking ID</span><div class="{{ $ro }} font-mono font-bold text-indigo-700 dark:text-indigo-300" x-text="file.tracking_id || '—'"></div></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div><span class="{{ $label }}">Plot No.</span><div class="{{ $ro }}" x-text="file.plot_no || '—'"></div></div>
                                <div><span class="{{ $label }}">District</span><div class="{{ $ro }}" x-text="file.district || '—'"></div></div>
                                <div><span class="{{ $label }}">LGA</span><div class="{{ $ro }}" x-text="file.lga || '—'"></div></div>
                            </div>
                            <div><span class="{{ $label }}">Location</span><div class="{{ $ro }}" x-text="file.location || '—'"></div></div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div><span class="{{ $label }}">Land Use</span><div class="{{ $ro }}" x-text="file.land_use || '—'"></div></div>
                                <div><span class="{{ $label }}">Plot Size</span><div class="{{ $ro }}" x-text="file.plot_size || '—'"></div></div>
                                <div><span class="{{ $label }}">Street</span><div class="{{ $ro }}" x-text="file.street_name || '—'"></div></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><span class="{{ $label }}">Phone</span><div class="{{ $ro }}" x-text="file.phone || '—'"></div></div>
                                <div><span class="{{ $label }}">Title Status</span><div class="{{ $ro }}" x-text="file.title_status || '—'"></div></div>
                            </div>
                            <div><span class="{{ $label }}">Address</span><div class="{{ $ro }}" x-text="file.address || '—'"></div></div>
                            <p x-show="file.bp_files && file.bp_files.length" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                               x-text="file.bp_files && file.bp_files.length ? 'This land file already has: ' + file.bp_files.join(', ') + '.' : ''"></p>
                        </div>
                    </template>

                    {{-- Typed land file (not in KLAES), or a saved one shown on Edit --}}
                    @if ($mode === 'edit' && ! $file->land_in_klaes)
                        <p class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">This land file was typed: it is not in KLAES indexing.</p>
                    @endif
                    @if ($mode !== 'edit')
                        <div x-show="notInKlaes" x-cloak class="space-y-4">
                            <p class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">Typed land file: it will be flagged "Not in KLAES" on the register.</p>
                            <div>
                                <label for="t-no" class="{{ $label }}">Land File Number <span class="text-rose-500">*</span></label>
                                <input id="t-no" name="land_file_number" x-model="typed.land_file_number" @input="mirror()" :disabled="! notInKlaes" class="{{ $field }} font-mono">
                                @if ($err('land_file_number'))<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $err('land_file_number') }}</p>@endif
                            </div>
                            <div><label for="t-title" class="{{ $label }}">File Title</label><input id="t-title" name="land_file_title" x-model="typed.land_file_title" @input="mirror()" :disabled="! notInKlaes" class="{{ $field }} uppercase"></div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div><label for="t-plot" class="{{ $label }}">Plot No.</label><input id="t-plot" name="plot_no" x-model="typed.plot_no" :disabled="! notInKlaes" class="{{ $field }}"></div>
                                <div><label for="t-district" class="{{ $label }}">District</label><input id="t-district" name="district" x-model="typed.district" :disabled="! notInKlaes" class="{{ $field }} uppercase"></div>
                                <div><label for="t-lga" class="{{ $label }}">LGA</label><input id="t-lga" name="lga" x-model="typed.lga" :disabled="! notInKlaes" class="{{ $field }} uppercase"></div>
                            </div>
                            <div><label for="t-loc" class="{{ $label }}">Location</label><input id="t-loc" name="location" x-model="typed.location" :disabled="! notInKlaes" class="{{ $field }} uppercase"></div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label for="t-use" class="{{ $label }}">Land Use</label><input id="t-use" name="land_use" x-model="typed.land_use" :disabled="! notInKlaes" placeholder="e.g. RESIDENTIAL" class="{{ $field }} uppercase"></div>
                                <div><label for="t-size" class="{{ $label }}">Plot Size</label><input id="t-size" name="plot_size" x-model="typed.plot_size" :disabled="! notInKlaes" class="{{ $field }}"></div>
                            </div>
                            <button type="button" @click="notInKlaes = false" class="text-xs font-semibold text-indigo-700 hover:underline dark:text-indigo-300">Search KLAES instead</button>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route($back[0]) }}" class="rounded-xl px-5 py-2.5 text-xs font-black uppercase tracking-wider text-slate-500 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800">Cancel</a>
            <button type="submit" :disabled="saving"
                    class="inline-flex min-w-[170px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400">
                <i data-lucide="save" class="h-4 w-4"></i>
                {{ ['indexed' => 'Index BP File', 'commissioned' => 'Commission BP File', 'edit' => 'Save Changes'][$mode] }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            function bpFileForm(cfg) {
                const getJson = async (url) => {
                    const r = await fetch(url, { headers: { Accept: 'application/json' } });
                    const d = await r.json().catch(() => ({}));
                    if (! r.ok) throw new Error(d.message || 'Something went wrong.');
                    return d;
                };
                return {
                    q: '', results: [], searching: false, searched: false, loading: false, error: '', saving: false,
                    file: cfg.saved, notInKlaes: !! cfg.old.not_in_klaes, typed: Object.assign({}, cfg.old.typed),
                    zone: cfg.old.zone ? String(cfg.old.zone) : '', title: cfg.old.title || '',
                    year: cfg.old.year ? String(cfg.old.year) : '', serial: cfg.old.serial || '', numberState: '', numberMsg: '', bpTrackingId: '',

                    async init() {
                        if (! cfg.saved && cfg.old.file_id && ! this.notInKlaes) await this.load(cfg.old.file_id, false);
                        if (this.year && this.serial) this.checkNumber();
                        // Typed fields must not reach the server while a KLAES file is used, and vice versa.
                        this.$watch('notInKlaes', (on) => { if (on) { this.file = null; this.title = this.typed.land_file_title || ''; } });
                    },

                    get land() {
                        return this.notInKlaes ? this.typed : (this.file || {});
                    },

                    get zoneHint() {
                        const z = cfg.zones.find((z) => String(z.id) === this.zone);
                        return z ? z.description : '';
                    },

                    async search() {
                        this.error = '';
                        if (this.q.trim().length < 2) { this.results = []; this.searched = false; return; }
                        this.searching = true;
                        try { this.results = (await getJson(cfg.searchUrl + '?q=' + encodeURIComponent(this.q.trim()))).files; }
                        catch (e) { this.error = 'The KLAES file search did not respond. Try again.'; }
                        finally { this.searching = false; this.searched = true; }
                    },

                    async load(id, backfill = true) {
                        this.loading = true; this.error = '';
                        try {
                            this.file = (await getJson(cfg.landUrl.replace('__ID__', id))).file;
                            // A newly picked land file replaces what the previous one filled in.
                            if (backfill) this.title = this.file.file_title || '';
                            this.$nextTick(() => window.lucide && lucide.createIcons());
                        } catch (e) {
                            this.file = null; this.error = 'That land file could not be loaded from KLAES. Pick it again.';
                        } finally { this.loading = false; }
                    },

                    pick(f) { this.results = []; this.searched = false; this.q = ''; this.load(f.id); },

                    changeFile() {
                        this.file = null; this.title = '';
                        this.$nextTick(() => document.getElementById('land-q')?.focus());
                    },

                    mirror() { if (this.notInKlaes && ! this.title) this.title = this.typed.land_file_title || ''; },

                    async checkNumber() {
                        if (cfg.mode !== 'indexed') return;
                        this.numberState = ''; this.numberMsg = ''; this.bpTrackingId = '';
                        if (! this.year || ! this.serial) return;
                        try {
                            const r = await getJson(cfg.checkUrl + '?year=' + encodeURIComponent(this.year) + '&serial=' + encodeURIComponent(this.serial));
                            this.numberState = r.state; this.numberMsg = r.message || '';
                            if (r.state === 'available') this.bpTrackingId = r.tracking_id || '';
                        } catch (e) { this.numberMsg = ''; }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
