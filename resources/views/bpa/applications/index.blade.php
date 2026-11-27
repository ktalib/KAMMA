<x-app-layout title="Application Form">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Building Plan Approval applications</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Assessment Stage. The BP file is commissioned after the application bill is paid.</p>
            </div>
            <a href="{{ route('bpa.applications.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New application
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Application no., file no., developer, plot, district…" class="{{ $field }} w-80">
            </div>
            <div>
                <label for="land_use_check" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Land-use check</label>
                <select id="land_use_check" name="land_use_check" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach (['Match', 'Mismatch', 'Not checked'] as $option)
                        <option value="{{ $option }}" @selected($check === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="source" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Sent from</label>
                <select id="source" name="source" class="{{ $field }}">
                    <option value="">All</option>
                    <option value="portal" @selected($source === 'portal')>Portal (applicant)</option>
                    <option value="staff" @selected($source === 'staff')>Entered by staff</option>
                </select>
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Filter</button>
            @if ($search !== '' || $check !== '' || $source !== '')
                <a href="{{ route('bpa.applications.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        <th class="{{ $th }}">Application No.</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }}">Plot No.</th>
                        <th class="{{ $th }}">District</th>
                        <th class="{{ $th }}">LGA</th>
                        <th class="{{ $th }}">Location</th>
                        <th class="{{ $th }}">Use / check</th>
                        <th class="{{ $th }}">Bill</th>
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $applications->firstItem() + $loop->index }}</td>
                            <td class="{{ $td }}">
                                <span class="block font-medium text-slate-900 dark:text-slate-100">{{ $application->application_no }}</span>
                                @if ($application->portal_applicant_id)
                                    <span class="mt-0.5 inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300" title="Sent by the applicant on the KLAES-METRO portal">Portal</span>
                                @endif
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $application->created_at?->format('d M Y') }}</span>
                            </td>
                            <td class="{{ $td }}">{{ $application->file_number }}</td>
                            <td class="{{ $td }} min-w-[9rem] !whitespace-normal">{{ $application->developer_name }}</td>
                            <td class="{{ $td }}">{{ $application->plot_no ?? '—' }}</td>
                            <td class="{{ $td }} min-w-[7rem] !whitespace-normal">{{ $application->district ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $application->lga ?? '—' }}</td>
                            <td class="{{ $td }} min-w-[10rem] !whitespace-normal">{{ $application->location ?? '—' }}</td>
                            <td class="{{ $td }}">
                                <span class="block">{{ $application->proposed_use === 'Other' ? $application->proposed_use_other : $application->proposed_use }}</span>
                                <x-land-use-badge :check="$application->land_use_check" class="mt-1" />
                            </td>
                            <td class="{{ $td }}">
                                @if ($application->bill)
                                    <span class="block">₦{{ number_format((float) $application->bill->amount, 2) }}</span>
                                    <span class="block text-xs {{ $application->bill->status === 'Paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $application->bill->status }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                <x-row-actions>
                                    <x-row-action :href="route('bpa.applications.show', $application)">
                                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-slot:icon>
                                        View application
                                    </x-row-action>
                                    @if ($application->bill)
                                        <x-row-action :href="route('bpa.bills.index', ['search' => $application->bill->bill_no])">
                                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z" /></svg></x-slot:icon>
                                            View bill
                                        </x-row-action>
                                    @endif
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' || $check !== '' || $source !== '' ? 'No applications match these filters.' : 'No applications yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $applications->links() }}
    </div>
</x-app-layout>
