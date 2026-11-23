{{-- File number selector (KLAES rule: file numbers come from a selector and are never typed). Searches KLAES's
     indexed land files (read-only 'klaes' connection, NOLOCK), fills the hidden fields and announces the pick with
     a window event `file-picked` (detail = the file), so the form around it can backfill Plot No., District, LGA,
     Location, title, phone and address. A prefilled file locks the field. --}}
@props(['name' => 'file_number', 'idName' => 'klaes_file_indexing_id', 'value' => null, 'fileId' => null, 'required' => true, 'label' => 'File Number'])

<div x-data="{
        open: false, q: '', results: [], searching: false, searched: false, error: '',
        number: @js($value), id: @js($fileId), locked: @js(filled($value)),
        async search() {
            const term = this.q.trim();
            if (term.length < 2) { this.results = []; this.searched = false; return; }
            this.searching = true; this.error = '';
            try {
                const r = await fetch(@js(route('klaes.files.search')) + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' } });
                this.results = (await r.json()).files || [];
                this.searched = true;
            } catch (e) { this.error = 'The KLAES file search did not respond. Try again.'; }
            this.searching = false;
        },
        pick(f) {
            this.number = f.file_number; this.id = f.id; this.open = false; this.results = []; this.q = '';
            window.dispatchEvent(new CustomEvent('file-picked', { detail: f }));
        },
        clear() {
            if (this.locked) return;
            this.number = null; this.id = null;
            window.dispatchEvent(new CustomEvent('file-picked', { detail: null }));
        },
    }" {{ $attributes->merge(['class' => '']) }}>
    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    <div class="flex">
        <input type="text" name="{{ $name }}" :value="number" readonly @if ($required) required @endif
               @click="locked || (open = true)" placeholder="Select the land file…"
               class="w-full cursor-pointer rounded-l-lg border-slate-300 bg-slate-50 font-mono text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        <input type="hidden" name="{{ $idName }}" :value="id">
        <button type="button" @click="open = true" x-show="! locked"
                class="inline-flex items-center gap-1.5 border border-l-0 border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            <i data-lucide="search" class="h-4 w-4"></i> Select
        </button>
        <button type="button" @click="clear()" x-show="number && ! locked" x-cloak aria-label="Clear the file"
                class="rounded-r-lg border border-l-0 border-slate-300 bg-white px-2 text-slate-400 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-900">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
        <span x-show="locked" x-cloak class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-100 px-3 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"><i data-lucide="lock" class="h-3.5 w-3.5"></i></span>
    </div>
    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">From KLAES File Indexing (read-only). The details below fill in from the file.</p>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center bg-slate-950/60 p-4 pt-24" @keydown.escape.window="open = false">
            <div @click.outside="open = false" class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Select the land file (KLAES)</h3>
                    <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>
                <div class="p-5">
                    <input type="search" x-model="q" @input.debounce.350ms="search()" @keydown.enter.prevent placeholder="File number (any column) or file title"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" x-init="$watch('open', v => v && $nextTick(() => $el.focus()))">
                    <p x-show="searching" class="mt-2 text-xs text-slate-500 dark:text-slate-400">Searching KLAES…</p>
                    <p x-show="error" x-text="error" class="mt-2 text-xs text-rose-600"></p>
                    <p x-show="searched && ! searching && results.length === 0" class="mt-2 text-xs text-slate-500 dark:text-slate-400">No live land file in KLAES matches that.</p>
                    <ul class="mt-3 max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800" x-show="results.length">
                        <template x-for="f in results" :key="f.id">
                            <li>
                                <button type="button" @click="pick(f)" class="block w-full px-4 py-2.5 text-left hover:bg-emerald-50 dark:hover:bg-slate-800">
                                    <span class="font-mono text-sm font-bold text-emerald-700 dark:text-emerald-400" x-text="f.file_number"></span>
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-200" x-text="f.file_title || ''"></span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400" x-text="[f.plot_no ? 'Plot ' + f.plot_no : null, f.district, f.lga, f.land_use].filter(Boolean).join(' · ')"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </template>
</div>
