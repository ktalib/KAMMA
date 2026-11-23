{{-- Digital File Archive > Virtual File/Folder System (Folio), after KLAES's VFS: Registry > File on the left; the open file's
     folders (one per document type) and their pages on the right. --}}
<x-app-layout title="Virtual File/Folder System (Folio)">
    <div class="space-y-6" x-data="{ filter: '' }">
        <x-ui.page-head icon="folder-tree" title="Virtual File/Folder System (Folio)" subtitle="Browse archived files as folders: registry, file, document type, pages" tone="amber" section="Digital File Archive" />
        @if ($error)<x-ui.klaes-note :error="$error" />@endif

        <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
            <x-ui.panel title="Registries" :padded="false">
                <div class="border-b border-slate-100 p-3 dark:border-slate-800">
                    <input type="search" x-model="filter" placeholder="Filter files…" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div class="max-h-[32rem] overflow-y-auto p-2 text-sm">
                    @forelse ($tree as $registry => $files)
                        <details open class="group">
                            <summary class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i data-lucide="folder" class="h-4 w-4 text-amber-500"></i> {{ $registry }} <span class="ml-auto text-xs font-normal text-slate-400">{{ $files->count() }}</span>
                            </summary>
                            <ul class="ml-5 border-l border-slate-200 pl-2 dark:border-slate-700">
                                @foreach ($files as $f)
                                    <li x-show="! filter || @js(mb_strtolower($f->file_number.' '.$f->file_title)).includes(filter.toLowerCase())">
                                        <a href="{{ route('folio.index', ['file' => $f->id]) }}" @class(['flex items-center gap-2 rounded-md px-2 py-1 hover:bg-slate-50 dark:hover:bg-slate-800', 'bg-amber-50 font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => $file?->id === $f->id, 'text-slate-600 dark:text-slate-300' => $file?->id !== $f->id])>
                                            <i data-lucide="file-archive" class="h-3.5 w-3.5 shrink-0"></i><span class="truncate font-mono text-xs">{{ $f->file_number }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @empty
                        <x-ui.empty icon="folder-x" title="No archived files">Files appear once their pages are typed.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.panel>

            <x-ui.panel :title="$file ? $file->file_number : 'Open a file'" :subtitle="$file ? $file->file_title : 'Pick a file on the left to see its folders'">
                @if ($file)
                    <x-slot:actions>
                        <a href="{{ route('archive.show', $file->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="book-open" class="h-4 w-4"></i> Open in Doc-WARE</a>
                    </x-slot:actions>
                    <dl class="mb-5 grid gap-3 text-sm sm:grid-cols-4">
                        @foreach (['Plot No.' => $file->plot_number, 'District' => $file->district, 'LGA' => $file->lga, 'Location' => $file->location] as $k => $v)
                            <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $k }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $v ?: '—' }}</dd></div>
                        @endforeach
                    </dl>
                    <div class="space-y-5">
                        @foreach ($folders as $type => $pages)
                            <div>
                                <h3 class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100"><i data-lucide="folder-open" class="h-4 w-4 text-amber-500"></i> {{ $type }} <span class="text-xs font-normal text-slate-400">{{ $pages->count() }} page(s)</span></h3>
                                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                                    @foreach ($pages as $p)
                                        <a href="{{ $p->scanning ? route('dms.scans.image', $p->scanning) : '#' }}" target="_blank" class="group overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                                            <div class="aspect-[3/4] bg-slate-100 dark:bg-slate-800">
                                                @if ($p->scanning)<img src="{{ route('dms.scans.image', $p->scanning) }}" alt="Page {{ $p->page_number }}" loading="lazy" class="h-full w-full object-contain transition group-hover:scale-105">@endif
                                            </div>
                                            <p class="truncate px-2 py-1 text-[11px] text-slate-600 dark:text-slate-300">p.{{ $p->page_number }} · {{ $p->page_subtype ?: $p->page_type }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty icon="folder-search" title="No file open" />
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
