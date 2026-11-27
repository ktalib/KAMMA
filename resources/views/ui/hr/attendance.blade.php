{{-- HR Manager > Attendance Tracking, copied from KLAES payroll/index?url=attendance: "Monitor employee login sessions,
     attendance records, and work hours", the figure cards, then the Attendance / Daily Logins / Cumulative Hours tabs with
     KLAES's columns. --}}
<x-app-layout title="Attendance Tracking">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $tabs = [
            'attendance' => ['Attendance', ['S/N', 'Employee ID', 'Name', 'Department', 'Unit', 'Shift', 'Daily Breakdown', 'Hours Worked', 'Login Days', 'Overtime', 'Attendance %', 'Status']],
            'daily-logins' => ['Daily Logins', ['S/N', 'Employee ID', 'Name', 'Department', 'Unit', 'Presence / Lateness', 'Login Time', 'Logout Time', 'Shift Hours', 'Total Hours']],
            'cumulative-hours' => ['Cumulative Hours', ['S/N', 'Employee ID', 'Name', 'Department', 'Unit', 'Shift Hours', 'Total Hours', 'Login Days', 'Attendance %']],
        ];
        $months = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="calendar-clock" title="Attendance Tracking" subtitle="Monitor employee login sessions, attendance records, and work hours" tone="blue" section="HR Manager" />
        @include('ui.hr.partials.stats')

        <nav class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @foreach ($tabs as $k => [$l])
                <a href="{{ route('hr.attendance', ['tab' => $k]) }}" @class(['flex-1 rounded-lg px-3 py-2 text-center font-medium', 'bg-blue-600 text-white' => $tab === $k, 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $tab !== $k])>{{ $l }}</a>
            @endforeach
        </nav>

        <form method="GET" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-3 dark:border-slate-800 dark:bg-slate-900">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if ($tab === 'daily-logins')
                <div><label for="at-date" class="{{ $label }}">Date</label><input id="at-date" type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="{{ $field }}"></div>
            @else
                <div><label for="at-month" class="{{ $label }}">Month</label><select id="at-month" name="month" class="{{ $field }}">@foreach ($months as $m)<option value="{{ $m->format('Y-m') }}" @selected($m->format('Y-m') === $month->format('Y-m'))>{{ $m->format('F Y') }}</option>@endforeach</select></div>
            @endif
            <div><label for="at-q" class="{{ $label }}">Search Employee</label><input id="at-q" type="search" name="q" value="{{ request('q') }}" class="{{ $field }}"></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply Filters</button></div>
        </form>

        <x-ui.panel :title="$tab === 'attendance' ? 'Employee Attendance Records' : ($tab === 'daily-logins' ? 'Daily Logins & Manual Attendance' : 'Cumulative Hours Leaderboard')" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>@foreach ($tabs[$tab][1] as $h)<th class="whitespace-nowrap px-4 py-3">{{ $h }}</th>@endforeach</tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="calendar-clock" title="No attendance records">Sign-in sessions of KAMMA staff will be counted here once the staff list is set up.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
