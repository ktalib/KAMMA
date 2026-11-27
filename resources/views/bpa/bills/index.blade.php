<x-app-layout title="Initial Bill">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300';
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Initial Bill</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Building Plan Approval application fees, raised when an application is saved.</p>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Bill no., application no., file no., developer…" class="{{ $field }} w-80">
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Status</label>
                <select id="status" name="status" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach (['Unpaid', 'Paid'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Filter</button>
            @if ($search !== '' || $status !== '')
                <a href="{{ route('bpa.bills.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        <th class="{{ $th }}">Bill No.</th>
                        <th class="{{ $th }}">Application No.</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }}">Bill type</th>
                        <th class="{{ $th }} text-right">Plots</th>
                        <th class="{{ $th }} text-right">Rate (₦)</th>
                        <th class="{{ $th }} text-right">Amount (₦)</th>
                        <th class="{{ $th }}">Status</th>
                        <th class="{{ $th }}">Raised</th>
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($bills as $bill)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $bills->firstItem() + $loop->index }}</td>
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $bill->bill_no }}</td>
                            <td class="{{ $td }}">{{ $bill->application?->application_no }}</td>
                            <td class="{{ $td }}">{{ $bill->application?->file_number }}</td>
                            <td class="{{ $td }}">{{ $bill->application?->developer_name }}</td>
                            <td class="{{ $td }}">{{ $bill->bill_type }}</td>
                            <td class="{{ $td }} text-right">{{ $bill->quantity }}</td>
                            <td class="{{ $td }} text-right">{{ number_format((float) $bill->rate, 2) }}</td>
                            <td class="{{ $td }} text-right font-medium text-slate-900 dark:text-slate-100">{{ number_format((float) $bill->amount, 2) }}</td>
                            <td class="{{ $td }}">
                                <span class="{{ $bill->status === 'Paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $bill->status }}</span>
                            </td>
                            <td class="{{ $td }}">{{ $bill->created_at?->format('d M Y') }}</td>
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                @if ($bill->application)
                                    <x-row-actions>
                                        <x-row-action :href="route('bpa.applications.show', $bill->application)">
                                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-slot:icon>
                                            View application
                                        </x-row-action>
                                    </x-row-actions>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' || $status !== '' ? 'No bills match these filters.' : 'No bills yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $bills->links() }}
    </div>
</x-app-layout>
