{{-- System Admin > User Roles (KLAES users.index / module permission grid, shown not granted): KAMMA's modules (the sidebar's
     top-level sections) and the KLAES staff accounts that can sign in, read-only from KLAES dbo.users. Roles and
     permissions are on hold (user 2026-11-22), and the briefing puts access control in KLAES-CORE (Super Admin -> KAMMA
     Sub Admin), so nothing here grants anything yet. --}}
<x-app-layout title="User Roles">
    @php $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="shield-check" title="User Roles" subtitle="KLAES-METRO modules and the staff accounts that sign in" tone="slate" section="System Admin" />
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <i data-lucide="lock-open" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p><span class="font-semibold">Roles are on hold.</span> Every active KLAES account can open every KLAES-METRO screen. Access will be granted from KLAES-CORE by the Super Admin through a KAMMA Sub Admin.</p>
        </div>

        <x-ui.panel title="Modules" subtitle="One role per module, as in KLAES">
            <div class="flex flex-wrap gap-2">
                @foreach ($modules as $m)
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $m }}</span>
                @endforeach
            </div>
        </x-ui.panel>

        <x-ui.panel title="Staff accounts" subtitle="From KLAES (read-only); details are changed in KLAES" :padded="false">
            <x-slot:actions>
                <form method="GET" class="flex gap-2"><input type="search" name="q" value="{{ $q }}" placeholder="Name or username" class="{{ $field }}"><button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Search</button></form>
            </x-slot:actions>
            @if (! $users || $users->isEmpty())
                <x-ui.empty icon="users" :title="$users ? 'No account matches' : 'KLAES accounts could not be read'" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Username</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">KAMMA role</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($users as $u)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $users->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $u->name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $u->username }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $u->email ?: '—' }}</td>
                                    <td class="px-4 py-3"><span @class(['rounded-full px-2 py-0.5 text-xs font-medium', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => (int) $u->is_active === 1, 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => (int) $u->is_active !== 1])>{{ (int) $u->is_active === 1 ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="px-4 py-3 text-slate-400 dark:text-slate-500">Not assigned</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $users->links() }}</div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
