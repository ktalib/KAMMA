{{-- KAMMA > Conversion / ST / SLTR > Planning Recommendation, and Parcel/Title Management > Master KSI. Copied from KLAES
     physical_planning/conversion/planning_recommendation (heading + "Enter JSI Report Details", Total / Obtainable /
     Not Obtainable cards, the JSI reports table with KLAES's columns, three-dots row menu). JSI → KSI. --}}
<x-app-layout :title="$kind === 'master' ? 'Master KSI' : $k['label'].' · Planning Recommendation'">
    @php
        $th = 'whitespace-nowrap px-4 py-3';
        $tones = ['emerald' => 'emerald', 'blue' => 'blue', 'lime' => 'lime', 'violet' => 'violet'];
        $tone = $tones[$k['accent']] ?? 'emerald';
        $create = $kind === 'master' ? route('kamma.master-ksi.create') : route('kamma.pr.create', $kind);
        $statusTone = fn ($s) => match ($s) {
            'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'declined' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        };
        $m2 = fn ($v) => $v === null ? '—' : number_format((float) $v, 2).' m²';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head :icon="$kind === 'master' ? 'clipboard-list' : 'clipboard-check'" :tone="$tone"
                        :title="$kind === 'master' ? 'Master KSI' : $k['label']"
                        :subtitle="$kind === 'master' ? 'KAMMA Site Inspection reports for post-CofO applications (Sub-Division, Merger, Extension, Change of Purpose)' : 'KAMMA Site Inspection (KSI) Reports for '.$k['short'].' Files'"
                        :section="$kind === 'master' ? 'KAMMA › Parcel/Title Management' : 'KAMMA › '.$k['label']">
            <a href="{{ $create }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"><i data-lucide="clipboard-plus" class="h-4 w-4"></i> Enter KSI Report Details</a>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Total Reports" :value="number_format($stats['total'])" icon="clipboard-list" tone="slate" :href="url()->current()" />
            <x-ui.stat title="Obtainable" :value="number_format($stats['obtainable'])" icon="check-circle-2" tone="cyan" :href="url()->current().'?compliance=Obtainable'" />
            <x-ui.stat title="Not Obtainable" :value="number_format($stats['not_obtainable'])" icon="x-circle" tone="rose" :href="url()->current().'?compliance='.urlencode('Not Obtainable')" />
            <x-ui.stat title="Approved by MD KAMMA" :value="number_format($stats['approved'])" icon="stamp" tone="emerald" />
        </div>

        <x-ui.panel title="KAMMA Site Inspection Reports" :subtitle="$reports->total() ? 'Showing '.$reports->firstItem().'–'.$reports->lastItem().' of '.$reports->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    @if ($compliance)<input type="hidden" name="compliance" value="{{ $compliance }}">@endif
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input type="search" name="q" value="{{ $q }}" placeholder="File no., applicant, plot, TP / LKN no." class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    @if ($q !== '' || $compliance)<a href="{{ url()->current() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Reset</a>@endif
                </form>
            </x-slot:actions>
            @if ($reports->isEmpty())
                <x-ui.empty icon="clipboard-check" :title="$q !== '' || $compliance ? 'No KSI report matches' : 'No KSI reports yet'">Enter the Field Officer's KAMMA Site Inspection report on a shadow file.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th><th class="{{ $th }}">File Number</th><th class="{{ $th }}">Applicant</th>
                                @if ($kind === 'master')<th class="{{ $th }}">Application</th>@endif
                                <th class="{{ $th }}">Applied Land Use</th><th class="{{ $th }}">TPNo</th><th class="{{ $th }}">LKN No</th>
                                <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th>
                                <th class="{{ $th }}">Inspection Date</th><th class="{{ $th }}">Recommended Site Measurement</th><th class="{{ $th }}">Existing Site Measurement</th>
                                <th class="{{ $th }}">Compliance</th><th class="{{ $th }}">KSI Status</th><th class="{{ $th }}">Created By</th><th class="{{ $th }}">Date Created</th>
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($reports as $r)
                                @php $url = \App\Http\Controllers\Kamma\KsiController::showUrl($r); @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $reports->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $r->file_number }}</td>
                                    <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $r->applicant_name }}</td>
                                    @if ($kind === 'master')<td class="whitespace-nowrap px-4 py-3">{{ $r->post_cofo_type ?: '—' }}</td>@endif
                                    <td class="px-4 py-3">{{ $r->applied_land_use ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->tp_no ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->lpkn_no ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->district ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r->lga ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($r->location, 30) ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $r->inspection_date?->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $m2($r->recommended_site_area) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $m2($r->existing_site_area) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span @class(['rounded-full px-2 py-0.5 text-xs font-medium', 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300' => $r->compliance_status === 'Obtainable', 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' => $r->compliance_status !== 'Obtainable'])>{{ $r->compliance_status }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusTone($r->md_decision ?? $r->status) }}">{{ ucfirst($r->md_decision ?? $r->status) }}</span></td>
                                    <td class="px-4 py-3">{{ $r->created_by_name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r->created_at?->format('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            <x-row-action :href="$url"><x-slot:icon><i data-lucide="layout-dashboard" class="h-4 w-4"></i></x-slot:icon> View KSI Details</x-row-action>
                                            <x-row-action :href="$url.'?print=1'"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Print Planning Recommendation</x-row-action>
                                            <x-row-action :href="route('kamma.bills.create', ['report' => $r->id])"><x-slot:icon><i data-lucide="calculator" class="h-4 w-4"></i></x-slot:icon> Generate Betterment Bill</x-row-action>
                                            @if ($kind !== 'master')
                                                <x-row-action :href="route('kamma.memos.index', ['kind' => $kind, 'report' => $r->id])"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> KAMMA Memo</x-row-action>
                                            @endif
                                            @if ($r->file_indexing_id)
                                                <x-row-action :href="route('kamma.shadow-files.show', $r->file_indexing_id)"><x-slot:icon><i data-lucide="folder-open" class="h-4 w-4"></i></x-slot:icon> Open shadow file</x-row-action>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $reports->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
