{{-- KAMMA > Shadow File Commissioning (KAMMA Workflow v1.0 phase 1). New screen in KLAES style: figure cards, the
     "Commission Shadow File" pop-up (land file from the KLAES selector, application type, remarks) and the register of
     shadow files with S/N, Plot No., District, LGA, Location, desk and tracking ID; row actions in a three-dots menu. --}}
<x-app-layout title="Shadow File Commissioning">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-4 py-3';
        $deskTone = fn ($s) => match ($s) {
            'returned_to_ps' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'planning_recommendation' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'md' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
            default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        };
        $total = array_sum($counts);
    @endphp

    <div class="space-y-6" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }}, type: @js(old('application_type', 'conversion')) }">
        <x-ui.page-head icon="folder-input" title="Shadow File Commissioning" section="KAMMA"
                        subtitle="Land files the PS sends from the Cadastral Department for Planning Recommendation. Each gets a KAMMA shadow file, keeps its land file number, and goes to the MD KAMMA.">
            <button type="button" @click.stop="open = true" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                <i data-lucide="plus-circle" class="h-4 w-4"></i> Commission Shadow File
            </button>
        </x-ui.page-head>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <p class="font-semibold">The shadow file was not commissioned.</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Shadow files" :value="number_format($total)" hint="Received from the PS" icon="files" tone="blue" :href="route('kamma.shadow-files.index')" />
            <x-ui.stat title="Received today" :value="number_format($today)" :hint="now()->format('l, j F Y')" icon="calendar-check" tone="emerald" />
            <x-ui.stat title="On KAMMA desks" :value="number_format($total - ($counts['returned_to_ps'] ?? 0))" hint="MD KAMMA → Field Officer → Planning Recommendation" icon="route" tone="amber" />
            <x-ui.stat title="Back to the PS" :value="number_format($counts['returned_to_ps'] ?? 0)" hint="Land file returned to the Ministry" icon="undo-2" tone="slate" :href="route('kamma.returns.index', ['tab' => 'returned'])" />
        </div>

        {{-- Desk filter chips --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('kamma.shadow-files.index', array_filter(['q' => $q])) }}" @class(['rounded-full px-3 py-1.5 text-xs font-semibold ring-1', 'bg-emerald-600 text-white ring-emerald-600' => ! $stage, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' => $stage])>All desks ({{ $total }})</a>
            @foreach (\App\Support\KammaDesk::FLOW as $key => $name)
                @continue(in_array($key, ['ps', 'ksi'], true))
                <a href="{{ route('kamma.shadow-files.index', array_filter(['stage' => $key, 'q' => $q])) }}" @class(['rounded-full px-3 py-1.5 text-xs font-semibold ring-1', 'bg-emerald-600 text-white ring-emerald-600' => $stage === $key, 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' => $stage !== $key])>{{ $name }} ({{ $counts[$key] ?? 0 }})</a>
            @endforeach
        </div>

        <x-ui.panel title="Shadow files" :subtitle="$files->total() ? 'Showing '.$files->firstItem().'–'.$files->lastItem().' of '.$files->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="relative">
                    @if ($stage)<input type="hidden" name="stage" value="{{ $stage }}">@endif
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="File no., title, plot, district, LGA…" class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </x-slot:actions>
            @if ($files->isEmpty())
                <x-ui.empty icon="folder-input" :title="$q !== '' || $stage ? 'No shadow file matches' : 'No shadow files yet'">Commission the first one when the PS sends a land file for Planning Recommendation.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th><th class="{{ $th }}">File No.</th><th class="{{ $th }}">File Title</th><th class="{{ $th }}">Application</th>
                                <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th>
                                <th class="{{ $th }}">Desk</th><th class="{{ $th }}">Tracking ID</th><th class="{{ $th }}">Received</th>
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($files as $f)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 tabular-nums text-slate-500 dark:text-slate-400">{{ $files->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $f->file_number }}</td>
                                    <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $f->file_title ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">{{ $kinds[$f->application_type]['short'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->district ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $f->lga ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($f->location, 40) ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deskTone($f->stage) }}">{{ \App\Support\KammaDesk::label($f->stage) }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $f->tracker?->tracking_id ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $f->received_from_ps_at?->format('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action :href="route('kamma.shadow-files.show', $f->id)">
                                                <x-slot:icon><i data-lucide="folder-open" class="h-4 w-4"></i></x-slot:icon> Open shadow file
                                            </x-row-action>
                                            <x-row-action :href="route('kamma.site-visits.index', ['file' => $f->id])">
                                                <x-slot:icon><i data-lucide="camera" class="h-4 w-4"></i></x-slot:icon> Site visit photos
                                            </x-row-action>
                                            @if ($f->tracker)
                                                <x-row-action :href="route('tracking.show', $f->tracker)">
                                                    <x-slot:icon><i data-lucide="map-pin" class="h-4 w-4"></i></x-slot:icon> Track the file
                                                </x-row-action>
                                            @endif
                                            <x-row-action :href="route('dms.scanning.index', ['file' => $f->id])">
                                                <x-slot:icon><i data-lucide="scan-line" class="h-4 w-4"></i></x-slot:icon> Scan / upload pages
                                            </x-row-action>
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

        {{-- Commission Shadow File pop-up --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="open = false">
            <form method="POST" action="{{ route('kamma.shadow-files.store') }}" @click.outside="open = false" @click.stop
                  class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Commission Shadow File</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">The land file keeps its number; KAMMA's tracking ID (KMA-TRK-…) is issued and the file goes to the MD KAMMA.</p>
                    </div>
                    <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>
                <div class="space-y-5 p-6">
                    <x-ui.file-picker name="klaes_file_indexing_id" label="Land file sent by the PS (KLAES)" hint="Search KLAES File Indexing by file number or title. The file number is never typed." />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="sf-type" class="{{ $label }}">Application <span class="text-rose-500">*</span></label>
                            <select id="sf-type" name="application_type" x-model="type" required class="{{ $field }}">
                                @foreach ($kinds as $key => $k)
                                    <option value="{{ $key }}">{{ $k['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="type === 'master'" x-cloak>
                            <label for="sf-post" class="{{ $label }}">Post-CofO application <span class="text-rose-500">*</span></label>
                            <select id="sf-post" name="post_cofo_type" :required="type === 'master'" :disabled="type !== 'master'" class="{{ $field }}">
                                <option value="">Select…</option>
                                @foreach ($postCofo as $p)
                                    <option @selected(old('post_cofo_type') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="sf-remarks" class="{{ $label }}">PS minute / remarks</label>
                        <textarea id="sf-remarks" name="remarks" rows="3" maxlength="1000" class="{{ $field }}" placeholder="e.g. For Planning Recommendation, minute ref. …">{{ old('remarks') }}</textarea>
                    </div>
                    <div class="grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 dark:bg-slate-800/60">
                        <div><span class="{{ $label }}">Received by</span>{{ auth()->user()->name }}</div>
                        <div><span class="{{ $label }}">Date</span>{{ now()->format('d M Y') }}</div>
                        <div><span class="{{ $label }}">First desk</span>MD KAMMA</div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" @click="open = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Commission and send to MD KAMMA</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
