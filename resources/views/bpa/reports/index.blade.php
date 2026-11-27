<x-app-layout title="Reports">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $card = 'break-inside-avoid rounded-xl border border-slate-200 bg-white p-5 shadow-sm print:shadow-none dark:border-slate-800 dark:bg-slate-900';
        $kpi = 'break-inside-avoid rounded-xl border border-slate-200 bg-white p-4 shadow-sm print:shadow-none dark:border-slate-800 dark:bg-slate-900';
        $label = 'text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $value = 'mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100';
        $section = 'mb-2 mt-2 text-xs font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400';
        $th = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'px-3 py-2 text-sm text-slate-700 dark:text-slate-300';
        $nf = fn ($n) => number_format((int) $n);
        $naira = fn ($n) => '₦'.number_format((float) $n, 2);
        $k = $kpis;
        $range = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">KAMMA</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">Reports</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Building Plan Approval, covering {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}. Desk figures are as of today.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <a href="{{ route('bpa.reports.register', $range) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Applications register (CSV)
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Print report</button>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 print:hidden">
            <div>
                <label for="from" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">From</label>
                <input id="from" type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="{{ $field }}">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">To</label>
                <input id="to" type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="{{ $field }}">
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Apply</button>
            <a href="{{ route('bpa.reports.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">This year</a>
        </form>

        <div>
            <p class="{{ $section }}">Applications</p>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 print:grid-cols-4">
                <div class="{{ $kpi }}"><div class="{{ $label }}">Received</div><div class="{{ $value }}">{{ $nf($k['applications']['received']) }}</div></div>
                <div class="{{ $kpi }}"><div class="{{ $label }}">Plots applied for</div><div class="{{ $value }}">{{ $nf($k['applications']['plots']) }}</div></div>
                <div class="{{ $kpi }}"><div class="{{ $label }}">Land-use flagged</div><div class="{{ $value }} !text-rose-700 dark:!text-rose-300">{{ $nf($k['applications']['flagged']) }}</div></div>
                <div class="{{ $kpi }}"><div class="{{ $label }}">Fees billed</div><div class="{{ $value }}">{{ $naira($k['applications']['billed']) }}</div></div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 print:grid-cols-2">
            <div>
                <p class="{{ $section }}">Payments and commissioning</p>
                <div class="grid gap-3 sm:grid-cols-3 print:grid-cols-3">
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Bills paid</div><div class="{{ $value }}">{{ $nf($k['payments']['paid']) }}</div></div>
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Fees paid</div><div class="{{ $value }} !text-emerald-700 dark:!text-emerald-300">{{ $naira($k['payments']['amount']) }}</div></div>
                    <div class="{{ $kpi }}"><div class="{{ $label }}">BP files</div><div class="{{ $value }}">{{ $nf($k['payments']['commissioned']) }}</div></div>
                </div>
            </div>
            <div>
                <p class="{{ $section }}">On the desks today</p>
                <div class="grid gap-3 grid-cols-2 sm:grid-cols-4 print:grid-cols-4">
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Director</div><div class="{{ $value }}">{{ $nf($k['desks']['director']) }}</div></div>
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Deputy Dir.</div><div class="{{ $value }}">{{ $nf($k['desks']['deputy']) }}</div></div>
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Returned</div><div class="{{ $value }}">{{ $nf($k['desks']['returned']) }}</div></div>
                    <div class="{{ $kpi }}"><div class="{{ $label }}">Zonal Office</div><div class="{{ $value }}">{{ $nf($k['desks']['zonal']) }}</div></div>
                </div>
            </div>
        </div>

        {{-- Each chart with the table of its figures (KLAES Survey Reports pattern). --}}
        <div class="grid gap-6 xl:grid-cols-2 print:grid-cols-1">
            <section class="{{ $card }}">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Applications received and BP files commissioned, by month</h2>
                <div class="relative mt-3 h-60"><canvas id="rp-monthly" role="img" aria-label="Applications received and BP files commissioned per month"></canvas></div>
                <table class="mt-4 w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-700"><tr><th class="{{ $th }}">Month</th><th class="{{ $th }} text-right">Received</th><th class="{{ $th }} text-right">BP files</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($monthly->filter(fn ($m) => $m['received'] || $m['commissioned']) as $m)
                            <tr><td class="{{ $td }}">{{ $m['label'] }}</td><td class="{{ $td }} text-right">{{ $nf($m['received']) }}</td><td class="{{ $td }} text-right">{{ $nf($m['commissioned']) }}</td></tr>
                        @endforeach
                        <tr class="font-semibold"><td class="{{ $td }}">Total</td><td class="{{ $td }} text-right">{{ $nf($monthly->sum('received')) }}</td><td class="{{ $td }} text-right">{{ $nf($monthly->sum('commissioned')) }}</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="{{ $card }}">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Applications by LGA</h2>
                <div class="relative mt-3 h-60"><canvas id="rp-lga" role="img" aria-label="Applications by LGA"></canvas></div>
                <table class="mt-4 w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-700"><tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">LGA</th><th class="{{ $th }} text-right">Applications</th><th class="{{ $th }} text-right">Flagged</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($byLga as $row)
                            <tr><td class="{{ $td }}">{{ $loop->iteration }}</td><td class="{{ $td }}">{{ $row['lga'] }}</td><td class="{{ $td }} text-right">{{ $nf($row['count']) }}</td><td class="{{ $td }} text-right">{{ $nf($row['flagged']) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="{{ $td }} text-center text-slate-500">No applications in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="{{ $card }}">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Proposed use and the land-use check</h2>
                <div class="relative mt-3 h-60"><canvas id="rp-use" role="img" aria-label="Proposed use by land-use check result"></canvas></div>
                <table class="mt-4 w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-700"><tr><th class="{{ $th }}">Proposed use</th><th class="{{ $th }} text-right">Match</th><th class="{{ $th }} text-right">Flagged</th><th class="{{ $th }} text-right">Not checked</th><th class="{{ $th }} text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($byUse as $row)
                            <tr><td class="{{ $td }}">{{ $row['use'] }}</td><td class="{{ $td }} text-right">{{ $nf($row['match']) }}</td><td class="{{ $td }} text-right">{{ $nf($row['mismatch']) }}</td><td class="{{ $td }} text-right">{{ $nf($row['unchecked']) }}</td><td class="{{ $td }} text-right font-semibold">{{ $nf($row['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="{{ $td }} text-center text-slate-500">No applications in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="{{ $card }}">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recommendations at each desk</h2>
                <div class="relative mt-3 h-60"><canvas id="rp-desks" role="img" aria-label="Recommended and not recommended at each desk"></canvas></div>
                <table class="mt-4 w-full text-sm">
                    <thead class="border-b border-slate-200 dark:border-slate-700"><tr><th class="{{ $th }}">Desk</th><th class="{{ $th }} text-right">Recommended</th><th class="{{ $th }} text-right">Not recommended</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($decisions as $row)
                            <tr><td class="{{ $td }}">{{ $row['desk'] }}</td><td class="{{ $td }} text-right">{{ $nf($row['recommended']) }}</td><td class="{{ $td }} text-right">{{ $nf($row['not_recommended']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>

        <p class="hidden text-xs text-slate-500 print:block">Printed {{ now()->format('j M Y, g:i a') }} by {{ Auth::user()->name }} · KLAES-METRO (KAMMA).</p>
    </div>

    <style>
        @media print { @page { size: A4 portrait; margin: 10mm; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        (function () {
            if (! window.Chart) return; // offline: the tables under each chart still give every figure
            const dark = document.documentElement.classList.contains('dark');
            const grid = dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.3)';
            Chart.defaults.color = dark ? '#cbd5e1' : '#475569';
            Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
            const axes = (stacked = false) => ({
                x: { stacked, grid: { display: false } },
                y: { stacked, beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } },
            });
            const charts = [];

            const monthly = @js($monthly->values());
            charts.push(new Chart(document.getElementById('rp-monthly'), {
                type: 'bar',
                data: { labels: monthly.map((m) => m.label), datasets: [
                    { label: 'Received', data: monthly.map((m) => m.received), backgroundColor: '#6366f1', borderRadius: 3 },
                    { label: 'BP files', data: monthly.map((m) => m.commissioned), backgroundColor: '#10b981', borderRadius: 3 },
                ] },
                options: { maintainAspectRatio: false, scales: axes(), plugins: { legend: { position: 'bottom' } } },
            }));

            const lga = @js($byLga->take(10)->values());
            charts.push(new Chart(document.getElementById('rp-lga'), {
                type: 'bar',
                data: { labels: lga.map((r) => r.lga), datasets: [{ label: 'Applications', data: lga.map((r) => r.count), backgroundColor: '#0ea5e9', borderRadius: 3 }] },
                options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
            }));

            const use = @js($byUse->values());
            charts.push(new Chart(document.getElementById('rp-use'), {
                type: 'bar',
                data: { labels: use.map((r) => r.use), datasets: [
                    { label: 'Match', data: use.map((r) => r.match), backgroundColor: '#10b981' },
                    { label: 'Flagged', data: use.map((r) => r.mismatch), backgroundColor: '#f43f5e' },
                    { label: 'Not checked', data: use.map((r) => r.unchecked), backgroundColor: '#94a3b8' },
                ] },
                options: { maintainAspectRatio: false, scales: axes(true), plugins: { legend: { position: 'bottom' } } },
            }));

            const desks = @js($decisions->values());
            charts.push(new Chart(document.getElementById('rp-desks'), {
                type: 'bar',
                data: { labels: desks.map((r) => r.desk), datasets: [
                    { label: 'Recommended', data: desks.map((r) => r.recommended), backgroundColor: '#10b981', borderRadius: 3 },
                    { label: 'Not recommended', data: desks.map((r) => r.not_recommended), backgroundColor: '#f43f5e', borderRadius: 3 },
                ] },
                options: { maintainAspectRatio: false, scales: axes(), plugins: { legend: { position: 'bottom' } } },
            }));

            // Chart.js does not resize for the printed page on its own.
            window.addEventListener('beforeprint', () => charts.forEach((c) => c.resize()));
            window.addEventListener('afterprint', () => charts.forEach((c) => c.resize()));
        })();
    </script>
</x-app-layout>
