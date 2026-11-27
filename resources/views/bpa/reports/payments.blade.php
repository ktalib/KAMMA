<x-app-layout title="Payments Report">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $card = 'break-inside-avoid rounded-xl border border-slate-200 bg-white p-5 shadow-sm print:shadow-none dark:border-slate-800 dark:bg-slate-900';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 print:px-2 print:py-1.5 print:text-xs dark:text-slate-300';
        $naira = fn ($n) => '₦'.number_format((float) $n, 2);
        $query = array_filter([
            'from' => $filters['from']?->format('Y-m-d'),
            'to' => $filters['to']?->format('Y-m-d'),
            'channel' => $filters['channel'],
            'search' => $filters['search'],
        ]);
        $period = match (true) {
            $filters['from'] && $filters['to'] => $filters['from']->format('j M Y').' to '.$filters['to']->format('j M Y'),
            (bool) $filters['from'] => 'From '.$filters['from']->format('j M Y'),
            (bool) $filters['to'] => 'Up to '.$filters['to']->format('j M Y'),
            default => 'All time',
        };
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Bills &amp; Payments</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">Payments Report</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">KAMMA payments recorded · {{ $period }}@if ($filters['channel']) · {{ $filters['channel'] }}@endif</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('bpa.payments-report.csv', $query) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Download CSV
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                    Print payment summary
                </button>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 print:hidden">
            <div>
                <label for="from" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Paid from</label>
                <input id="from" type="date" name="from" value="{{ $filters['from']?->format('Y-m-d') }}" class="{{ $field }}">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Paid to</label>
                <input id="to" type="date" name="to" value="{{ $filters['to']?->format('Y-m-d') }}" class="{{ $field }}">
            </div>
            <div>
                <label for="channel" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Channel</label>
                <select id="channel" name="channel" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach ($channels as $option)
                        <option value="{{ $option }}" @selected($filters['channel'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Receipt, bill, BP no., file no., developer…" class="{{ $field }} w-72">
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Apply</button>
            @if ($query)
                <a href="{{ route('bpa.payments-report') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        {{-- Summary (KLAES: Total Payments / Total Records / Payment Status) --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:grid-cols-4">
            <div class="{{ $card }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total payments</div>
                <div class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $naira($summary['total']) }}</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $period }}</div>
            </div>
            <div class="{{ $card }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Payments recorded</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($summary['count']) }}</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Receipts in this report</div>
            </div>
            <div class="{{ $card }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Bill status</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($summary['paid_bills']) }} / {{ number_format($summary['unpaid_bills']) }}</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Paid / unpaid bills, all time</div>
            </div>
            <div class="{{ $card }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Outstanding</div>
                <div class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $naira($summary['outstanding']) }}</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Unpaid bills, all time</div>
            </div>
        </div>

        {{-- Payment type and channel breakdown (KLAES: Payment Type Breakdown) --}}
        <div class="grid gap-4 lg:grid-cols-2 print:grid-cols-2">
            <div class="{{ $card }}">
                <h2 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Payment type breakdown</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($byType as $type => $t)
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $type }}</div>
                            <div class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">{{ $naira($t['amount']) }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t['count'] }} payment{{ $t['count'] === 1 ? '' : 's' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">No payments in this report.</p>
                    @endforelse
                </div>
            </div>
            <div class="{{ $card }}">
                <h2 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">By channel</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($byChannel as $channel => $c)
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $channel }}</div>
                            <div class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">{{ $naira($c['amount']) }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $c['count'] }} payment{{ $c['count'] === 1 ? '' : 's' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">No payments in this report.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Charts (KLAES: status distribution, payment trends, comparison) --}}
        <div class="grid gap-4 lg:grid-cols-3 print:grid-cols-3">
            <div class="{{ $card }}">
                <h2 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Bill status</h2>
                <div class="relative h-56"><canvas id="chart-status" aria-label="Paid and unpaid bills" role="img"></canvas></div>
            </div>
            <div class="{{ $card }} lg:col-span-2 print:col-span-2">
                <h2 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Payments by month (12 months to {{ $trend->last()['label'] }})</h2>
                <div class="relative h-56"><canvas id="chart-trend" aria-label="Amount paid per month" role="img"></canvas></div>
            </div>
        </div>

        {{-- The payments behind the figures --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm print:overflow-visible dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        <th class="{{ $th }}">Receipt No.</th>
                        <th class="{{ $th }}">Paid on</th>
                        <th class="{{ $th }} text-right">Amount (₦)</th>
                        <th class="{{ $th }}">Channel</th>
                        <th class="{{ $th }}">Bill No.</th>
                        <th class="{{ $th }}">BP / Application</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }}">Recorded by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($payments as $bill)
                        <tr>
                            <td class="{{ $td }}">{{ $payments->firstItem() + $loop->index }}</td>
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $bill->receipt_no }}</td>
                            <td class="{{ $td }}">{{ $bill->paid_on?->format('d M Y') }}</td>
                            <td class="{{ $td }} text-right font-medium text-slate-900 dark:text-slate-100">{{ number_format((float) $bill->amount_paid, 2) }}</td>
                            <td class="{{ $td }}">{{ $bill->payment_channel === 'Other' ? 'Other: '.$bill->payment_channel_other : $bill->payment_channel }}</td>
                            <td class="{{ $td }}">{{ $bill->bill_no }}</td>
                            <td class="{{ $td }}">
                                @if ($bill->application?->bp_file_no)
                                    <span class="block font-medium text-indigo-700 dark:text-indigo-300">{{ $bill->application->bp_file_no }}</span>
                                @endif
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $bill->application?->application_no }}</span>
                            </td>
                            <td class="{{ $td }}">{{ $bill->application?->file_number }}</td>
                            <td class="{{ $td }} min-w-[9rem] !whitespace-normal">{{ $bill->application?->developer_name }}</td>
                            <td class="{{ $td }}">{{ $bill->payment_recorded_by_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No payments match this report.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print:hidden">{{ $payments->links() }}</div>
        <p class="hidden text-xs text-slate-500 print:block">
            Printed {{ now()->format('j M Y, g:i a') }} by {{ Auth::user()->name }} · KLAES-METRO (KAMMA).
            @if ($payments->hasPages()) The list shows page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}; download the CSV for every payment. @endif
        </p>
    </div>

    <style>
        @media print { @page { size: A4 landscape; margin: 10mm; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        (function () {
            if (! window.Chart) return; // offline: the figures and tables above still tell the story
            const dark = document.documentElement.classList.contains('dark');
            const ink = dark ? '#cbd5e1' : '#475569';
            const grid = dark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.3)';
            Chart.defaults.color = ink;
            Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';

            const charts = [];
            charts.push(new Chart(document.getElementById('chart-status'), {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Unpaid'],
                    datasets: [{ data: @js([$summary['paid_bills'], $summary['unpaid_bills']]), backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }],
                },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            }));

            const trend = @js($trend);
            charts.push(new Chart(document.getElementById('chart-trend'), {
                type: 'bar',
                data: {
                    labels: trend.map((m) => m.label),
                    datasets: [{ label: 'Amount paid (₦)', data: trend.map((m) => m.amount), backgroundColor: '#6366f1', borderRadius: 4 }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (c) => '₦' + Number(c.raw).toLocaleString('en-NG', { minimumFractionDigits: 2 }) + ' · ' + trend[c.dataIndex].count + ' payment(s)' } },
                    },
                    scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: (v) => '₦' + Number(v).toLocaleString('en-NG') } } },
                },
            }));

            // Chart.js does not resize for the printed page on its own.
            window.addEventListener('beforeprint', () => charts.forEach((c) => c.resize()));
            window.addEventListener('afterprint', () => charts.forEach((c) => c.resize()));
        })();
    </script>
</x-app-layout>
