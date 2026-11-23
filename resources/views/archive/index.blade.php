{{-- Digital File Archive > File Digital Library – Doc-WARE, copied from KLAES's File Digital Archive
     (resources/views/filearchive): Total Page Typed Files / Recently Added / Total Pages / Storage Used, Search Archives,
     and the Archived Files grid (green band with file no. + pages, cover page, title, chips). --}}
<x-app-layout title="File Digital Library – Doc-WARE">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $size = function (int $b) {
            foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $u) { if ($b < 1024) return round($b, 1).' '.$u; $b /= 1024; }
            return round($b, 1).' PB';
        };
        $cards = [
            ['Total Page Typed Files', number_format($stats['files']), 'Completed page typed files', 'text-blue-700 dark:text-blue-300', 'border-blue-100 dark:border-blue-500/20'],
            ['Recently Added', number_format($stats['recent']), 'Added in the last 30 days', 'text-emerald-700 dark:text-emerald-300', 'border-emerald-100 dark:border-emerald-500/20'],
            ['Total Pages', number_format($stats['pages']), 'Digitally classified pages', 'text-amber-700 dark:text-amber-300', 'border-amber-100 dark:border-amber-500/20'],
            ['Storage Used', $size($stats['bytes']), 'Of archived documents', 'text-violet-700 dark:text-violet-300', 'border-violet-100 dark:border-violet-500/20'],
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-600/25"><i data-lucide="library" class="h-6 w-6"></i></span>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">File Digital Archive</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Access and manage digitally archived KAMMA files</p>
                </div>
            </div>
            <a href="{{ route('dms.pagetyping.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="type" class="h-4 w-4"></i> Page Typing</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as [$title, $value, $hint, $tone, $border])
                <div class="rounded-2xl border bg-white p-5 shadow-sm dark:bg-slate-900 {{ $border }}">
                    <p class="text-sm font-semibold {{ $tone }}">{{ $title }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $value }}</p>
                    <p class="mt-1 text-xs {{ $tone }} opacity-80">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Search Archives --}}
        <form method="GET" class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="font-bold text-slate-900 dark:text-white">Search Archives</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Find archived files by file number, title, plot, district or LGA</p>
            </div>
            <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label for="a-q" class="{{ $label }}">Search Term</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="a-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Enter search term…" class="{{ $field }} pl-9">
                    </div>
                </div>
                <div>
                    <label for="a-lu" class="{{ $label }}">Land Use</label>
                    <select id="a-lu" name="land_use" class="{{ $field }}"><option value="">All Land Uses</option>@foreach ($landUses as $v)<option @selected($filters['land_use'] === $v)>{{ $v }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="a-lga" class="{{ $label }}">LGA</label>
                    <select id="a-lga" name="lga" class="{{ $field }}"><option value="">All LGAs</option>@foreach ($lgas as $v)<option @selected($filters['lga'] === $v)>{{ $v }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="a-pt" class="{{ $label }}">Category (page type)</label>
                    <select id="a-pt" name="page_type" class="{{ $field }}"><option value="">All Categories</option>@foreach ($pageTypes as $v)<option @selected($filters['page_type'] === $v)>{{ $v }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="a-y" class="{{ $label }}">Year Indexed</label>
                    <select id="a-y" name="year" class="{{ $field }}"><option value="">All Years</option>@foreach ($years as $v)<option @selected((string) $filters['year'] === (string) $v)>{{ $v }}</option>@endforeach</select>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-3 dark:border-slate-800">
                <div class="flex items-center gap-2 text-sm">
                    <label for="a-sort" class="text-slate-500">Sort</label>
                    <select id="a-sort" name="sort" onchange="this.form.submit()" class="rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="recent" @selected($filters['sort'] === 'recent')>Newest first</option>
                        <option value="file" @selected($filters['sort'] === 'file')>File number</option>
                        <option value="pages" @selected($filters['sort'] === 'pages')>Most pages</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('archive.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Clear</a>
                    <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
                </div>
            </div>
        </form>

        {{-- Archived files --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Archived Files</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Page typed digital files ({{ number_format($files->total()) }} files)</p>
                </div>
            </div>
            <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @forelse ($files as $f)
                    @php $cover = $dms->cover($f); @endphp
                    <a href="{{ route('archive.show', $f) }}" class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-2 bg-gradient-to-r from-emerald-600 to-emerald-700 px-3 py-2 text-white">
                            <span class="truncate font-mono text-xs font-bold">{{ $f->file_number }}</span>
                            <span class="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold">{{ $f->pages_count }} pages</span>
                        </div>
                        <div class="flex h-52 items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-800">
                            @if ($cover && ! str_contains((string) $cover->mime, 'pdf'))
                                <img src="{{ route('dms.scans.image', $cover) }}" loading="lazy" alt="Cover of {{ $f->file_number }}" class="h-full w-full object-contain transition group-hover:scale-105">
                            @else
                                <i data-lucide="file-text" class="h-12 w-12 text-slate-300 dark:text-slate-600"></i>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-3">
                            <p class="line-clamp-2 text-sm font-bold uppercase text-slate-900 dark:text-white">{{ $f->file_title ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ collect([$f->plot_number ? 'Plot '.$f->plot_number : null, $f->district, $f->lga])->filter()->join(' · ') ?: '—' }}</p>
                            <div class="mt-auto flex flex-wrap gap-1.5">
                                @if ($f->untyped_count)
                                    <span class="rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-bold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300">{{ $f->untyped_count }} new to type</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Archived</span>
                                @endif
                                @if ($f->land_use_type)<span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">{{ $f->land_use_type }}</span>@endif
                                @if ($f->district)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $f->district }}</span>@endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-16 text-center">
                        <i data-lucide="library" class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">No archived file yet{{ array_filter(\Illuminate\Support\Arr::except($filters, 'sort')) ? ' for this search' : '' }}.</p>
                        <p class="mt-1 text-xs text-slate-400">Files appear here once their pages are typed.</p>
                    </div>
                @endforelse
            </div>
            @if ($files->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-3 text-sm text-slate-500 dark:border-slate-800">
                    <span>Showing {{ $files->firstItem() }} to {{ $files->lastItem() }} of {{ number_format($files->total()) }} results</span>
                    {{ $files->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
