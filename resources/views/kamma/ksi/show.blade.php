{{-- One KSI report and its Planning Recommendation (KLAES physical_planning/*/print/recommendation, KAMMA wording):
     property (Plot No., District, LGA, Location), inspection findings, measurements, the MD KAMMA's decision and the bills.
     Prints on A4 (?print=1 opens the print dialog). --}}
<x-app-layout :title="'KSI · '.$r->file_number">
    @php
        $label = 'text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $value = 'mt-0.5 text-sm text-slate-800 dark:text-slate-100';
        $yn = fn ($v) => $v === null ? '—' : ($v ? 'Yes' : 'No');
        $m2 = fn ($v) => $v === null ? '—' : number_format((float) $v, 2).' m²';
        $naira = fn ($v) => '₦'.number_format((float) $v, 2);
        $list = $kind === 'master' ? route('kamma.master-ksi.index') : route('kamma.pr.index', $kind);
        $decision = $r->md_decision;
    @endphp

    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="clipboard-check" :title="'KSI Report · '.$r->file_number" :subtitle="($kind === 'master' ? 'Master KSI · '.($r->post_cofo_type ?: 'post-CofO') : $k['label']).' · inspected '.($r->inspection_date?->format('j F Y') ?? '—')" :section="'KAMMA › '.($kind === 'master' ? 'Parcel/Title Management' : $k['label'])">
                <a href="{{ $list }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Back</a>
                <a href="{{ route('kamma.bills.create', ['report' => $r->id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="calculator" class="h-4 w-4"></i> Betterment bill</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            </x-ui.page-head>
        </div>

        <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:border-0 print:p-0 print:shadow-none dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center gap-4 border-b border-slate-200 pb-5 dark:border-slate-700">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-16 object-contain">
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">KLAES-METRO · Kano State Metropolitan Area Management Agency</p>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">Planning Recommendation · KAMMA Site Inspection Report</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $kind === 'master' ? 'Post-CofO: '.($r->post_cofo_type ?: '—') : $k['label'] }}</p>
                </div>
                <span @class(['rounded-full px-3 py-1 text-xs font-bold uppercase', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $decision === 'approved', 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' => $decision === 'declined', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => ! $decision])>{{ $decision ? ucfirst($decision).' by MD KAMMA' : 'Awaiting MD KAMMA' }}</span>
            </header>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">File No.</p><p class="{{ $value }} font-mono font-semibold">{{ $r->file_number }}</p></div>
                <div><p class="{{ $label }}">Applicant</p><p class="{{ $value }}">{{ $r->applicant_name }}</p></div>
                <div class="sm:col-span-2"><p class="{{ $label }}">Applicant address</p><p class="{{ $value }}">{{ $r->applicant_address }}</p></div>
                <div><p class="{{ $label }}">Plot No.</p><p class="{{ $value }}">{{ $r->plot_number ?: '—' }}</p></div>
                <div><p class="{{ $label }}">District</p><p class="{{ $value }}">{{ $r->district ?: '—' }}</p></div>
                <div><p class="{{ $label }}">LGA</p><p class="{{ $value }}">{{ $r->lga ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Location</p><p class="{{ $value }}">{{ $r->location ?: '—' }}</p></div>
                <div><p class="{{ $label }}">LPKN No.</p><p class="{{ $value }}">{{ $r->lpkn_no ?: '—' }}</p></div>
                <div><p class="{{ $label }}">TP No.</p><p class="{{ $value }}">{{ $r->tp_no ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Scheme No.</p><p class="{{ $value }}">{{ $r->scheme_no ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Inspection date</p><p class="{{ $value }}">{{ $r->inspection_date?->format('d M Y') }}</p></div>
            </section>

            <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Boundaries and findings</h2>
            <section class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">North</p><p class="{{ $value }}">{{ $r->boundary_north }}</p></div>
                <div><p class="{{ $label }}">East</p><p class="{{ $value }}">{{ $r->boundary_east }}</p></div>
                <div><p class="{{ $label }}">South</p><p class="{{ $value }}">{{ $r->boundary_south }}</p></div>
                <div><p class="{{ $label }}">West</p><p class="{{ $value }}">{{ $r->boundary_west }}</p></div>
                <div><p class="{{ $label }}">Existing structure</p><p class="{{ $value }}">{{ $yn($r->existing_structure) }}{{ $r->existing_structure_dims ? ' · '.$r->existing_structure_dims : '' }}</p></div>
                <div><p class="{{ $label }}">Service</p><p class="{{ $value }}">{{ $r->service ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Detailed land use</p><p class="{{ $value }}">{{ $r->detailed_land_use ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Purpose</p><p class="{{ $value }}">{{ $r->purpose ?: '—' }}</p></div>
                <div class="sm:col-span-2"><p class="{{ $label }}">Road category</p><p class="{{ $value }}">{{ $r->road_category ?: '—' }}</p></div>
                <div><p class="{{ $label }}">Conformity</p><p class="{{ $value }}">{{ $yn($r->conformity) }}</p></div>
                <div><p class="{{ $label }}">Accessibility</p><p class="{{ $value }}">{{ $yn($r->accessibility) }}</p></div>
                <div class="sm:col-span-2"><p class="{{ $label }}">Recommended reservation</p><p class="{{ $value }}">{{ $r->reservation_required ? ($r->recommended_reservation ?: '—').($r->reservation_meters ? ' · '.$r->reservation_meters.' m' : '') : 'Not required' }}</p></div>
            </section>

            <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Measurements</h2>
            <section class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">Existing site</p><p class="{{ $value }}">{{ $m2($r->existing_site_area) }}@if ($r->existing_site_length) ({{ $r->existing_site_length }} × {{ $r->existing_site_width }} m)@endif</p></div>
                <div><p class="{{ $label }}">Recommended site</p><p class="{{ $value }} font-semibold text-emerald-700 dark:text-emerald-400">{{ $m2($r->recommended_site_area) }}@if ($r->recommended_site_length) ({{ $r->recommended_site_length }} × {{ $r->recommended_site_width }} m)@endif</p></div>
                <div><p class="{{ $label }}">Extension</p><p class="{{ $value }}">{{ $r->has_extension ? $m2($r->extension_site_area).' · '.$r->extension_dims : 'No' }}</p></div>
                @if ($kind === 'st')<div><p class="{{ $label }}">Units</p><p class="{{ $value }}">{{ $r->units_count }}</p></div>@endif
                <div><p class="{{ $label }}">Adequate size</p><p class="{{ $value }}">{{ $yn($r->adequate_size_requirement) }}</p></div>
                <div><p class="{{ $label }}">Utility traversing</p><p class="{{ $value }}">{{ $yn($r->land_traversing_utility) }}</p></div>
                <div><p class="{{ $label }}">Building on the land</p><p class="{{ $value }}">{{ $yn($r->building_exists) }}{{ $r->building_exists ? ($r->bpa_approved ? ' · BPA approved' : ' · no BPA (×4 penalty)') : '' }}</p></div>
            </section>

            <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Land use and recommendation</h2>
            <section class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="{{ $label }}">Prevailing land use</p><p class="{{ $value }}">{{ $r->prevailing_land_use }}</p></div>
                <div><p class="{{ $label }}">Applied land use</p><p class="{{ $value }}">{{ $r->applied_land_use }}</p></div>
                <div><p class="{{ $label }}">Compliance</p><p class="{{ $value }} font-semibold">{{ $r->compliance_status }}</p></div>
                <div><p class="{{ $label }}">EIA needed</p><p class="{{ $value }}">{{ $yn($r->need_for_eia) }}</p></div>
                <div class="sm:col-span-2 lg:col-span-4"><p class="{{ $label }}">Observations</p><p class="{{ $value }} whitespace-pre-line">{{ $r->additional_observations }}</p></div>
            </section>

            <footer class="mt-10 grid gap-8 border-t border-slate-200 pt-6 sm:grid-cols-2 dark:border-slate-700">
                <div>
                    <p class="{{ $label }}">Inspecting officer</p>
                    <p class="{{ $value }} font-semibold">{{ $r->inspection_officer }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $r->officer_rank }}</p>
                </div>
                <div>
                    <p class="{{ $label }}">MD KAMMA</p>
                    <p class="{{ $value }} font-semibold">{{ $r->md_decided_by_name ?: '________________________' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $decision ? ucfirst($decision).' on '.$r->md_decided_at?->format('d M Y') : 'Not yet decided' }}</p>
                    @if ($r->md_remarks)<p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $r->md_remarks }}</p>@endif
                </div>
            </footer>
        </article>

        <x-ui.panel title="Bills on this report" class="print:hidden" :padded="false">
            @if ($r->bills->isEmpty())
                <x-ui.empty icon="calculator" title="No betterment bill yet"><a href="{{ route('kamma.bills.create', ['report' => $r->id]) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Generate the betterment bill</a> from the recommended site measurement.</x-ui.empty>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($r->bills as $b)
                        <li class="flex items-center justify-between px-6 py-3 text-sm">
                            <span class="font-mono">{{ $b->bill_no }}</span>
                            <span>{{ $naira($b->amount) }}@if ($b->penalty_multiplier > 1) <span class="text-xs font-bold text-rose-600">(×{{ $b->penalty_multiplier }})</span>@endif</span>
                            <span class="text-xs">{{ ucfirst($b->status) }}</span>
                            <a href="{{ route('kamma.bills.print', $b->id) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Print</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>

    @if (request()->boolean('print'))
        @push('scripts')<script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>@endpush
    @endif
</x-app-layout>
