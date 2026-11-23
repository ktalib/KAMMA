{{-- One KAMMA shadow file: the land file's details (Plot No., District, LGA, Location), where it is on the KAMMA desks,
     the desk decision, and everything KAMMA files on it (site visit photos, KSI reports, bills, the return to the PS). --}}
<x-app-layout :title="'Shadow file '.$f->file_number">
    @php
        $label = 'text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $value = 'mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-100';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $naira = fn ($v) => '₦'.number_format((float) $v, 2);
        $onDesk = in_array($f->stage, \App\Services\Kamma\DeskFlow::ROUTE, true);
        $route = ['ps', ...\App\Services\Kamma\DeskFlow::ROUTE, 'returned_to_ps'];
        $at = array_search($f->stage, $route, true);
    @endphp

    <div class="space-y-6" x-data="{ decide: null }">
        <x-ui.page-head icon="folder-open" :title="$f->file_number" :subtitle="$f->file_title ?: 'No title recorded'" section="KAMMA › Shadow File">
            <a href="{{ route('kamma.shadow-files.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Shadow files</a>
            @if ($f->tracker)
                <a href="{{ route('tracking.label', $f->tracker) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="qr-code" class="h-4 w-4"></i> QR label</a>
            @endif
        </x-ui.page-head>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Desk route --}}
        <x-ui.panel title="Where the file is" subtitle="PS → MD KAMMA → Department → Director → Deputy Director → Zonal Officer → Field Officer (KSI) → Planning Recommendation → back to the PS">
            <ol class="flex flex-wrap items-center gap-2">
                @foreach ($route as $i => $s)
                    <li class="flex items-center gap-2">
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-semibold ring-1',
                            'bg-emerald-600 text-white ring-emerald-600' => $i === $at,
                            'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30' => $at !== false && $i < $at,
                            'bg-white text-slate-500 ring-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700' => $at === false || $i > $at,
                        ])>{{ \App\Support\KammaDesk::label($s) }}</span>
                        @if (! $loop->last)<i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-300 dark:text-slate-600"></i>@endif
                    </li>
                @endforeach
            </ol>

            @if ($onDesk)
                <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <button type="button" @click="decide = 'recommended'" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        <i data-lucide="check-circle-2" class="h-4 w-4"></i> {{ $next === 'returned_to_ps' ? 'Approve and return to the PS' : 'Recommend to '.\App\Support\KammaDesk::label($next) }}
                    </button>
                    @if ($previous)
                        <button type="button" @click="decide = 'not_recommended'" class="inline-flex items-center gap-2 rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">
                            <i data-lucide="undo-2" class="h-4 w-4"></i> Not recommended (back to {{ \App\Support\KammaDesk::label($previous) }})
                        </button>
                    @endif
                    @if (in_array($f->stage, ['md', 'planning_recommendation'], true))
                        <form method="POST" action="{{ route('kamma.shadow-files.return', $f->id) }}" onsubmit="return confirm('Detach the land file and send it back to the Ministry of Land (PS)?')">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="send" class="h-4 w-4"></i> Return to the Ministry</button>
                        </form>
                    @endif
                </div>
            @elseif ($f->stage === 'returned_to_ps')
                <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                    The land file went back to the Ministry of Land (PS) on {{ $f->returned_to_ps_at?->format('d M Y, H:i') }}. The shadow file and its KAMMA documents stay archived in KLAES-METRO.
                </p>
            @endif

            {{-- Decision pop-up --}}
            <div x-show="decide" x-cloak class="fixed inset-0 z-40 flex items-start justify-center bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="decide = null">
                <form method="POST" action="{{ route('kamma.shadow-files.decide', $f->id) }}" @click.outside="decide = null" @click.stop class="w-full max-w-lg rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    @csrf
                    <input type="hidden" name="decision" :value="decide">
                    <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h2 class="font-bold text-slate-900 dark:text-white" x-text="decide === 'recommended' ? 'Recommend' : 'Not recommended'"></h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ \App\Support\KammaDesk::label($f->stage) }} · {{ $f->file_number }}</p>
                    </div>
                    <div class="p-6">
                        <label for="dc-remarks" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Remarks <span x-show="decide === 'not_recommended'" class="text-rose-500">* (reason)</span></label>
                        <textarea id="dc-remarks" name="remarks" rows="4" maxlength="1000" class="{{ $field }}" :required="decide === 'not_recommended'"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                        <button type="button" @click="decide = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save decision</button>
                    </div>
                </form>
            </div>
        </x-ui.panel>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Land file (from KLAES)" class="xl:col-span-2">
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="{{ $label }}">Plot No.</dt><dd class="{{ $value }}">{{ $f->plot_number ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">District</dt><dd class="{{ $value }}">{{ $f->district ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">LGA</dt><dd class="{{ $value }}">{{ $f->lga ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Location</dt><dd class="{{ $value }}">{{ $f->location ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Land use</dt><dd class="{{ $value }}">{{ $f->land_use_type ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Plot size</dt><dd class="{{ $value }}">{{ $f->plot_size ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Application</dt><dd class="{{ $value }}">{{ $kind['label'] ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Registry</dt><dd class="{{ $value }}">{{ $f->registry ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">KAMMA tracking ID</dt><dd class="{{ $value }} font-mono">@if ($f->tracker)<a href="{{ route('tracking.show', $f->tracker) }}" class="text-emerald-700 hover:underline dark:text-emerald-400">{{ $f->tracker->tracking_id }}</a>@else — @endif</dd></div>
                    <div><dt class="{{ $label }}">KLAES tracking ID</dt><dd class="{{ $value }} font-mono">{{ $f->klaes_tracking_id ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Received from the PS</dt><dd class="{{ $value }}">{{ $f->received_from_ps_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Commissioned by</dt><dd class="{{ $value }}">{{ $f->matched_by_name ?: '—' }}</dd></div>
                </dl>
                @if ($f->remarks)
                    <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300"><span class="font-semibold">PS minute:</span> {{ $f->remarks }}</p>
                @endif
            </x-ui.panel>

            <x-ui.panel title="Next steps">
                <div class="space-y-2 text-sm">
                    <a href="{{ route('kamma.site-visits.index', ['file' => $f->id]) }}" class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="camera" class="h-4 w-4 text-slate-500"></i><span class="flex-1">Initial site visit (geocoded photos)</span><span class="text-xs text-slate-400">{{ $f->photos->count() }}</span></a>
                    @if ($f->application_type && $f->application_type !== 'master')
                        <a href="{{ route('kamma.pr.create', ['kind' => $f->application_type, 'file' => $f->id]) }}" class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="clipboard-plus" class="h-4 w-4 text-slate-500"></i><span class="flex-1">Enter KSI report</span><span class="text-xs text-slate-400">{{ $f->ksiReports->count() }}</span></a>
                    @else
                        <a href="{{ route('kamma.master-ksi.create', ['file' => $f->id]) }}" class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="clipboard-plus" class="h-4 w-4 text-slate-500"></i><span class="flex-1">Enter Master KSI report</span><span class="text-xs text-slate-400">{{ $f->ksiReports->count() }}</span></a>
                    @endif
                    <a href="{{ route('kamma.bills.create', ['file' => $f->id]) }}" class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="calculator" class="h-4 w-4 text-slate-500"></i><span class="flex-1">Betterment bill</span></a>
                    <form method="POST" action="{{ route('kamma.shadow-files.decide', $f->id) }}">
                        @csrf
                        <input type="hidden" name="decision" value="processing_fee">
                        <button @disabled($processingFee === null) class="flex w-full items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 text-left hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-800 dark:hover:bg-slate-800">
                            <i data-lucide="receipt" class="h-4 w-4 text-slate-500"></i>
                            <span class="flex-1">Raise Application Processing Fee</span>
                            <span class="text-xs text-slate-400">{{ $processingFee === null ? 'amount not set' : $naira($processingFee) }}</span>
                        </button>
                    </form>
                    <a href="{{ route('dms.scanning.index', ['file' => $f->id]) }}" class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="scan-line" class="h-4 w-4 text-slate-500"></i><span class="flex-1">Scan / upload documents (EDMS)</span></a>
                </div>
                @if ($processingFee === null)
                    <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">The processing fee amount is an open question for KAMMA; the bill opens once it is set.</p>
                @endif
            </x-ui.panel>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.panel title="Desk history" :padded="false">
                @if ($f->deskMovements->isEmpty())
                    <x-ui.empty icon="route" title="No desk decisions yet" />
                @else
                    <ol class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($f->deskMovements as $m)
                            <li class="flex gap-3 px-6 py-3 text-sm">
                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $m->decision === 'not_recommended' ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300' }}"><i data-lucide="{{ $m->decision === 'not_recommended' ? 'undo-2' : 'arrow-right' }}" class="h-3.5 w-3.5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ \App\Support\KammaDesk::label($m->from_stage) }} → {{ \App\Support\KammaDesk::label($m->to_stage) }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $m->decision)) }} · {{ $m->acted_by_name }} · {{ $m->acted_at?->format('d M Y, H:i') }}</p>
                                    @if ($m->remarks)<p class="mt-1 text-slate-600 dark:text-slate-300">{{ $m->remarks }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.panel>

            <x-ui.panel title="Bills" :padded="false">
                @if ($f->bills->isEmpty())
                    <x-ui.empty icon="receipt" title="No bills yet">The processing fee and the betterment bill appear here.</x-ui.empty>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Bill No.</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($f->bills as $b)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $b->bill_no }}</td>
                                    <td class="px-4 py-3">{{ $b->bill_type === 'processing' ? 'Processing fee' : 'Betterment' }}@if ($b->penalty_multiplier > 1) <span class="ml-1 rounded bg-rose-50 px-1.5 text-[10px] font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">×{{ $b->penalty_multiplier }}</span>@endif</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $naira($b->amount) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $b->isPaid() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">{{ ucfirst($b->status) }}</span></td>
                                    <td class="px-4 py-2 text-right">
                                        <x-row-actions>
                                            <x-row-action :href="route('kamma.bills.print', $b->id)"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Print bill</x-row-action>
                                            @if (! $b->isPaid())
                                                <x-row-action :href="route('kamma.payments.index', ['q' => $b->bill_no])"><x-slot:icon><i data-lucide="banknote" class="h-4 w-4"></i></x-slot:icon> Record payment</x-row-action>
                                            @else
                                                <x-row-action :href="route('kamma.payments.receipt', $b->id)"><x-slot:icon><i data-lucide="file-check-2" class="h-4 w-4"></i></x-slot:icon> Receipt</x-row-action>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-ui.panel>

            <x-ui.panel title="KSI reports" :padded="false">
                @if ($f->ksiReports->isEmpty())
                    <x-ui.empty icon="clipboard-check" title="No KSI report yet">The Field Officer's KSI (KAMMA Site Inspection) report appears here.</x-ui.empty>
                @else
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($f->ksiReports as $r)
                            <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-800 dark:text-slate-100">KSI of {{ $r->inspection_date?->format('d M Y') ?? '—' }} · {{ $r->inspection_officer ?: '—' }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($r->status) }} · recommended area {{ $r->recommended_site_area ? number_format((float) $r->recommended_site_area, 2).' m²' : '—' }} · building on site: {{ $r->building_exists ? 'Yes' : 'No' }}</p>
                                </div>
                                <a href="{{ $r->kind === 'master' ? route('kamma.master-ksi.show', $r->id) : route('kamma.pr.show', [$r->kind, $r->id]) }}" class="text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Open</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.panel>

            <x-ui.panel title="Site visit photos" :padded="false">
                @if ($f->photos->isEmpty())
                    <x-ui.empty icon="camera" title="No geocoded photos yet">Photos from the field app carry their GPS position and time.</x-ui.empty>
                @else
                    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3">
                        @foreach ($f->photos->take(6) as $p)
                            <figure class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                                <img src="{{ route('kamma.site-visits.photo', $p->id) }}" alt="Site photo" class="h-28 w-full object-cover">
                                <figcaption class="px-2 py-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $p->latitude ? number_format((float) $p->latitude, 5).', '.number_format((float) $p->longitude, 5) : 'No GPS' }} · {{ $p->taken_at?->format('d M H:i') }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
