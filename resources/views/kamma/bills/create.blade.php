{{-- KAMMA > Bills > Generate Bill. Betterment: KLAES PP's betterment calculation (KLAES BettermentBillController /
     pp_betterment_rates: base fee + (area − base area) × rate per extra m², on the KSI's RECOMMENDED area; extension not
     charged) with KAMMA's no-BPA penalty: building on the land and no Building Plan Approval → standard × 4.
     Processing fee: the Application Processing Fee on a shadow file (amount still to be given by KAMMA). --}}
<x-app-layout title="Generate Bill">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $card = 'space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $reportRows = $reports->map(fn ($r) => [
            'id' => $r->id, 'label' => $r->file_number.' · '.$r->applicant_name.' ('.strtoupper($r->kind).')', 'area' => (float) ($r->recommended_site_area ?: $r->existing_site_area),
            'building' => (bool) $r->building_exists, 'bpa' => (bool) $r->bpa_approved, 'use' => $r->applied_land_use,
            'property' => collect([$r->plot_number ? 'Plot '.$r->plot_number : null, $r->district, $r->lga, $r->location])->filter()->implode(' · '),
        ])->values();
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="calculator" title="Generate Bill" section="KAMMA › Bills & Payments" subtitle="Betterment from a KSI report, or the Application Processing Fee on a shadow file.">
            <a href="{{ route('kamma.bills.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="arrow-left" class="h-4 w-4"></i> Bills</a>
        </x-ui.page-head>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <p class="font-semibold">The bill was not generated.</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div x-data="{
                tab: @js(old('bill_type', 'betterment')),
                reports: @js($reportRows), rates: @js($rates), multiplier: {{ $multiplier }},
                reportId: @js((string) old('ksi_report_id', $report?->id ?? '')), category: @js(old('category', '')), bpa: null,
                get report() { return this.reports.find(r => String(r.id) === String(this.reportId)) || null },
                get rate() { return this.rates.find(r => r.category === this.category) || null },
                get building() { return this.report ? this.report.building : false },
                get bpaApproved() { return this.bpa === null ? (this.report ? this.report.bpa : false) : this.bpa === '1' },
                get standard() { if (! this.report || ! this.rate) return 0; return this.rate.base_fee + Math.max(0, this.report.area - this.rate.base_area) * this.rate.rate_per_extra_sqm },
                get penalty() { return this.building && ! this.bpaApproved },
                get amount() { return this.standard * (this.penalty ? this.multiplier : 1) },
                naira(v) { return '₦' + Number(v || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
                guess() { if (! this.report || this.category) return; const u = (this.report.use || '').toLowerCase(); const m = this.rates.find(r => u.includes(r.category.toLowerCase())); if (m) this.category = m.category },
             }" x-init="guess()" class="space-y-6">
            <div class="flex gap-1 border-b border-slate-200 dark:border-slate-800" role="tablist">
                @foreach (['betterment' => 'Betterment Bill', 'processing' => 'Application Processing Fee'] as $k => $v)
                    <button type="button" role="tab" @click="tab = '{{ $k }}'" :aria-selected="tab === '{{ $k }}'"
                            :class="tab === '{{ $k }}' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100'"
                            class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium">{{ $v }}</button>
                @endforeach
            </div>

            {{-- Betterment --}}
            <form x-show="tab === 'betterment'" method="POST" action="{{ route('kamma.bills.store') }}" class="grid gap-6 xl:grid-cols-3">
                @csrf
                <input type="hidden" name="bill_type" value="betterment">
                <section class="{{ $card }} xl:col-span-2">
                    <div>
                        <label for="b-report" class="{{ $label }}">KSI report <span class="text-rose-500">*</span></label>
                        <select id="b-report" name="ksi_report_id" x-model="reportId" @change="category = ''; bpa = null; guess()" required class="{{ $field }}">
                            <option value="">Select the KSI report…</option>
                            <template x-for="r in reports" :key="r.id"><option :value="r.id" x-text="r.label" :selected="String(r.id) === String(reportId)"></option></template>
                        </select>
                        <p x-show="! reports.length" class="mt-1 text-xs text-amber-700 dark:text-amber-300">No KSI report is waiting for a betterment bill. Enter the KSI report first.</p>
                    </div>
                    <template x-if="report">
                        <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 dark:bg-slate-800/60">
                            <div class="sm:col-span-3"><dt class="{{ $label }}">Property (Plot No., District, LGA, Location)</dt><dd class="text-slate-800 dark:text-slate-100" x-text="report.property || '—'"></dd></div>
                            <div><dt class="{{ $label }}">Recommended area</dt><dd class="font-semibold text-emerald-700 dark:text-emerald-400" x-text="report.area.toLocaleString('en-NG') + ' m²'"></dd></div>
                            <div><dt class="{{ $label }}">Applied land use</dt><dd class="text-slate-800 dark:text-slate-100" x-text="report.use || '—'"></dd></div>
                            <div><dt class="{{ $label }}">Building on the land (KSI)</dt><dd class="text-slate-800 dark:text-slate-100" x-text="report.building ? 'Yes' : 'No'"></dd></div>
                        </dl>
                    </template>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="b-cat" class="{{ $label }}">Betterment category <span class="text-rose-500">*</span></label>
                            <select id="b-cat" name="category" x-model="category" required class="{{ $field }}">
                                <option value="">Select…</option>
                                @foreach ($rates as $r)<option value="{{ $r['category'] }}">{{ $r['category'] }}</option>@endforeach
                            </select>
                        </div>
                        <div x-show="building" x-cloak>
                            <span class="{{ $label }}">Building Plan Approval (KADCA)</span>
                            <div class="flex gap-4 pt-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="bpa_approved" value="1" :checked="bpaApproved" @change="bpa = '1'" class="text-emerald-600"> Approved</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="bpa_approved" value="0" :checked="! bpaApproved" @change="bpa = '0'" class="text-emerald-600"> No BPA</label>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                        <table class="min-w-full text-sm">
                            <caption class="bg-slate-50 px-4 py-2 text-left text-xs font-semibold text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">Betterment rates {{ $ratesFromKlaes ? '(KLAES pp_betterment_rates, read-only)' : '(built-in copy of KLAES PP rates; KLAES not reachable)' }} · KAMMA\'s own rate table is still an open question</caption>
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th class="px-4 py-2">S/N</th><th class="px-4 py-2">Category</th><th class="px-4 py-2 text-right">Base fee</th><th class="px-4 py-2 text-right">Base area (m²)</th><th class="px-4 py-2 text-right">Rate per extra m²</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($rates as $r)
                                    <tr :class="category === @js($r['category']) ? 'bg-emerald-50 dark:bg-emerald-500/10' : ''"><td class="px-4 py-2 text-slate-500">{{ $loop->iteration }}</td><td class="px-4 py-2">{{ $r['category'] }}</td><td class="px-4 py-2 text-right tabular-nums">₦{{ number_format($r['base_fee'], 2) }}</td><td class="px-4 py-2 text-right tabular-nums">{{ number_format($r['base_area']) }}</td><td class="px-4 py-2 text-right tabular-nums">₦{{ number_format($r['rate_per_extra_sqm'], 2) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <aside class="{{ $card }}">
                    <h2 class="font-bold text-slate-900 dark:text-white">Calculation</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Base fee</dt><dd class="tabular-nums" x-text="rate ? naira(rate.base_fee) : '—'"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Extra area</dt><dd class="tabular-nums" x-text="report && rate ? Math.max(0, report.area - rate.base_area).toLocaleString('en-NG') + ' m² × ' + naira(rate.rate_per_extra_sqm) : '—'"></dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 dark:border-slate-800"><dt class="font-semibold text-slate-700 dark:text-slate-200">Standard fee</dt><dd class="font-semibold tabular-nums" x-text="naira(standard)"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">No-BPA penalty</dt><dd :class="penalty ? 'font-bold text-rose-600 dark:text-rose-400' : 'text-slate-400'" x-text="penalty ? '× ' + multiplier : 'None'"></dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-base dark:border-slate-700"><dt class="font-bold text-slate-900 dark:text-white">Betterment fee</dt><dd class="font-black tabular-nums text-emerald-700 dark:text-emerald-400" x-text="naira(amount)"></dd></div>
                    </dl>
                    <p x-show="penalty" x-cloak class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">A building stands on the land without Building Plan Approval: the standard fee is multiplied by {{ $multiplier }} (KAMMA Workflow v1.0).</p>
                    <button :disabled="! report || ! rate" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">Generate betterment bill</button>
                </aside>
            </form>

            {{-- Processing fee --}}
            <form x-show="tab === 'processing'" x-cloak method="POST" action="{{ route('kamma.bills.store') }}" class="{{ $card }} max-w-3xl">
                @csrf
                <input type="hidden" name="bill_type" value="processing">
                @if ($file)
                    <input type="hidden" name="file_indexing_id" value="{{ $file->id }}">
                    <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm dark:bg-emerald-500/10">
                        <p class="font-mono font-bold text-emerald-900 dark:text-emerald-200">{{ $file->file_number }}</p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">{{ collect([$file->plot_number ? 'Plot '.$file->plot_number : null, $file->district, $file->lga, $file->location])->filter()->implode(' · ') }}</p>
                    </div>
                @else
                    <x-ui.file-picker name="file_indexing_id" source="shadow" label="Shadow file" />
                @endif
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                    <span class="text-sm text-slate-600 dark:text-slate-300">Application Processing Fee</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white">{{ $processingFee === null ? 'Not set' : '₦'.number_format($processingFee, 2) }}</span>
                </div>
                @if ($processingFee === null)
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">KAMMA has not given the processing fee amount yet (open question). The bill opens once it is set.</p>
                @endif
                <button @disabled($processingFee === null) class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">Raise processing fee bill</button>
            </form>
        </div>
    </div>
</x-app-layout>
