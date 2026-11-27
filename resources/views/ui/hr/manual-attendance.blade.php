{{-- HR Manager > Manual Attendance, copied from KLAES payroll/manual-attendance: date, shift and work-station filters,
     employee search, then the staff list (list or card view) to mark present / absent. Saves post to the preview stub. --}}
<x-app-layout title="Manual Attendance">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6" x-data="{ view: 'list' }">
        <x-ui.page-head icon="clipboard-check" title="Manual Attendance Capture" subtitle="Mark staff as present or absent. Check-in time is auto-captured when marked present." tone="blue" section="HR Manager" />
        <x-ui.preview-note what="Capturing attendance" />

        <form method="GET" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 lg:grid-cols-5 dark:border-slate-800 dark:bg-slate-900">
            <div><label for="ma-date" class="{{ $label }}">Attendance Date</label><input id="ma-date" type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="{{ $field }}"></div>
            <div><label for="ma-shift" class="{{ $label }}">Assigned Shift</label><select id="ma-shift" name="shift" class="{{ $field }}"><option value="">All shifts</option><option>8 Hours (Full Day)</option><option>4 Hours (Half Day)</option></select></div>
            <div><label for="ma-ws" class="{{ $label }}">Work Station</label><select id="ma-ws" name="station" class="{{ $field }}"><option value="">All work stations</option></select></div>
            <div><label for="ma-q" class="{{ $label }}">Search Employee</label><input id="ma-q" type="search" name="q" value="{{ request('q') }}" class="{{ $field }}"></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button></div>
        </form>

        @include('ui.hr.partials.stats')

        <x-ui.panel title="Staff Attendance" subtitle="Choose list or grid view to capture attendance efficiently." :padded="false">
            <x-slot:actions>
                <span class="text-sm text-slate-500 dark:text-slate-400">0 staff · 0 selected</span>
                <div class="flex rounded-lg border border-slate-200 p-0.5 text-xs dark:border-slate-700">
                    <button type="button" @click="view = 'list'" :class="view === 'list' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-300'" class="rounded-md px-2.5 py-1">List view</button>
                    <button type="button" @click="view = 'card'" :class="view === 'card' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-300'" class="rounded-md px-2.5 py-1">Card view</button>
                </div>
            </x-slot:actions>
            <div x-show="view === 'list'" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr><th class="px-4 py-3"><input type="checkbox" disabled class="rounded dark:border-slate-600 dark:bg-slate-800" aria-label="Select all"></th>@foreach (['S/N', 'Employee ID', 'Name', 'Department', 'Unit', 'Shift', 'Check-in', 'Status'] as $h)<th class="whitespace-nowrap px-4 py-3">{{ $h }}</th>@endforeach<th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="users" title="No staff to mark">Once KAMMA's staff list is set up, each officer appears here with Present / Absent buttons.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
