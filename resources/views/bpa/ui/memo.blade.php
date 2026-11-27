{{-- KAMMA > KAMMA Memo: the internal memo on a BP file (to the MD through the Director), laid out like KLAES's Planning
     Recommendation / ST memos: heading block (To / Through / From / Date / Ref / Subject), the file particulars in KLAES
     order (Plot No., District, LGA, Location), the body and the recommendation, with a live print preview. Saves through
     Bpa\ZonalController::storeMemo (memo no. KMA-MEMO/YYYY/00001), then opens the printable memo. --}}
<x-app-layout title="KAMMA Memo">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="notebook-pen" title="KAMMA Memo" subtitle="Memo on a Building Plan Approval file" tone="indigo" section="KAMMA" />
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"><ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('bpa.memo.store') }}" class="grid gap-6 xl:grid-cols-2"
              x-data="{ file: null, to: @js(old('to', 'The Managing Director')), through: @js(old('through', 'Director, Architectural Department')), from: @js(old('from', auth()->user()->name ?? '')), date: @js(old('memo_date', now()->toDateString())), subject: @js(old('subject', '')), body: @js(old('body', '')), recommendation: @js(old('recommendation', '')) }"
              @file-picked="file = $event.detail; if (file && ! subject) subject = 'Building Plan Approval: ' + (file.file_number || '')">
            @csrf
            <x-ui.panel title="Memo details">
                <div class="space-y-4">
                    <x-ui.file-picker name="bpa_application_id" source="bp" label="BP file" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="m-to" class="{{ $label }}">To <span class="text-rose-500">*</span></label><input id="m-to" name="to" x-model="to" required class="{{ $field }}"></div>
                        <div><label for="m-thr" class="{{ $label }}">Through</label><input id="m-thr" name="through" x-model="through" class="{{ $field }}"></div>
                        <div><label for="m-from" class="{{ $label }}">From <span class="text-rose-500">*</span></label><input id="m-from" name="from" x-model="from" required class="{{ $field }}"></div>
                        <div><label for="m-date" class="{{ $label }}">Date <span class="text-rose-500">*</span></label><input id="m-date" type="date" name="memo_date" x-model="date" required class="{{ $field }}"></div>
                    </div>
                    <div><label for="m-sub" class="{{ $label }}">Subject <span class="text-rose-500">*</span></label><input id="m-sub" name="subject" x-model="subject" required class="{{ $field }}"></div>
                    <div><label for="m-body" class="{{ $label }}">Body <span class="text-rose-500">*</span></label><textarea id="m-body" name="body" x-model="body" rows="8" required class="{{ $field }}"></textarea></div>
                    <div><label for="m-rec" class="{{ $label }}">Recommendation</label><textarea id="m-rec" name="recommendation" x-model="recommendation" rows="3" class="{{ $field }}"></textarea></div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Print preview</button>
                        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save memo</button>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Preview" :padded="false">
                <article class="m-4 rounded-xl border border-slate-200 bg-white p-8 text-sm leading-relaxed text-slate-800 dark:border-slate-700 dark:bg-white dark:text-slate-900">
                    <header class="flex items-center gap-4 border-b-2 border-slate-800 pb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-14 w-14 object-contain">
                        <div><p class="font-black uppercase tracking-wide">Kano State Development Control Agency</p><p class="text-xs">Internal Memo</p></div>
                    </header>
                    <dl class="mt-4 grid grid-cols-[6rem_1fr] gap-y-1">
                        <dt class="font-semibold">To:</dt><dd x-text="to"></dd>
                        <dt class="font-semibold" x-show="through">Through:</dt><dd x-show="through" x-text="through"></dd>
                        <dt class="font-semibold">From:</dt><dd x-text="from"></dd>
                        <dt class="font-semibold">Date:</dt><dd x-text="date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : ''"></dd>
                        <dt class="font-semibold">Ref:</dt><dd x-text="file ? file.file_number : '—'"></dd>
                    </dl>
                    <p class="mt-4 border-y border-slate-300 py-2 font-bold uppercase" x-text="subject || 'Subject'"></p>
                    <table class="mt-4 w-full border border-slate-300 text-xs" x-show="file">
                        <tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1">Plot No.</th><th class="border border-slate-300 px-2 py-1">District</th><th class="border border-slate-300 px-2 py-1">LGA</th><th class="border border-slate-300 px-2 py-1">Location</th></tr>
                        <tr><td class="border border-slate-300 px-2 py-1" x-text="file?.plot_no || '—'"></td><td class="border border-slate-300 px-2 py-1" x-text="file?.district || '—'"></td><td class="border border-slate-300 px-2 py-1" x-text="file?.lga || '—'"></td><td class="border border-slate-300 px-2 py-1" x-text="file?.location || '—'"></td></tr>
                    </table>
                    <p class="mt-4 whitespace-pre-line" x-text="body || 'The body of the memo appears here.'"></p>
                    <template x-if="recommendation"><div class="mt-4"><p class="font-bold">Recommendation</p><p class="whitespace-pre-line" x-text="recommendation"></p></div></template>
                    <div class="mt-12 w-56 border-t border-slate-500 pt-1 text-xs" x-text="from"></div>
                </article>
            </x-ui.panel>
        </form>

        <x-ui.panel title="Saved memos" :padded="false">
            @if ($memos->isEmpty())
                <x-ui.empty icon="notebook-pen" title="No memo saved yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400"><tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Memo No.</th><th class="px-4 py-3">BP File No.</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">To</th><th class="px-4 py-3">From</th><th class="px-4 py-3">Date</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($memos as $m)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $m->memo_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $m->application?->bp_file_no }}</td>
                                    <td class="px-4 py-3">{{ $m->subject }}</td>
                                    <td class="px-4 py-3">{{ $m->to }}</td>
                                    <td class="px-4 py-3">{{ $m->from }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $m->memo_date?->format('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action :href="route('bpa.memo.show', $m)"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Open / print</x-row-action>
                                            <x-row-action :href="route('bpa.applications.show', $m->bpa_application_id)"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> Open application</x-row-action>
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>

        <x-ui.panel title="Recent BP files" subtitle="Open a file to read its history before writing" :padded="false">
            @if ($files->isEmpty())
                <x-ui.empty icon="folder" title="No BP file yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400"><tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">BP File No.</th><th class="px-4 py-3">Developer</th><th class="px-4 py-3">Plot No.</th><th class="px-4 py-3">District</th><th class="px-4 py-3">LGA</th><th class="px-4 py-3">Location</th><th class="px-4 py-3">Desk</th><th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($files as $a)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $a->bp_file_no }}</td>
                                    <td class="px-4 py-3">{{ $a->developer_name }}</td>
                                    <td class="px-4 py-3">{{ $a->plot_no ?: '—' }}</td><td class="px-4 py-3">{{ $a->district ?: '—' }}</td><td class="px-4 py-3">{{ $a->lga ?: '—' }}</td><td class="px-4 py-3">{{ $a->location ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ \App\Support\Bpa\BpaStage::status((string) $a->stage) }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900"><x-row-actions><x-row-action :href="route('bpa.applications.show', $a)"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> Open application</x-row-action></x-row-actions></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
