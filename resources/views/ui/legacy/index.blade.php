{{-- Legacy Systems: where the records of the systems before KLAES-METRO are reached from (fresh design; KLAES's own
     /legacy-systems link has no screen). Each card opens the KAMMA screen that reads that legacy data. --}}
<x-app-layout title="Legacy Systems">
    @php
        $systems = [
            ['Legacy file numbers', 'MLS, KANGIS and New KANGIS file numbers, searched together by the file selector.', 'folder-search', 'blue', route('records.show', 'file-records'), 'File Records'],
            ['KANGIS GIS records', 'Parcels captured in the older KANGIS GIS, as KLAES holds them.', 'map-pinned', 'teal', route('records.show', 'gis-records'), 'GIS Records'],
            ['Electronic registry', 'Files commissioned into the e-Registry with their plan approvals and current office.', 'book-open-check', 'sky', route('records.show', 'e-registry'), 'E-Registry'],
            ['Legacy bills', 'Bills raised before KLAES-METRO, captured with their payments.', 'history', 'slate', route('revm.legacy'), 'Bill Legacy'],
            ['Scanned archives', 'Scanned files and their typed pages in the digital library.', 'archive', 'violet', route('archive.index'), 'Doc-WARE'],
            ['Indexing history', 'Earlier indexing records and changes of KLAES land files.', 'history', 'amber', route('records.show', 'file-history'), 'File History View'],
        ];
        $tones = [
            'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300', 'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-300',
            'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300', 'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300', 'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        ];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="database" title="Legacy Systems" subtitle="Records from the systems before KLAES-METRO, read-only" tone="slate" section="Legacy Systems" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($systems as [$title, $text, $icon, $tone, $href, $cta])
                <a href="{{ $href }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tones[$tone] }}"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                    <p class="mt-4 font-bold text-slate-900 dark:text-white">{{ $title }}</p>
                    <p class="mt-1 flex-1 text-sm text-slate-500 dark:text-slate-400">{{ $text }}</p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 group-hover:underline dark:text-indigo-300">Open {{ $cta }} <i data-lucide="arrow-right" class="h-4 w-4"></i></span>
                </a>
            @endforeach
        </div>

        <x-ui.klaes-note tables="the same KLAES tables KLAES's own screens read" />
    </div>
</x-app-layout>
