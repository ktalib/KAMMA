{{-- DMS > File SerialNo Grouping > SerialNo Grouping (KLAES Grouping Analytics): shadow files by batch and serial number,
     and the files still waiting for a batch / serial. --}}
<x-app-layout title="SerialNo Grouping">
    @php $n = fn ($v) => number_format((int) $v); @endphp

    <div class="space-y-6" x-data="{ picked: [] }">
        <x-ui.page-head icon="layers" title="SerialNo Grouping" subtitle="Grouping analytics: shadow files by batch and serial number" tone="violet" section="DMS › Indexing › File SerialNo Grouping">
            <a href="{{ route('dms.labels') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="tag" class="h-4 w-4"></i> Print Files Label</a>
        </x-ui.page-head>

        @if ($error)<x-ui.klaes-note :error="$error" />@endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Shadow files" :value="$n($files->count())" hint="Indexed in KAMMA" icon="files" tone="blue" />
            <x-ui.stat title="Batches" :value="$n($batches->where('batch', '<>', '')->count())" hint="Distinct batch numbers" icon="boxes" tone="violet" />
            <x-ui.stat title="Grouped" :value="$n($files->count() - $ungrouped->count())" hint="With batch and serial no." icon="check-check" tone="emerald" />
            <x-ui.stat title="Awaiting grouping" :value="$n($ungrouped->count())" hint="Missing a batch or serial no." icon="hourglass" tone="amber" />
        </div>

        <x-ui.panel title="Batches" subtitle="Each KLAES batch the shadow files came from" :padded="false">
            @if ($batches->isEmpty())
                <x-ui.empty icon="boxes" title="No shadow files yet">Files appear here after the File Indexing Assistant commissions them from KLAES.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Batch No.</th><th class="px-4 py-3 text-right">Files</th><th class="px-4 py-3">Serial range</th><th class="px-4 py-3">Registry</th><th class="px-4 py-3">Last added</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($batches as $b)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $b['batch'] !== '' ? $b['batch'] : 'No batch' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ $n($b['files']) }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $b['serials']->isNotEmpty() ? $b['serials']->first().' – '.$b['serials']->last() : '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $b['registry'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $b['last']?->format('d M Y') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <x-row-actions>
                                            <x-row-action :href="route('dms.labels', ['batch' => $b['batch']])">
                                                <x-slot:icon><i data-lucide="tag" class="h-4 w-4"></i></x-slot:icon>
                                                Print labels
                                            </x-row-action>
                                            <x-row-action :href="route('dms.signin', ['batch' => $b['batch']])">
                                                <x-slot:icon><i data-lucide="clipboard-signature" class="h-4 w-4"></i></x-slot:icon>
                                                Sign In &amp; Out sheet
                                            </x-row-action>
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>

        <x-ui.panel title="Awaiting grouping" subtitle="Tick files and give them a batch and serial numbers" :padded="false">
            @if ($ungrouped->isEmpty())
                <x-ui.empty icon="check-check" title="Every shadow file is grouped" />
            @else
                <form method="POST" action="{{ route('ui.preview', 'serialno-grouping') }}">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr><th class="w-10 px-4 py-3"></th><th class="px-4 py-3">S/N</th><th class="px-4 py-3">File No.</th><th class="px-4 py-3">File Title</th><th class="px-4 py-3">Plot No.</th><th class="px-4 py-3">District</th><th class="px-4 py-3">LGA</th><th class="px-4 py-3">Location</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($ungrouped as $f)
                                    <tr>
                                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $f->id }}" x-model="picked" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"></td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $f->file_number }}</td>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $f->file_title ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->plot_number ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->district ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->lga ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($f->location, 40) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Sticky footer bar (KLAES batch-action pattern) --}}
                    <div x-show="picked.length" x-cloak class="sticky bottom-4 mx-4 mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl dark:bg-slate-800">
                        <span class="font-semibold"><span x-text="picked.length"></span> selected</span>
                        <input name="batch_no" required placeholder="Batch No." class="w-36 rounded-lg border-0 bg-white/10 text-sm text-white placeholder-white/50 focus:ring-2 focus:ring-indigo-400">
                        <input name="first_serial" type="number" min="1" required placeholder="First serial" class="w-32 rounded-lg border-0 bg-white/10 text-sm text-white placeholder-white/50 focus:ring-2 focus:ring-indigo-400">
                        <button class="ml-auto rounded-lg bg-indigo-500 px-4 py-2 font-semibold hover:bg-indigo-400">Group selected</button>
                    </div>
                </form>
            @endif
        </x-ui.panel>
        <x-ui.preview-note what="Grouping" />
    </div>
</x-app-layout>
