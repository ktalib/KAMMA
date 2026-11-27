{{-- System Admin > KLAES Documentation, copied from KLAES system-admin/klaes-documentation (manuals, workflow and form
     documents, opened inline or downloaded). Lists the files in this app's DOC folder; PDFs and images preview on the page. --}}
<x-app-layout title="KLAES Documentation">
    @php
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $shown = $q === '' ? $docs : $docs->filter(fn ($d) => str_contains(mb_strtolower($d['name']), mb_strtolower($q)))->values();
        $icons = ['pdf' => 'file-text', 'docx' => 'file-type', 'doc' => 'file-type', 'png' => 'image', 'jpg' => 'image', 'jpeg' => 'image', 'xlsx' => 'sheet'];
        $previewable = ['pdf', 'png', 'jpg', 'jpeg'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="book-open" title="KLAES Documentation" subtitle="Workflow, forms and menu documents" tone="slate" section="System Admin" />

        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.panel title="Documents" :padded="false">
                <form method="GET" action="{{ route('admin.documentation') }}" class="border-b border-slate-100 p-3 dark:border-slate-800">
                    <label for="doc-q" class="sr-only">Search documents</label>
                    <input id="doc-q" name="q" value="{{ $q }}" placeholder="Search documents…" class="{{ $field }}">
                </form>
                @forelse ($shown as $i => $d)
                    <div @class(['flex items-center gap-3 px-4 py-3 text-sm', 'bg-indigo-50 dark:bg-indigo-500/10' => $current && $current['name'] === $d['name']])>
                        <span class="w-6 text-right text-xs text-slate-400">{{ $i + 1 }}</span>
                        <i data-lucide="{{ $icons[$d['ext']] ?? 'file' }}" class="h-4 w-4 shrink-0 text-slate-400"></i>
                        <a href="{{ route('admin.documentation', ['doc' => $d['name'], 'q' => $q ?: null]) }}" class="min-w-0 flex-1 truncate font-medium text-slate-800 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300" title="{{ $d['name'] }}">{{ $d['name'] }}</a>
                        <span class="shrink-0 text-xs text-slate-400">{{ number_format($d['size'] / 1024) }} KB</span>
                        <a href="{{ route('admin.documentation.file', ['name' => $d['name'], 'download' => 1]) }}" class="shrink-0 text-slate-400 hover:text-indigo-600" aria-label="Download {{ $d['name'] }}"><i data-lucide="download" class="h-4 w-4"></i></a>
                    </div>
                @empty
                    <x-ui.empty icon="book-open" title="No documents">{{ $q === '' ? 'Put the manuals and workflow documents in the DOC folder of the app.' : 'Nothing matches your search.' }}</x-ui.empty>
                @endforelse
            </x-ui.panel>

            <x-ui.panel :title="$current['name'] ?? 'Preview'" class="lg:col-span-2">
                @if (! $current)
                    <x-ui.empty icon="mouse-pointer-click" title="Pick a document">Choose a document on the left to read it here.</x-ui.empty>
                @elseif (in_array($current['ext'], $previewable, true))
                    @if ($current['ext'] === 'pdf')
                        <iframe src="{{ route('admin.documentation.file', ['name' => $current['name']]) }}" class="h-[75vh] w-full rounded-lg border border-slate-200 dark:border-slate-800" title="{{ $current['name'] }}"></iframe>
                    @else
                        <img src="{{ route('admin.documentation.file', ['name' => $current['name']]) }}" alt="{{ $current['name'] }}" class="mx-auto max-h-[75vh] rounded-lg">
                    @endif
                @else
                    <x-ui.empty icon="file-down" title="No preview for .{{ $current['ext'] }} files">
                        <a href="{{ route('admin.documentation.file', ['name' => $current['name'], 'download' => 1]) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Download {{ $current['name'] }}</a>
                    </x-ui.empty>
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
