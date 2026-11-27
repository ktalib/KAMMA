<x-app-layout title="File Commissioning">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
    @endphp

    <div class="space-y-6" x-data="{ confirm: null }" @keydown.escape.window="confirm = null">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">File Commissioning</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Paid applications get their BP file number: BP-year-serial, e.g. BP-{{ now()->year }}-1. The serial restarts every 1 January.</p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->has('application'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first('application') }}</div>
        @endif

        <x-tabs :tabs="['awaiting' => ['Awaiting commissioning', $counts['awaiting']], 'commissioned' => ['Commissioned', $counts['commissioned']]]" :active="$tab" route="bpa.commissioning.index" />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="BP No., application no., file no., developer, plot…" class="{{ $field }} w-96">
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Search</button>
            @if ($search !== '')
                <a href="{{ route('bpa.commissioning.index', ['tab' => $tab]) }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        @if ($tab === 'commissioned')
                            <th class="{{ $th }}">BP File No.</th>
                        @endif
                        <th class="{{ $th }}">Application No.</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }}">Plot No.</th>
                        <th class="{{ $th }}">District</th>
                        <th class="{{ $th }}">LGA</th>
                        <th class="{{ $th }}">Location</th>
                        <th class="{{ $th }}">{{ $tab === 'commissioned' ? 'Commissioned' : 'Paid on' }}</th>
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $applications->firstItem() + $loop->index }}</td>
                            @if ($tab === 'commissioned')
                                <td class="{{ $td }} font-semibold text-indigo-700 dark:text-indigo-300">{{ $application->bp_file_no }}</td>
                            @endif
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $application->application_no }}</td>
                            <td class="{{ $td }}">{{ $application->file_number }}</td>
                            <td class="{{ $td }} min-w-[9rem] !whitespace-normal">{{ $application->developer_name }}</td>
                            <td class="{{ $td }}">{{ $application->plot_no ?? '—' }}</td>
                            <td class="{{ $td }} min-w-[7rem] !whitespace-normal">{{ $application->district ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $application->lga ?? '—' }}</td>
                            <td class="{{ $td }} min-w-[10rem] !whitespace-normal">{{ $application->location ?? '—' }}</td>
                            <td class="{{ $td }}">
                                @if ($tab === 'commissioned')
                                    {{ $application->commissioned_at?->format('d M Y') }}
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $application->commissioned_by_name }}</span>
                                @else
                                    {{ $application->bill?->paid_on?->format('d M Y') }}
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">Receipt {{ $application->bill?->receipt_no }}</span>
                                @endif
                            </td>
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                <x-row-actions>
                                    @if ($tab === 'awaiting')
                                        <button type="button"
                                                @click.stop="confirm = @js(['url' => route('bpa.commissioning.store', $application), 'application_no' => $application->application_no, 'file_number' => $application->file_number, 'developer' => $application->developer_name]); open = false"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                                            Commission BP file
                                        </button>
                                    @endif
                                    <x-row-action :href="route('bpa.applications.show', $application)">
                                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-slot:icon>
                                        View application
                                    </x-row-action>
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'commissioned' ? 11 : 10 }}" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' ? 'No applications match this search.' : ($tab === 'commissioned' ? 'No BP files commissioned yet.' : 'No paid applications are waiting for a BP file.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $applications->links() }}

        {{-- Confirm before a BP number is given: numbers are never reused. --}}
        <div x-show="confirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="commission-title">
            <form method="POST" :action="confirm ? confirm.url : ''" @click.outside="confirm = null"
                  class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <h2 id="commission-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Commission BP file?</h2>
                <dl class="space-y-2 rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800/60">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Application</dt><dd class="font-medium text-slate-900 dark:text-slate-100" x-text="confirm ? confirm.application_no : ''"></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Land file</dt><dd class="text-slate-900 dark:text-slate-100" x-text="confirm ? confirm.file_number : ''"></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Developer</dt><dd class="text-right text-slate-900 dark:text-slate-100" x-text="confirm ? confirm.developer : ''"></dd></div>
                </dl>
                <p class="text-sm text-slate-600 dark:text-slate-300">The next BP number for {{ now()->year }} is given now and cannot be reused.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirm = null" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Commission</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
