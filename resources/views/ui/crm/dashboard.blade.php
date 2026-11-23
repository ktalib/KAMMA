<x-app-layout title="CRM Dashboard">
    @php
        $n = fn ($v) => number_format((int) $v);
        $tiles = [
            ['Individual', 'user', 'individual'],
            ['Group', 'users', 'group'],
            ['Multiple Owners', 'users-round', 'multiple-owners'],
            ['Corporate', 'building-2', 'corporate'],
        ];
        $typeChart = [
            'type' => 'doughnut',
            'labels' => array_keys($types),
            'series' => [['label' => 'Customers', 'data' => array_values($types)]],
        ];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head
            icon="contact"
            title="Customer Relationship Management"
            subtitle="KLAES customer master and KAMMA application records"
            tone="indigo"
            section="CRM"
        >
            <a href="{{ route('crm.appointments') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                <i data-lucide="calendar-plus" class="h-4 w-4"></i>
                New appointment
            </a>
        </x-ui.page-head>

        <x-ui.klaes-note tables="KLAES customers" :error="$error" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($tiles as [$label, $icon, $slug])
                <x-ui.stat
                    :title="$label"
                    :value="$n($types[$label] ?? 0)"
                    hint="In the KLAES customer master"
                    :icon="$icon"
                    tone="blue"
                    :href="route('crm.customers', $slug)"
                />
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Shadow files" :value="$n($shadowFiles)" hint="Land files linked to KAMMA" icon="files" tone="emerald" :href="route('dms.indexing.index')" />
            <x-ui.stat title="Planning reports" :value="$n($reports)" hint="KAMMA planning recommendations" icon="clipboard-check" tone="violet" :href="route('kamma.pr.index', 'conversion')" />
            <x-ui.stat title="Appointments today" value="0" hint="Appointment calendar" icon="calendar-clock" tone="amber" :href="route('crm.calendar')" />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-ui.panel title="Customers by type" subtitle="KLAES customer master">
                <x-ui.chart :chart="$typeChart" label="Customers by type" />
            </x-ui.panel>

            <x-ui.panel title="Latest KAMMA shadow files" :padded="false" class="xl:col-span-2">
                @if ($recentFiles->isEmpty())
                    <x-ui.empty icon="user-round-search" title="No shadow files yet">
                        Shadow files appear after a KLAES land file is matched or commissioned.
                    </x-ui.empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">S/N</th>
                                    <th class="px-4 py-3">File No.</th>
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3">Plot No.</th>
                                    <th class="px-4 py-3">District</th>
                                    <th class="px-4 py-3">LGA</th>
                                    <th class="px-4 py-3">Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($recentFiles as $file)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-indigo-700 dark:text-indigo-300">{{ $file->file_number }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $file->file_title ?: '—' }}</td>
                                        <td class="px-4 py-3">{{ $file->plot_number ?: '—' }}</td>
                                        <td class="px-4 py-3">{{ $file->district ?: '—' }}</td>
                                        <td class="px-4 py-3">{{ $file->lga ?: '—' }}</td>
                                        <td class="px-4 py-3">{{ $file->location ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.panel>
        </div>
    </div>
</x-app-layout>
