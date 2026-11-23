{{-- KAMMA > File Return to the Ministry (KAMMA Workflow v1.0 phase 4): land files whose Planning Recommendation the MD KAMMA
     approved, ready to be detached from their shadow file and sent back to the PS, and the ones already returned. --}}
<x-app-layout title="File Return to the Ministry">
    @php $th = 'whitespace-nowrap px-4 py-3'; @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="send" title="File Return to the Ministry" section="KAMMA"
                        subtitle="The land file is detached from its shadow file and goes back to the Ministry of Land (PS) for the next stage. The shadow file and every KAMMA document stay archived in KLAES-METRO." />

        <x-tabs :tabs="['ready' => ['Ready to return', $counts['ready']], 'returned' => ['Returned', $counts['returned']]]" :active="$tab" route="kamma.returns.index" />

        <x-ui.panel :padded="false">
            @if ($files->isEmpty())
                <x-ui.empty icon="send" :title="$tab === 'ready' ? 'No land file is waiting to go back' : 'No land file returned yet'">
                    {{ $tab === 'ready' ? 'Files appear here once the MD KAMMA approves their Planning Recommendation.' : 'Returned land files are listed here with the date they left KAMMA.' }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">File Title</th><th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th><th class="{{ $th }}">Tracking ID</th><th class="{{ $th }}">{{ $tab === 'ready' ? 'Received' : 'Returned' }}</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($files as $f)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500">{{ $files->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $f->file_number }}</td>
                                    <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $f->file_title ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $f->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $f->district ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $f->lga ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($f->location, 40) ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $f->tracker?->tracking_id ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ ($tab === 'ready' ? $f->received_from_ps_at : $f->returned_to_ps_at)?->format('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action :href="route('kamma.shadow-files.show', $f->id)"><x-slot:icon><i data-lucide="folder-open" class="h-4 w-4"></i></x-slot:icon> Open shadow file</x-row-action>
                                            @if ($f->tracker)
                                                <x-row-action :href="route('tracking.show', $f->tracker)"><x-slot:icon><i data-lucide="map-pin" class="h-4 w-4"></i></x-slot:icon> Track the file</x-row-action>
                                            @endif
                                            <x-row-action :href="route('archive.show', $f->id)"><x-slot:icon><i data-lucide="archive" class="h-4 w-4"></i></x-slot:icon> Archived documents</x-row-action>
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $files->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
