{{-- File selector (KLAES rule: file numbers are picked, never typed). source "klaes" = KLAES land files
     (dbo.file_indexings, read-only); source "shadow" = KAMMA's commissioned shadow files. On pick the details fill in and lock
     (Plot No., District, LGA, Location); "Change file" clears them. Fires a "file-picked" event with the file. --}}
@props(['name' => 'file_id', 'source' => 'klaes', 'label' => null, 'required' => true, 'hint' => null])

@php
    $url = $source === 'shadow' ? route('ui.shadow-files') : route('klaes.files.search');
    $label ??= $source === 'shadow' ? 'KAMMA shadow file' : 'Land file (KLAES)';
    $placeholder = $source === 'shadow' ? 'e.g. RES-2016-452, KMA-TRK tracking ID or file title' : 'e.g. RES-2016-452 or the holder\'s name';
    $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $locked = 'rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
@endphp

<div x-data="{
        q: '', results: [], file: null, searching: false, searched: false,
        async search() {
            if (this.q.trim().length < 2) { this.results = []; this.searched = false; return; }
            this.searching = true;
            try {
                const r = await fetch(@js($url) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                this.results = r.ok ? ((await r.json()).files || []) : [];
            } catch (e) { this.results = []; }
            this.searching = false; this.searched = true;
        },
        pick(f) { this.file = f; this.results = []; this.q = ''; $dispatch('file-picked', f); },
        change() { this.file = null; $dispatch('file-picked', null); this.$nextTick(() => this.$refs.q && this.$refs.q.focus()); },
     }" {{ $attributes }}>
    <input type="hidden" name="{{ $name }}" :value="file ? file.id : ''">
    <input type="hidden" name="{{ $name }}_number" :value="file ? file.file_number : ''">

    <div x-show="! file" class="relative">
        <label class="{{ $lbl }}">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input x-ref="q" type="search" x-model="q" @input.debounce.350ms="search()" @keydown.escape="results = []" @keydown.enter.prevent
                   placeholder="{{ $placeholder }}" autocomplete="off" class="{{ $field }} pl-9" @if ($required) :required="! file" @endif>
        </div>
        @if ($hint)
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
        @endif
        <p x-show="searching" x-cloak class="mt-2 text-xs text-slate-500 dark:text-slate-400">Searching…</p>
        <p x-show="searched && ! searching && results.length === 0" x-cloak class="mt-2 text-xs text-slate-500 dark:text-slate-400">No file matches that.</p>
        <ul x-show="results.length" x-cloak class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
            <template x-for="f in results" :key="f.id">
                <li>
                    <button type="button" @click="pick(f)" class="block w-full px-4 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-800">
                        <span class="block text-sm font-medium text-slate-900 dark:text-slate-100" x-text="f.file_number"></span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="[f.file_title, f.plot_no ? 'Plot ' + f.plot_no : null, f.district, f.lga].filter(Boolean).join(' · ')"></span>
                    </button>
                </li>
            </template>
        </ul>
    </div>

    <template x-if="file">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-indigo-50 px-4 py-3 dark:bg-indigo-500/10">
                <div>
                    <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-200" x-text="file.file_number"></p>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300" x-text="file.file_title || 'No title recorded'"></p>
                </div>
                <button type="button" @click="change()" class="rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-slate-900 dark:text-indigo-300 dark:hover:bg-slate-800">Change file</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><span class="{{ $lbl }}">Plot No.</span><div class="{{ $locked }}" x-text="file.plot_no || '—'"></div></div>
                <div><span class="{{ $lbl }}">District</span><div class="{{ $locked }}" x-text="file.district || '—'"></div></div>
                <div><span class="{{ $lbl }}">LGA</span><div class="{{ $locked }}" x-text="file.lga || '—'"></div></div>
                <div><span class="{{ $lbl }}">Location</span><div class="{{ $locked }}" x-text="file.location || '—'"></div></div>
            </div>
        </div>
    </template>
</div>
