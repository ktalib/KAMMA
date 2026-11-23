{{-- System Admin > Activity Logs: every recorded KAMMA action, newest first, last 90 days. --}}
<x-app-layout title="Activity Logs">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $tone = ['KAMMA' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300', 'Tracking' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', 'DMS' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="scroll" title="Activity Logs" subtitle="Shadow files, bills, payments, desk decisions, KSI reports, file movements and DMS work, last 90 days" tone="slate" section="System Admin">
            <a href="{{ route('activity.monitoring', ['scope' => 'admin']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="activity" class="h-4 w-4"></i> Activity Monitoring</a>
        </x-ui.page-head>

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="min-w-[16rem] flex-1">
                <label for="al-q" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Search</label>
                <input id="al-q" type="search" name="q" value="{{ $q }}" placeholder="Officer, file number, action…" class="{{ $field }} w-full">
            </div>
            <div>
                <label for="al-m" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Module</label>
                <select id="al-m" name="module" class="{{ $field }}"><option value="">All modules</option>@foreach (['KAMMA', 'Tracking', 'DMS'] as $m)<option @selected($module === $m)>{{ $m }}</option>@endforeach</select>
            </div>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Filter</button>
            <a href="{{ route('admin.activity-logs') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Reset</a>
        </form>

        <x-ui.panel :padded="false">
            @if ($rows->isEmpty())
                <x-ui.empty icon="scroll" title="No activity recorded">Actions appear here as officers commission shadow files, raise bills, record payments, decide files, move files and work in the DMS.</x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">When</th><th class="px-4 py-3">Officer</th><th class="px-4 py-3">Module</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Detail</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $e)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $rows->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">{{ $e['at']->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $e['who'] }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tone[$e['module']] }}">{{ $e['module'] }}</span></td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $e['action'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-indigo-700 dark:text-indigo-300">{{ $e['ref'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($e['detail'], 60) ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $rows->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
