{{-- A filed Site Inspection Report: site visit with the geocoded photos (map link per photo), Site Analysis Report 1.0 / 2.0,
     measurements, the officer's recommendation and the Head of Zonal Office's review. Printable. --}}
<x-app-layout title="Site Inspection Report">
    @php
        $r = $inspection;
        $a = $r->application;
        $dt = 'text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $dd = 'mt-0.5 text-sm text-slate-800 dark:text-slate-200';
        $show = fn ($v) => filled($v) ? $v : '—';
        $m = fn ($v, $unit = 'm') => $v !== null ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.').' '.$unit : '—';
        $review = ['approved' => ['Approved: on to Final Conveyance', 'emerald'], 'returned' => ['Returned to the officer', 'rose'], 'referred' => ['Referred back to the Deputy Director (Arch.)', 'rose']][$r->review_decision] ?? ['Awaiting the Head of Zonal Office', 'amber'];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('bpa.inspection') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"><i data-lucide="arrow-left" class="h-4 w-4"></i> Inspection</a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <x-ui.page-head icon="clipboard-list" :title="'Site Inspection Report · '.$a->bp_file_no" :subtitle="$a->developer_name.' · land file '.$a->file_number" tone="violet" section="KAMMA › Zonal Officer" />

        <div @class(['rounded-xl border px-4 py-3 text-sm font-semibold',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' => $review[1] === 'emerald',
            'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300' => $review[1] === 'rose',
            'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300' => $review[1] === 'amber'])>
            {{ $review[0] }}
            @if ($r->reviewed_at)<span class="font-normal"> · {{ $r->reviewed_by_name }}, {{ $r->reviewed_at->format('d M Y H:i') }}</span>@endif
            @if ($r->review_remarks)<p class="mt-1 whitespace-pre-line font-normal">{{ $r->review_remarks }}</p>@endif
        </div>

        <x-ui.panel title="Initial site visit">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="{{ $dt }}">Instruction No.</dt><dd class="{{ $dd }} font-mono">{{ $r->assignment?->instruction_no ?? '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Date of visit</dt><dd class="{{ $dd }}">{{ $r->visit_date?->format('d M Y') }} {{ $r->visit_time }}</dd></div>
                <div><dt class="{{ $dt }}">Person met on site</dt><dd class="{{ $dd }}">{{ $show($r->person_met) }}</dd></div>
                <div><dt class="{{ $dt }}">Photos</dt><dd class="{{ $dd }}">{{ $r->photos->count() }}</dd></div>
            </dl>
            @if ($r->visit_notes)<p class="mt-4 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $r->visit_notes }}</p>@endif
            <ul class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($r->photos as $p)
                    <li class="overflow-hidden rounded-lg border border-slate-200 break-inside-avoid dark:border-slate-700">
                        <a href="{{ route('bpa.inspection.photo', $p) }}" target="_blank" rel="noopener"><img src="{{ route('bpa.inspection.photo', $p) }}" alt="Site photo {{ $loop->iteration }}" class="h-40 w-full object-cover"></a>
                        <div class="space-y-0.5 p-2 text-[11px] text-slate-600 dark:text-slate-300">
                            @if ($p->latitude !== null)
                                <p><span class="font-semibold">Lat/Lng:</span> <a href="https://www.google.com/maps?q={{ $p->latitude }},{{ $p->longitude }}" target="_blank" rel="noopener" class="text-violet-700 hover:underline dark:text-violet-300">{{ number_format($p->latitude, 6) }}, {{ number_format($p->longitude, 6) }}</a></p>
                            @else
                                <p class="text-amber-700 dark:text-amber-300">No position recorded</p>
                            @endif
                            <p><span class="font-semibold">Taken:</span> {{ $p->taken_at?->timezone('Africa/Lagos')->format('d M Y H:i') ?? '—' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.panel>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.panel title="1.0 Location details">
                <dl class="grid gap-4 sm:grid-cols-2">
                    @foreach (['Plot No' => $r->plot_no, 'District' => $r->district, 'Cadastral Zone' => $r->cadastral_zone, 'Land use' => $r->land_use, 'Purpose clause' => $r->purpose_clause, 'Plot size' => $r->plot_size, 'Density' => $r->density, 'Street Name' => $r->street_name] as $k => $v)
                        <div><dt class="{{ $dt }}">{{ $k }}</dt><dd class="{{ $dd }}">{{ $show($v) }}</dd></div>
                    @endforeach
                </dl>
            </x-ui.panel>
            <x-ui.panel title="2.0 Site details">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="{{ $dt }}">2.1a Nature of plot</dt><dd class="{{ $dd }}">{{ $r->nature_of_plot === 'Other' ? 'Other: '.$r->nature_of_plot_other : $r->nature_of_plot }}</dd></div>
                    <div><dt class="{{ $dt }}">2.1b Estimated slope</dt><dd class="{{ $dd }}">{{ $r->slope }}</dd></div>
                    <div><dt class="{{ $dt }}">2.2 Vacancy of plot</dt><dd class="{{ $dd }}">{{ $r->vacancy }}</dd></div>
                    <div><dt class="{{ $dt }}">2.4 Level of service</dt><dd class="{{ $dd }}">{{ $r->level_of_service }}{{ $r->level_of_service_details ? ': '.$r->level_of_service_details : '' }}</dd></div>
                    @if ($r->vacancy === 'Not vacant')
                        <div class="sm:col-span-2"><dt class="{{ $dt }}">2.3a Development on site</dt><dd class="{{ $dd }} whitespace-pre-line">{{ $show($r->existing_development) }}</dd></div>
                        <div><dt class="{{ $dt }}">2.3b Approved earlier?</dt><dd class="{{ $dd }}">{{ $show($r->approved_earlier) }}</dd></div>
                        <div><dt class="{{ $dt }}">2.3c Conformity with submission</dt><dd class="{{ $dd }}">{{ $show($r->conformity) }}</dd></div>
                        @if ($r->non_conformity)<div class="sm:col-span-2"><dt class="{{ $dt }}">2.3d Nature of non-conformity</dt><dd class="{{ $dd }} whitespace-pre-line">{{ $r->non_conformity }}</dd></div>@endif
                    @endif
                </dl>
            </x-ui.panel>
        </div>

        <x-ui.panel title="Site assessment">
            <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach (['Front setback' => $m($r->front_setback), 'Rear setback' => $m($r->rear_setback), 'Left side setback' => $m($r->left_setback), 'Right side setback' => $m($r->right_setback), 'Plot frontage' => $m($r->plot_frontage), 'Plot depth' => $m($r->plot_depth), 'Building footprint' => $m($r->building_footprint, 'm²'), 'Building height' => $m($r->building_height), 'Floors' => $show($r->floors)] as $k => $v)
                    <div><dt class="{{ $dt }}">{{ $k }}</dt><dd class="{{ $dd }} tabular-nums">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Recommendation</dt><dd class="{{ $dd }} font-semibold">{{ $r->recommendation }}</dd></div>
                <div><dt class="{{ $dt }}">Inspecting officer</dt><dd class="{{ $dd }}">{{ $r->officer_name }}{{ $r->officer_rank ? ', '.$r->officer_rank : '' }} · {{ $r->filed_at?->format('d M Y') }}</dd></div>
                @if ($r->remarks)<div class="sm:col-span-2"><dt class="{{ $dt }}">Remarks</dt><dd class="{{ $dd }} whitespace-pre-line">{{ $r->remarks }}</dd></div>@endif
            </div>
        </x-ui.panel>
    </div>
</x-app-layout>
