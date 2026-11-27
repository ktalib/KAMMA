{{-- Main Dashboard (first sidebar item, also KAMMA > Dashboard). Real figures from App\Services\MainDashboard, in the KAMMA
     card style; replaced the Breeze placeholder's made-up numbers (user 2026-11-25: "it's not accurate"). --}}
<x-app-layout title="Dashboard">
    @php
        $fmt = fn ($n) => $n === null ? '—' : number_format($n);
        $naira = fn ($n) => $n === null ? '—' : '₦'.number_format($n, 2);
        $monthHint = fn ($c) => $c['total'] === null ? 'Not set up yet' : $fmt($c['month']).' this month';
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $tones = [
            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
            'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
            'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        ];
        $sections = [
            ['KAMMA', 'Planning recommendation, KSI and desk work', $kamma],
            ['Development Permit', 'Building plan approval, from application to conveyance', $permit],
            ['Files & Records', 'DMS and file tracking', $records],
        ];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="layout-dashboard" :title="$greeting.', '.Auth::user()->name" :subtitle="($mdView ?? false) ? 'Managing Director\'s view · '.now()->format('l, j F Y') : 'KLAES-METRO overview · '.now()->format('l, j F Y')" tone="emerald">
            <a href="{{ route('bpa.applications.index', ['source' => 'portal']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="globe" class="h-4 w-4"></i> Portal requests</a>
            <a href="{{ route('tracking.search') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="search" class="h-4 w-4"></i> Quick Search</a>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($headline as $c)
                <x-ui.stat :title="$c['label']" :value="$fmt($c['total'])" :hint="$monthHint($c)" :icon="$c['icon']" :tone="$c['tone']" :href="$c['url']" />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                @foreach ($sections as [$title, $subtitle, $cards])
                    <x-ui.panel :title="$title" :subtitle="$subtitle">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($cards as $c)
                                <a href="{{ $c['url'] }}" class="group flex flex-col rounded-xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:hover:border-emerald-500/50">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $c['label'] }}</span>
                                        <i data-lucide="{{ $c['icon'] }}" class="h-4 w-4 text-slate-400 group-hover:text-emerald-600 dark:text-slate-500"></i>
                                    </span>
                                    <span class="mt-2 text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ $fmt($c['total']) }}</span>
                                    <span class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">{{ $monthHint($c) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-ui.panel>
                @endforeach
            </div>

            <div class="space-y-6">
                <x-ui.panel title="Bills & Payments" subtitle="KAMMA and Development Permit bills">
                    <div class="space-y-3">
                        @foreach ($money as $m)
                            <a href="{{ $m['url'] }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/40 dark:border-slate-700 dark:hover:bg-emerald-500/5">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $m['label'] }}</span>
                                    <span class="block text-xs text-slate-400 dark:text-slate-500">{{ $m['hint'] }}</span>
                                </span>
                                <span class="text-lg font-black tabular-nums text-slate-900 dark:text-white">{{ $naira($m['value']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Recent activity" subtitle="Latest desk decisions, file moves and payments" :padded="false">
                    @if ($activity->isEmpty())
                        <x-ui.empty icon="activity" title="Nothing recorded yet">Desk decisions, file movements and payments will show here as they happen.</x-ui.empty>
                    @else
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($activity as $a)
                                <li>
                                    <a @if ($a['url']) href="{{ $a['url'] }}" @endif class="flex items-start gap-3 px-6 py-3 {{ $a['url'] ? 'hover:bg-slate-50 dark:hover:bg-slate-800/40' : '' }}">
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tones[$a['tone']] ?? $tones['blue'] }}"><i data-lucide="{{ $a['icon'] }}" class="h-4 w-4"></i></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $a['title'] }}</span>
                                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $a['section'] }}@if ($a['by']) · {{ $a['by'] }}@endif</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($a['when'])->diffForHumans() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.panel>
            </div>
        </div>
    </div>
</x-app-layout>
