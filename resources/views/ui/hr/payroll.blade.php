{{-- HR Manager > Payroll, copied from KLAES payroll/index ("HR Management System: Manage employee rates, salaries, and payroll
     with flexible shift hours"): figure cards, then the Rates / Salary / Reports tabs with KLAES's columns and the
     "Edit Daily Rate & Shift" dialog. Saves post to the preview stub. --}}
<x-app-layout title="Payroll">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $tabs = [
            'rates' => ['Daily Rates & Shifts', 'Employee Daily Rates & Shifts', ['S/N', 'Employee ID', 'Name', 'Department', 'Unit', 'Position', 'Daily Rate', 'Base Salary', 'Shift Hours', 'Overtime', 'Status']],
            'salary' => ['Salary Calculation', 'Salary Breakdown', ['S/N', 'Employee', 'Shift', 'Daily Rate', 'Login Days', 'Base Salary', 'Bonuses', 'Extra Hours', 'Total']],
            'reports' => ['Payroll Reports', 'Employee Payroll Details', ['S/N', 'Employee ID', 'Name', 'Department', 'Shift', 'Daily Rate', 'Login Days', 'Net Salary']],
        ];
        $months = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
    @endphp

    <div class="space-y-6" x-data="{ rate: false }">
        <x-ui.page-head icon="wallet" title="HR Management System" subtitle="Manage employee rates, salaries, and payroll with flexible shift hours" tone="cyan" section="HR Manager">
            <button type="button" @click.stop="rate = true" class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-500"><i data-lucide="pencil" class="h-4 w-4"></i> Edit Daily Rate &amp; Shift</button>
        </x-ui.page-head>
        @include('ui.hr.partials.stats')

        <nav class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @foreach ($tabs as $k => [$l])
                <a href="{{ route('hr.payroll', ['tab' => $k]) }}" @class(['flex-1 rounded-lg px-3 py-2 text-center font-medium', 'bg-cyan-600 text-white' => $tab === $k, 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $tab !== $k])>{{ $l }}</a>
            @endforeach
        </nav>

        <form method="GET" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-3 dark:border-slate-800 dark:bg-slate-900">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div><label for="pr-month" class="{{ $label }}">Month</label><select id="pr-month" name="month" class="{{ $field }}">@foreach ($months as $m)<option value="{{ $m->format('Y-m') }}" @selected($m->format('Y-m') === $month->format('Y-m'))>{{ $m->format('F Y') }}</option>@endforeach</select></div>
            <div><label for="pr-q" class="{{ $label }}">Search Employee</label><input id="pr-q" type="search" name="q" value="{{ request('q') }}" class="{{ $field }}"></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button></div>
        </form>

        <x-ui.panel :title="$tabs[$tab][1]" :subtitle="$tab === 'salary' ? 'View and manage employee salaries for '.$month->format('F Y') : null" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>@foreach ($tabs[$tab][2] as $h)<th class="whitespace-nowrap px-4 py-3">{{ $h }}</th>@endforeach<th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="wallet" title="No employees yet">Rates and salaries are set per officer once KAMMA's staff list is set up.</x-ui.empty>
            @if ($tab === 'salary')
                <div class="border-t border-slate-100 px-6 py-4 text-right text-sm font-semibold dark:border-slate-800">Total Payroll: ₦0.00</div>
            @endif
        </x-ui.panel>

        {{-- Edit Daily Rate & Shift (KLAES payroll dialogs) --}}
        <div x-show="rate" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="rate = false">
            <form method="POST" action="{{ route('ui.preview', 'payroll-rate') }}" @click.outside="rate = false" class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900" x-data="{ ot: false }">
                @csrf
                <div class="flex items-start justify-between"><h2 class="text-lg font-bold text-slate-900 dark:text-white">Edit Daily Rate &amp; Shift</h2><button type="button" @click="rate = false" class="text-xl leading-none text-slate-400" aria-label="Close">&times;</button></div>
                <div><label for="rt-emp" class="{{ $label }}">Employee <span class="text-rose-500">*</span></label><select id="rt-emp" name="employee" required class="{{ $field }}"><option value="">Select employee</option></select></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><span class="{{ $label }}">Current Daily Rate</span><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/60">₦0.00</div></div>
                    <div><label for="rt-new" class="{{ $label }}">New Daily Rate (₦) <span class="text-rose-500">*</span></label><input id="rt-new" type="number" min="0" step="0.01" name="daily_rate" required class="{{ $field }}"></div>
                </div>
                <div><label for="rt-shift" class="{{ $label }}">Shift Hours</label><select id="rt-shift" name="shift_hours" class="{{ $field }}"><option value="8">8 Hours (Full Day)</option><option value="4">4 Hours (Half Day)</option></select><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sets the number of hours required for 1 login day</p></div>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="overtime" value="1" x-model="ot" class="mt-0.5 rounded dark:border-slate-600 dark:bg-slate-800"> <span><span class="font-semibold">Overtime Authorization</span><br><span class="text-xs text-slate-500 dark:text-slate-400">Allow overtime beyond shift hours. When disabled, sessions are auto-logged out at shift end.</span></span></label>
                <div x-show="ot" x-cloak><label for="rt-cap" class="{{ $label }}">Overtime Cap (hours)</label><input id="rt-cap" type="number" min="0" max="12" name="overtime_cap" :disabled="! ot" class="{{ $field }}"><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cap overtime to a maximum number of hours per day.</p></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="rt-eff" class="{{ $label }}">Effective Date <span class="text-rose-500">*</span></label><input id="rt-eff" type="date" name="effective_date" required class="{{ $field }}"></div>
                    <div><label for="rt-why" class="{{ $label }}">Reason for Change</label><input id="rt-why" name="reason" class="{{ $field }}"></div>
                </div>
                <div class="flex justify-end gap-3"><button type="button" @click="rate = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700 dark:text-slate-300">Cancel</button><button class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Save Changes</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
