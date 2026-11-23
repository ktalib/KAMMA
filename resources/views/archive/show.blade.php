{{-- Doc-WARE file viewer: the file's details (Plot No., District, LGA, Location), its pages grouped by page type on
     the left and the page on the right, with previous / next, zoom and rotate. --}}
<x-app-layout title="Doc-WARE · {{ $file->file_number }}">
    @php
        $list = $pages->values()->map(fn ($s, $i) => [
            'id' => $s->id, 'no' => $i + 1, 'url' => route('dms.scans.image', $s), 'pdf' => str_contains((string) $s->mime, 'pdf'),
            'type' => $s->pageTyping?->typeLabel(), 'sub' => $s->pageTyping?->subtypeLabel(), 'code' => $s->pageTyping?->page_code,
        ]);
        $indexOf = $list->pluck('no', 'id');
    @endphp

    <div class="space-y-4" x-data="{ pages: @js($list), i: 0, zoom: 1, rotate: 0,
            get p() { return this.pages[this.i] },
            go(n) { if (n >= 0 && n < this.pages.length) { this.i = n; this.zoom = 1; this.rotate = 0; } } }"
         @keydown.window.arrow-left="go(i - 1)" @keydown.window.arrow-right="go(i + 1)">

        <div class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-0">
                <a href="{{ route('archive.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100"><i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Doc-WARE</a>
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
                    <div class="flex items-center gap-1">
                        <button type="button" @click="zoom = Math.max(0.5, zoom - 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom out"><i data-lucide="zoom-out" class="h-4 w-4"></i></button>
                        <button type="button" @click="zoom = Math.min(4, zoom + 0.25)" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Zoom in"><i data-lucide="zoom-in" class="h-4 w-4"></i></button>
                        <button type="button" @click="rotate = (rotate + 90) % 360" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Rotate"><i data-lucide="rotate-cw" class="h-4 w-4"></i></button>
                        <a :href="p.url" target="_blank" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Open full size"><i data-lucide="maximize-2" class="h-4 w-4"></i></a>
                        <a :href="p.url" download class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Download page"><i data-lucide="download" class="h-4 w-4"></i></a>
                    </div>
                </div>
                <div class="flex flex-1 items-start justify-center overflow-auto p-4">
                    <template x-if="! p.pdf"><img :src="p.url" alt="Archived page" class="max-w-full shadow-lg transition-transform" :style="`transform: scale(${zoom}) rotate(${rotate}deg); transform-origin: top center`"></template>
                    <template x-if="p.pdf"><iframe :src="p.url" class="h-[72vh] w-full rounded bg-white" title="PDF page"></iframe></template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
