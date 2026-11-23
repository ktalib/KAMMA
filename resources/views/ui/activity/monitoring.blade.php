{{-- Activity Monitoring (DMS / HR Manager / System Admin): who did what, per day and per officer, from KAMMA's own records. --}}
<x-app-layout title="Activity Monitoring">
    @php
        $section = ['dms' => 'DMS', 'hr' => 'HR Manager', 'admin' => 'System Admin'][$scope];
        $dayChart = ['type' => 'bar', 'labels' => array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'), array_keys($perDay)), 'series' => [['label' => 'Actions', 'data' => array_values($perDay)]]];
        $actionChart = ['type' => 'hbar', 'labels' => array_slice(array_keys($byAction), 0, 8), 'series' => [['label' => 'Actions', 'data' => array_slice(array_values($byAction), 0, 8)]]];
        $chip = fn ($on) => $on ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="activity" title="Activity Monitoring" :subtitle="$scope === 'dms' ? 'Indexing, scanning and page typing by officer' : 'What KAMMA officers did, by day and by officer'" tone="cyan" :section="$section">
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 text-sm font-semibold dark:border-slate-700">
                @foreach ([1 => 'Today', 7 => '7 days', 30 => '30 days'] as $d => $l)
                    <a href="{{ route('activity.monitoring', ['scope' => $scope, 'days' => $d]) }}" class="px-3 py-2 {{ $chip($days === $d) }}">{{ $l }}</a>
                @endforeach
            </div>
        </x-ui.page-head>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat title="Actions" :value="number_format($total)" :hint="'In the last '.($days === 1 ? 'day' : $days.' days')" icon="mouse-pointer-click" tone="cyan" />
            <x-ui.stat title="Active officers" :value="number_format($byUser->count())" hint="Officers with at least one action" icon="users" tone="emerald" />
            <x-ui.stat title="Busiest officer" :value="$byUser->first()['who'] ?? '—'" :hint="$byUser->first() ? number_format($byUser->first()['count']).' actions' : 'No activity yet'" icon="trophy" tone="amber" />
            <x-ui.stat title="Commonest action" :value="array_key_first($byAction) ?? '—'" :hint="$byAction ? number_format(reset($byAction)).' times' : 'No activity yet'" icon="list-checks" tone="violet" />
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.panel title="Actions per day"><x-ui.chart :chart="$dayChart" label="Actions per day" /></x-ui.panel>
            <x-ui.panel title="Actions by kind"><x-ui.chart :chart="$actionChart" label="Actions by kind" /></x-ui.panel>
        </div>

        <div class="grid gap-6 xl:grid-cols-5">
            <x-ui.panel title="By officer" class="xl:col-span-3" :padded="false">
                @if ($byUser->isEmpty())
                    <x-ui.empty icon="user-x" title="No activity in this period" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Officer</th><th class="px-4 py-3 text-right">Actions</th><th class="px-4 py-3">Mostly</th><th class="px-4 py-3">Last seen</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($byUser as $u)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $u['who'] }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ number_format($u['count']) }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ collect($u['actions'])->map(fn ($n, $a) => "$a ($n)")->implode(', ') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $u['last']->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.panel>

            <x-ui.panel title="Latest" class="xl:col-span-2">
                <x-slot:actions>
                    <a href="{{ route('admin.activity-logs') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">Activity Logs</a>
                </x-slot:actions>
                @forelse ($events as $e)
                    <div class="flex gap-3 border-b border-slate-100 py-2.5 last:border-0 dark:border-slate-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300"><i data-lucide="{{ $e['icon'] }}" class="h-4 w-4"></i></span>
                        <div class="min-w-0 text-sm">
                            <p class="text-slate-800 dark:text-slate-200"><span class="font-semibold">{{ $e['who'] }}</span> · {{ $e['action'] }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $e['ref'] }}{{ $e['detail'] ? ' · '.$e['detail'] : '' }} · {{ $e['at']->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <x-ui.empty icon="activity" title="Nothing yet" />
                @endforelse
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
