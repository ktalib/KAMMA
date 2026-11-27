{{-- KLAES payroll/partials/stats-cards, for KAMMA (no KAMMA staff list yet, so the figures are zero). --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <x-ui.stat title="Total Employees" value="0" hint="Active staff members" icon="users" tone="blue" />
    <x-ui.stat title="Present Today" value="0" hint="Logged in today" icon="user-check" tone="emerald" />
    <x-ui.stat title="Avg Attendance" value="0.0%" hint="Average for current period" icon="percent" tone="violet" />
    <x-ui.stat title="Total Hours Logged" value="0.00h" hint="Cumulative hours this period" icon="clock-3" tone="amber" />
    <x-ui.stat title="Total Payroll" value="₦0.00" hint="Net salaries this period" icon="wallet" tone="cyan" />
</div>
<div class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">
    <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
    <p>KAMMA's staff list is not set up yet: staff sign in with their KLAES accounts, and which accounts belong to KAMMA (department and officer list) is still to be agreed. Staff will be listed here once it is.</p>
</div>
