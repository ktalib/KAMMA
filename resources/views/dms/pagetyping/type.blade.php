{{-- Page Typing workspace for one file (KLAES Page Typing's "Typing" tab): page thumbnails on the left, the page in
     the middle (zoom / rotate), and Page Type, Subtype and the serial on the right. Save & Next moves on; a page's
     serial runs per page type within the file. --}}
<x-app-layout title="Page Typing">
    @php
        $label = 'mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div x-data="pageTyping(@js([
            'pages' => route('dms.scanning.pages', $file),
            'save' => route('dms.pagetyping.save', ['scan' => '__ID__']),
            'types' => $types,
        ]))" @keydown.window="key($event)" class="space-y-4">

        {{-- File header --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-0">
                <a href="{{ route('dms.pagetyping.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100"><i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Page Typing</a>
                <p class="mt-1 font-mono text-lg font-black text-emerald-700 dark:text-emerald-400">{{ $file->file_number }}</p>
                <p class="truncate text-sm text-slate-600 dark:text-slate-300">{{ collect([$file->file_title, $file->plot_number ? 'Plot '.$file->plot_number : null, $file->district, $file->lga])->filter()->join(' · ') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-56">
                    <div class="flex justify-between text-xs font-semibold text-slate-600 dark:text-slate-300"><span>Typed</span><span x-text="typedCount + ' / ' + pages.length"></span></div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-emerald-500 transition-all" :style="`width: ${pages.length ? typedCount / pages.length * 100 : 0}%`"></div></div>
                </div>
                <a href="{{ route('dms.scanning.index', ['file' => $file->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload more</a>
                <a x-show="typedCount" href="{{ route('archive.show', $file) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"><i data-lucide="library" class="h-4 w-4"></i> Doc-WARE</a>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[11rem_minmax(0,1fr)_20rem]">
            {{-- Thumbnails --}}
            <div class="max-h-[75vh] space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <template x-for="(p, i) in pages" :key="p.id">
                    <button type="button" @click="go(i)" :class="i === current ? 'ring-2 ring-blue-500' : 'ring-1 ring-slate-200 dark:ring-slate-700'" class="relative block w-full overflow-hidden rounded-lg bg-slate-50 text-left dark:bg-slate-800">
                        <img x-show="! p.pdf" :src="p.url" loading="lazy" alt="" class="h-28 w-full object-contain">
                        <div x-show="p.pdf" class="flex h-28 items-center justify-center text-xs font-bold text-rose-600">PDF</div>
                        <span class="absolute left-1 top-1 rounded bg-slate-900/70 px-1.5 text-[10px] font-bold text-white" x-text="p.no"></span>
                        <span class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full ring-2 ring-white" :class="p.type ? 'bg-emerald-500' : (p.more ? 'bg-orange-500' : 'bg-amber-400')"></span>
                        <span class="block truncate px-1.5 py-1 text-[10px]" :class="p.type ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500'" x-text="p.type ? p.type.code : 'Not typed'"></span>
                    </button>
                </template>
                <p x-show="! pages.length" class="p-3 text-center text-xs text-slate-500">Loading pages…</p>
            </div>

            {{-- Viewer --}}
            <div class="flex min-h-[60vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-1">
                        <button type="button" @click="prev()" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Previous page"><i data-lucide="chevron-left" class="h-4 w-4"></i></button>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="page ? 'Page ' + page.no + ' of ' + pages.length : ''"></span>
                        <button type="button" @click="next()" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Next page"><i data-lucide="chevron-right" class="h-4 w-4"></i></button>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="zoom = Math.max(0.5, zoom - 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom out"><i data-lucide="zoom-out" class="h-4 w-4"></i></button>
                        <span class="w-12 text-center text-xs text-slate-500" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" @click="zoom = Math.min(4, zoom + 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom in"><i data-lucide="zoom-in" class="h-4 w-4"></i></button>
                        <button type="button" @click="rotate = (rotate + 90) % 360" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Rotate"><i data-lucide="rotate-cw" class="h-4 w-4"></i></button>
                        <a :href="page ? page.url : '#'" target="_blank" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Open full size"><i data-lucide="maximize-2" class="h-4 w-4"></i></a>
                    </div>
                </div>
                <div class="flex flex-1 items-start justify-center overflow-auto p-4">
                    <template x-if="page && ! page.pdf">
                        <img :src="page.url" alt="Scanned page" class="max-w-full shadow-lg transition-transform" :style="`transform: scale(${zoom}) rotate(${rotate}deg); transform-origin: top center`">
                    </template>
                    <template x-if="page && page.pdf">
                        <iframe :src="page.url" class="h-[70vh] w-full rounded bg-white" title="PDF page"></iframe>
                    </template>
                </div>
            </div>

            {{-- Typing form --}}
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-bold text-slate-900 dark:text-white" x-text="page ? 'Type page ' + page.no : 'Page type'"></h2>
                        <span x-show="page && page.type" class="rounded-full bg-emerald-50 px-2.5 py-1 font-mono text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-text="page && page.type ? page.type.code : ''"></span>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="pt-type" class="{{ $label }}">Page Type <span class="text-rose-500">*</span></label>
                            <select id="pt-type" x-ref="type" x-model="form.page_type" @change="form.page_subtype = ''" class="{{ $field }}">
                                <option value="">Select page type</option>
                                <template x-for="t in Object.keys(cfg.types)" :key="t"><option :value="t" x-text="t"></option></template>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div x-show="form.page_type === 'Other'">
                            <label for="pt-type-o" class="{{ $label }}">Specify Page Type <span class="text-rose-500">*</span></label>
                            <input id="pt-type-o" type="text" x-model="form.page_type_others" maxlength="200" :disabled="form.page_type !== 'Other'" class="{{ $field }}">
                        </div>
                        <div x-show="form.page_type && form.page_type !== 'Other'">
                            <label for="pt-sub" class="{{ $label }}">Page Subtype</label>
                            <select id="pt-sub" x-model="form.page_subtype" class="{{ $field }}">
                                <option value="">— None —</option>
                                <template x-for="s in subtypes" :key="s"><option :value="s" x-text="s"></option></template>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div x-show="form.page_subtype === 'Other'">
                            <label for="pt-sub-o" class="{{ $label }}">Specify Subtype <span class="text-rose-500">*</span></label>
                            <input id="pt-sub-o" type="text" x-model="form.page_subtype_others" maxlength="200" :disabled="form.page_subtype !== 'Other'" class="{{ $field }}">
                        </div>

                        <p x-show="error" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="error"></p>
                        <p x-show="saved" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-text="saved"></p>

                        <div class="flex gap-2">
                            <button type="button" @click="save(true)" :disabled="saving || ! page"
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><i data-lucide="save" class="h-4 w-4"></i><span x-text="saving ? 'Saving…' : 'Save & Next'"></span></button>
                            <button type="button" @click="sameAsPrevious()" :disabled="! last" :title="last ? 'Use ' + last.label : 'Type a page first'"
                                    class="rounded-lg border border-slate-300 px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Same as last</button>
                        </div>
                        <p class="text-[11px] text-slate-400">Enter saves and moves on · ← → change page</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-3 text-xs font-black uppercase tracking-wider text-slate-500">Typed so far</h3>
                    <ul class="space-y-1.5">
                        <template x-for="[t, n] in summary" :key="t">
                            <li class="flex justify-between"><span class="text-slate-700 dark:text-slate-200" x-text="t"></span><span class="font-bold text-slate-900 dark:text-white" x-text="n"></span></li>
                        </template>
                        <li x-show="! summary.length" class="text-slate-500">Nothing typed yet.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function pageTyping(cfg) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const blank = () => ({ page_type: '', page_type_others: '', page_subtype: '', page_subtype_others: '' });
                return {
                    cfg, pages: [], current: 0, form: blank(), zoom: 1, rotate: 0, saving: false, error: '', saved: '', last: null,
                    get page() { return this.pages[this.current] || null; },
                    get typedCount() { return this.pages.filter((p) => p.type).length; },
                    get subtypes() { return cfg.types[this.form.page_type] || []; },
                    get summary() {
                        const m = {};
                        this.pages.forEach((p) => { if (p.type) m[p.type.label] = (m[p.type.label] || 0) + 1; });
                        return Object.entries(m);
                    },
                    async init() {
                        const r = await fetch(cfg.pages, { headers: { Accept: 'application/json' } });
                        this.pages = (await r.json()).pages || [];
                        const first = this.pages.findIndex((p) => ! p.type);
                        this.go(first === -1 ? 0 : first);
                    },
                    go(i) {
                        if (i < 0 || i >= this.pages.length) return;
                        this.current = i; this.zoom = 1; this.rotate = 0; this.error = '';
                        const t = this.page.type;
                        this.form = t ? { page_type: t.page_type, page_type_others: t.page_type_others || '', page_subtype: t.page_subtype || '', page_subtype_others: t.page_subtype_others || '' } : blank();
                        this.$nextTick(() => window.lucide && lucide.createIcons());
                    },
                    prev() { this.go(this.current - 1); },
                    next() { this.go(this.current + 1); },
                    sameAsPrevious() { if (this.last) this.form = { ...this.last.form }; },
                    key(e) {
                        if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) && e.key !== 'Enter') return;
                        if (e.key === 'ArrowLeft' && e.target.tagName !== 'SELECT') { e.preventDefault(); this.prev(); }
                        if (e.key === 'ArrowRight' && e.target.tagName !== 'SELECT') { e.preventDefault(); this.next(); }
                        if (e.key === 'Enter' && ! e.shiftKey) { e.preventDefault(); this.save(true); }
                    },
                    async save(advance) {
                        if (! this.page || this.saving) return;
                        this.error = ''; this.saved = '';
                        if (! this.form.page_type) { this.error = 'Select a page type.'; this.$refs.type.focus(); return; }
                        this.saving = true;
                        try {
                            const res = await fetch(cfg.save.replace('__ID__', this.page.id), {
                                method: 'POST', body: JSON.stringify(this.form),
                                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            });
                            const data = await res.json().catch(() => ({}));
                            if (! res.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not save.'));
                            const label = this.form.page_type === 'Other' ? this.form.page_type_others : this.form.page_type;
                            this.page.type = { ...this.form, label, code: data.code, serial: data.serial };
                            this.last = { form: { ...this.form }, label };
                            this.saved = data.message;
                            if (advance) {
                                const nextUntyped = this.pages.findIndex((p, i) => i > this.current && ! p.type);
                                if (nextUntyped !== -1) this.go(nextUntyped);
                                else if (this.current < this.pages.length - 1) this.go(this.current + 1);
                                else this.saved = data.message + ' All pages are typed.';
                            }
                        } catch (e) { this.error = e.message; }
                        finally { this.saving = false; }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
