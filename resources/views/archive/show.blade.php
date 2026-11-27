{{-- Doc-WARE file viewer: the file's details (Plot No., District, LGA, Location), its pages grouped by page type on
     the left and the page on the right, with previous / next, zoom and rotate.
     2026-11-25 (user: copy the KLAES UI, back end next): the KLAES Document Viewer tools. Previous File / Next File, and
     Edit Mode, which shows rotate left/right, brightness / contrast (on screen only), Crop, Replace, Delete page, Edit
     Page Classification, Quality Control Pass / Fail, Reassign and Move to NR. Since 2026-11-25 Rotate (saved), Replace,
     Delete page, Edit Type and QC are live (Archive\DocWareActionController; removed or replaced images go to the EDMS
     trash). Move to NR is off until KAMMA has its own registries; Crop and Reassign are still preview. --}}
<x-app-layout title="Doc-WARE · {{ $file->file_number }}">
    @php
        $list = $pages->values()->map(fn ($s, $i) => [
            'id' => $s->id, 'no' => $i + 1, 'url' => route('dms.scans.image', $s).'?v='.optional($s->updated_at)->timestamp, 'pdf' => str_contains((string) $s->mime, 'pdf'),
            'type' => $s->pageTyping?->typeLabel(), 'sub' => $s->pageTyping?->subtypeLabel(), 'code' => $s->pageTyping?->page_code,
            'raw_type' => $s->pageTyping?->page_type, 'raw_type_other' => $s->pageTyping?->page_type_others,
            'raw_sub' => $s->pageTyping?->page_subtype, 'raw_sub_other' => $s->pageTyping?->page_subtype_others,
            'qc' => $s->pageTyping?->qc_status,
        ]);
        $indexOf = $list->pluck('no', 'id');
        $start = max(0, (int) $list->search(fn ($p) => $p['id'] === (int) request('page')));
        $act = fn (string $name) => route('archive.pages.'.$name, ['scan' => '__ID__']);
    @endphp

    <div class="space-y-4" x-data="{ pages: @js($list), i: {{ $start }}, zoom: 1, rotate: 0, edit: {{ request()->has('page') ? 'true' : 'false' }}, bright: 100, contrast: 100, tune: false,
            types: @js($pageTypes), typeOpen: false, tf: {}, typeError: '', saving: false,
            act(name) { return ({ rotate: @js($act('rotate')), replace: @js($act('replace')), delete: @js($act('delete')), qc: @js($act('qc')) })[name].replace('__ID__', this.p.id) },
            openType() { this.tf = { page_type: this.p.raw_type || '', page_type_others: this.p.raw_type_other || '', page_subtype: this.p.raw_sub || '', page_subtype_others: this.p.raw_sub_other || '' }; this.typeError = ''; this.typeOpen = true },
            async saveType() {
                this.saving = true; this.typeError = '';
                const res = await fetch(@js(route('dms.pagetyping.save', ['scan' => '__ID__'])).replace('__ID__', this.p.id), { method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(this.tf) });
                const data = await res.json().catch(() => ({}));
                this.saving = false;
                if (! res.ok) { this.typeError = data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Could not save the page type.'); return; }
                window.location = @js(route('archive.show', $file->id)) + '?page=' + this.p.id;
            },
            get p() { return this.pages[this.i] },
            go(n) { if (n >= 0 && n < this.pages.length) { this.i = n; this.zoom = 1; this.rotate = 0; this.bright = 100; this.contrast = 100; } } }"
         @keydown.window.arrow-left="go(i - 1)" @keydown.window.arrow-right="go(i + 1)">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('archive.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100"><i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Doc-WARE</a>
                    <nav class="flex items-center gap-1" aria-label="File navigation">
                        @if ($prevFile)<a href="{{ route('archive.show', $prevFile) }}" title="Preview previous file" class="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="chevrons-left" class="h-3 w-3"></i> Previous File</a>@endif
                        @if ($nextFile)<a href="{{ route('archive.show', $nextFile) }}" title="Preview next file" class="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Next File <i data-lucide="chevrons-right" class="h-3 w-3"></i></a>@endif
                    </nav>
                </div>
                <p class="mt-1 font-mono text-xl font-black text-emerald-700 dark:text-emerald-400">{{ $file->file_number }}</p>
                <p class="text-base font-bold uppercase text-slate-900 dark:text-white">{{ $file->file_title ?? '—' }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                @foreach (['Plot No.' => $file->plot_number, 'District' => $file->district, 'LGA' => $file->lga, 'Land Use' => $file->land_use_type] as $k => $v)
                    <div><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $k }}</dt><dd class="font-semibold text-slate-800 dark:text-slate-100">{{ $v ?? '—' }}</dd></div>
                @endforeach
                <div class="col-span-2 sm:col-span-4"><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Location</dt><dd class="text-slate-700 dark:text-slate-200">{{ $file->location ?? '—' }}</dd></div>
                @if ($tracker)
                    <div class="col-span-2 sm:col-span-4"><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">KAMMA Tracking ID</dt>
                        <dd><a href="{{ route('tracking.show', $tracker) }}" class="rounded-md bg-blue-50 px-2 py-0.5 font-mono text-xs font-bold text-blue-700 hover:underline dark:bg-blue-500/10 dark:text-blue-300">{{ $tracker->tracking_id }}</a></dd></div>
                @endif
            </dl>
        </div>

        <div class="grid gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <div class="max-h-[78vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $pages->count() }} pages · {{ $groups->count() }} types</p>
                @foreach ($groups as $type => $group)
                    <details open class="mb-2 rounded-xl border border-slate-100 dark:border-slate-800">
                        <summary class="flex cursor-pointer items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-800 dark:bg-slate-800/60 dark:text-slate-100">
                            <span class="flex items-center gap-2"><i data-lucide="folder" class="h-4 w-4 {{ $type === 'Not typed yet' ? 'text-orange-500' : 'text-emerald-600' }}"></i>{{ $type }}</span>
                            <span class="rounded-full bg-white px-2 text-[10px] text-slate-500 dark:bg-slate-900">{{ $group->count() }}</span>
                        </summary>
                        <ul class="p-1">
                            @foreach ($group as $s)
                                @php $n = $indexOf[$s->id]; @endphp
                                <li><button type="button" @click="go({{ $n - 1 }})" :class="i === {{ $n - 1 }} ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs">
                                    <span>Page {{ $n }}{{ $s->pageTyping?->subtypeLabel() ? ' · '.$s->pageTyping->subtypeLabel() : '' }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $s->pageTyping?->page_code }}</span>
                                </button></li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </div>

            <div class="flex min-h-[70vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-white px-4 py-2 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="go(i - 1)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Previous page"><i data-lucide="chevron-left" class="h-4 w-4"></i></button>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="'Page ' + p.no + ' of ' + pages.length"></span>
                        <button type="button" @click="go(i + 1)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Next page"><i data-lucide="chevron-right" class="h-4 w-4"></i></button>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-text="(p.type || 'Not typed yet') + (p.sub ? ' · ' + p.sub : '') + (p.code ? ' (' + p.code + ')' : '')"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1">
                        <label class="mr-2 inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300" title="Turn on edit mode to show the editing actions">
                            <input type="checkbox" x-model="edit" class="rounded border-slate-300 text-blue-600"> Edit Mode
                        </label>
                        <template x-if="edit">
                            <span class="flex flex-wrap items-center gap-1 border-r border-slate-200 pr-2 dark:border-slate-700">
                                <button type="button" @click="rotate = (rotate + 270) % 360" title="Rotate left 90°" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></button>
                                <button type="button" @click="rotate = (rotate + 90) % 360" title="Rotate right 90°" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="rotate-cw" class="h-4 w-4"></i></button>
                                <form method="POST" :action="act('rotate')" class="inline" x-show="rotate !== 0 && ! p.pdf">@csrf<input type="hidden" name="degrees" :value="rotate">
                                    <button class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-700"><i data-lucide="save" class="h-3.5 w-3.5"></i>Save rotation</button></form>
                                <button type="button" @click="tune = ! tune" title="Brightness / contrast" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="sun-medium" class="h-4 w-4"></i></button>
                                <form method="POST" action="{{ route('ui.preview', 'docware-crop') }}" class="inline">@csrf<input type="hidden" name="scanning_id" :value="p.id">
                                    <button title="Crop to a selected region (preview)" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="crop" class="h-3.5 w-3.5"></i>Crop</button></form>
                                <form method="POST" :action="act('replace')" enctype="multipart/form-data" class="inline">@csrf
                                    <input x-ref="replace" type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.bmp,.tif,.tiff,.webp,.pdf" class="hidden"
                                           @change="if ($event.target.files.length && confirm('Replace page ' + p.no + ' with ' + $event.target.files[0].name + '? The old image is kept in the EDMS trash.')) $el.form.submit(); else $event.target.value = ''">
                                    <button type="button" @click="$refs.replace.click()" title="Replace with a new image" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="replace" class="h-3.5 w-3.5"></i>Replace</button></form>
                                <form method="POST" :action="act('delete')" class="inline" @submit="if (! confirm('Delete page ' + p.no + '? It moves to the EDMS trash with its page type.')) $event.preventDefault()">@csrf
                                    <button title="Delete this page" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i>Delete page</button></form>
                                <button type="button" @click.stop="openType()" title="Edit page classification (file type)" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="tag" class="h-3.5 w-3.5"></i>Edit Type</button>
                                <form method="POST" action="{{ route('ui.preview', 'docware-reassign') }}" class="inline">@csrf<input type="hidden" name="scanning_id" :value="p.id">
                                    <button title="Unlink these documents and attach them to a different file number (preview)" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="arrow-right-left" class="h-3.5 w-3.5"></i>Reassign</button></form>
                                {{-- Move to NR: off until KAMMA has its own registries (user 2026-11-25). --}}
                                <button type="button" disabled title="Opens when KAMMA has its own registries" class="inline-flex cursor-not-allowed items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-400 dark:text-slate-500"><i data-lucide="folder-input" class="h-3.5 w-3.5"></i>Move to NR</button>
                                <span x-show="p.type" class="ml-1 text-[11px] font-semibold text-slate-500" title="Quality control tools" x-text="'QC' + (p.qc && p.qc !== 'pending' ? ' (' + p.qc + ')' : '') + ':'"></span>
                                @foreach (['pass' => ['Pass', 'text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300'], 'fail' => ['Fail', 'text-rose-700 hover:bg-rose-50 dark:text-rose-300']] as $qc => [$lbl, $tone])
                                    <form method="POST" :action="act('qc')" class="inline" x-show="p.type">@csrf<input type="hidden" name="qc" value="{{ $qc }}">
                                        <button class="rounded-md px-2 py-1 text-xs font-semibold {{ $tone }}">{{ $lbl }}</button></form>
                                @endforeach
                            </span>
                        </template>
                        <button type="button" @click="zoom = Math.max(0.5, zoom - 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom out"><i data-lucide="zoom-out" class="h-4 w-4"></i></button>
                        <button type="button" @click="zoom = Math.min(4, zoom + 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom in"><i data-lucide="zoom-in" class="h-4 w-4"></i></button>
                        <button type="button" @click="rotate = (rotate + 90) % 360" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Rotate"><i data-lucide="rotate-cw" class="h-4 w-4"></i></button>
                        <a :href="p.url" target="_blank" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Open full size"><i data-lucide="maximize-2" class="h-4 w-4"></i></a>
                        <a :href="p.url" download class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Download page"><i data-lucide="download" class="h-4 w-4"></i></a>
                    </div>
                </div>
                <div x-show="edit && tune" x-cloak class="flex flex-wrap items-center gap-4 border-b border-slate-200 bg-white px-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-900">
                    <label class="flex items-center gap-2 text-slate-600 dark:text-slate-300">Brightness <input type="range" min="50" max="150" x-model="bright"><span class="w-10" x-text="bright + '%'"></span></label>
                    <label class="flex items-center gap-2 text-slate-600 dark:text-slate-300">Contrast <input type="range" min="50" max="150" x-model="contrast"><span class="w-10" x-text="contrast + '%'"></span></label>
                    <button type="button" @click="bright = 100; contrast = 100" class="rounded-md border border-slate-300 px-2 py-0.5 font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">Reset</button>
                    <span class="text-slate-400">On screen only; saving the enhanced page opens with the back end.</span>
                </div>
                <div class="flex flex-1 items-start justify-center overflow-auto p-4">
                    <template x-if="! p.pdf"><img :src="p.url" alt="Archived page" class="max-w-full shadow-lg transition-transform" :style="`transform: scale(${zoom}) rotate(${rotate}deg); transform-origin: top center; filter: brightness(${bright}%) contrast(${contrast}%)`"></template>
                    <template x-if="p.pdf"><iframe :src="p.url" class="h-[72vh] w-full rounded bg-white" title="PDF page"></iframe></template>
                </div>
            </div>
        </div>

        {{-- Edit Page Classification (KLAES viewer "Edit Type"): saved through Page Typing. --}}
        <template x-teleport="body">
            <div x-show="typeOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 pt-24 backdrop-blur-sm">
                <div @click.outside="typeOpen = false" class="w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div><h2 class="font-bold text-slate-900 dark:text-white">Edit Page Classification</h2><p class="text-xs text-slate-500" x-text="'Page ' + p.no + (p.code ? ' · ' + p.code : '')"></p></div>
                        <button type="button" @click="typeOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label for="et-type" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Page Type <span class="text-rose-500">*</span></label>
                            <select id="et-type" x-model="tf.page_type" @change="tf.page_subtype = ''" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Select page type</option>
                                <template x-for="t in Object.keys(types)" :key="t"><option :value="t" x-text="t" :selected="t === tf.page_type"></option></template>
                                <option value="Other">Other</option>
                            </select>
                            <input x-show="tf.page_type === 'Other'" x-model="tf.page_type_others" maxlength="200" placeholder="Specify the page type" class="mt-2 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                        <div x-show="tf.page_type">
                            <label for="et-sub" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Page Subtype</label>
                            <select id="et-sub" x-model="tf.page_subtype" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Select page subtype</option>
                                <template x-for="s in (types[tf.page_type] || [])" :key="s"><option :value="s" x-text="s" :selected="s === tf.page_subtype"></option></template>
                                <option value="Other">Other</option>
                            </select>
                            <input x-show="tf.page_subtype === 'Other'" x-model="tf.page_subtype_others" maxlength="200" placeholder="Specify the subtype" class="mt-2 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        </div>
                        <p x-show="typeError" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="typeError"></p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/40">
                        <button type="button" @click="typeOpen = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Cancel</button>
                        <button type="button" @click="saveType()" :disabled="saving || ! tf.page_type" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Save Classification</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
