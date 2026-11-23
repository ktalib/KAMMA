{{-- A Chart.js chart (cdnjs). $chart = ['type' => bar|hbar|line|doughnut, 'labels' => [...], 'series' => [['label' => .., 'data' => [..]]], 'money' => bool].
     Colours follow a fixed categorical order with light and dark steps; charts redraw when the theme changes.
     The figures behind every chart are also in a table or card on the same page (no colour-only reading). --}}
@props(['chart', 'label' => 'Chart', 'height' => 'h-64'])

@php
    $hasData = collect($chart['series'] ?? [])->flatMap(fn ($s) => $s['data'] ?? [])->sum() > 0;
@endphp

<div {{ $attributes->merge(['class' => "relative {$height}"]) }}>
    @if ($hasData)
        <canvas data-chart="{{ json_encode($chart) }}" role="img" aria-label="{{ $label }}"></canvas>
    @else
        <div class="flex h-full flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500">
            <i data-lucide="bar-chart-3" class="mb-2 h-6 w-6"></i>
            No figures to chart yet
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            (() => {
                const PALETTE = {
                    light: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'],
                    dark: ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
                };
                const charts = [];
                const naira = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 });
                const plain = new Intl.NumberFormat('en-NG');

                function draw() {
                    if (! window.Chart) return; // offline: the cards and tables still carry the figures
                    charts.splice(0).forEach((c) => c.destroy());
                    const dark = document.documentElement.classList.contains('dark');
                    const colors = dark ? PALETTE.dark : PALETTE.light;
                    const surface = dark ? '#0f172a' : '#ffffff';
                    Chart.defaults.color = dark ? '#94a3b8' : '#64748b';
                    Chart.defaults.borderColor = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
                    Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';

                    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
                        const cfg = JSON.parse(canvas.dataset.chart);
                        const fmt = (v) => cfg.money ? naira.format(v) : plain.format(v);
                        const round = cfg.type === 'doughnut';
                        const horizontal = cfg.type === 'hbar';
                        const many = (cfg.series || []).length > 1;
                        const datasets = (cfg.series || []).map((s, i) => {
                            const base = { label: s.label, data: s.data };
                            if (round) {
                                return { ...base, backgroundColor: s.data.map((_, j) => colors[j % colors.length]), borderColor: surface, borderWidth: 2, hoverOffset: 4 };
                            }
                            if (cfg.type === 'line') {
                                return { ...base, borderColor: colors[i], backgroundColor: colors[i] + '22', fill: ! many, tension: .3, borderWidth: 2, pointRadius: 3, pointHoverRadius: 5 };
                            }
                            return { ...base, backgroundColor: colors[i], borderRadius: 4, borderSkipped: 'start', maxBarThickness: 36, borderColor: surface, borderWidth: { top: 0, right: 2, bottom: 0, left: 2 } };
                        });
                        charts.push(new Chart(canvas, {
                            type: round ? 'doughnut' : (cfg.type === 'line' ? 'line' : 'bar'),
                            data: { labels: cfg.labels, datasets },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                indexAxis: horizontal ? 'y' : 'x',
                                cutout: round ? '62%' : undefined,
                                interaction: { mode: round ? 'nearest' : 'index', intersect: round },
                                plugins: {
                                    legend: { display: round || many, position: round ? 'right' : 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                                    tooltip: { callbacks: { label: (c) => ` ${c.dataset.label && many ? c.dataset.label + ': ' : (round ? c.label + ': ' : '')}${fmt(c.parsed.y ?? c.parsed.x ?? c.parsed)}` } },
                                },
                                scales: round ? {} : {
                                    x: { grid: { display: horizontal }, ticks: horizontal ? { callback: (v) => fmt(v) } : { maxRotation: 0, autoSkip: true } },
                                    y: { beginAtZero: true, grid: { display: ! horizontal }, ticks: horizontal ? {} : { precision: 0, callback: (v) => fmt(v) } },
                                },
                            },
                        }));
                    });
                }

                document.addEventListener('DOMContentLoaded', draw);
                new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                window.addEventListener('beforeprint', () => charts.forEach((c) => c.resize()));
            })();
        </script>
    @endpush
@endonce
