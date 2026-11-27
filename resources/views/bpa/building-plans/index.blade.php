<x-app-layout title="Building Plan">
    @php
        $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
    @endphp

    <div class="space-y-6" x-data="{ file: null, openResend(f) { this.file = f; } }" @keydown.escape.window="file = null">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Building Plan</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Commissioned BP files and their building plans, for the Architectural Department. A file the Director (Arch.) did not recommend comes back here; send it back once corrected.</p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->getBag('resend')->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->getBag('resend')->first() }}</div>
        @endif

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="BP No., application no., file no., developer, plot…" class="{{ $field }} w-96">
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="returned" value="1" @checked($returnedOnly) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
                Returned only <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $returnedCount }}</span>
            </label>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Search</button>
            @if ($search !== '' || $returnedOnly)
                <a href="{{ route('bpa.building-plans.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="{{ $th }}">S/N</th>
                        <th class="{{ $th }}">BP File No.</th>
                        <th class="{{ $th }}">File No.</th>
                        <th class="{{ $th }}">Developer</th>
                        <th class="{{ $th }}">Plot No.</th>
                        <th class="{{ $th }}">District</th>
                        <th class="{{ $th }}">LGA</th>
                        <th class="{{ $th }}">Location</th>
                        <th class="{{ $th }}">Use / check</th>
                        <th class="{{ $th }}">Plans</th>
                        <th class="{{ $th }}">Now with</th>
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $applications->firstItem() + $loop->index }}</td>
                            <td class="{{ $td }}">
                                <span class="block font-semibold text-indigo-700 dark:text-indigo-300">{{ $application->bp_file_no }}</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $application->application_no }}</span>
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
                                {{ $application->plan_count }} plan{{ (int) $application->plan_count === 1 ? '' : 's' }}@if ($application->cad_count), {{ $application->cad_count }} CAD @endif
                            </td>
                            <td class="{{ $td }} min-w-[9rem] !whitespace-normal">
                                @if ($application->stage === 'architecture')
                                    <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30">Returned</span>
                                    @if ($back = $returns[$application->id] ?? null)
                                        <span class="mt-1 block text-xs text-slate-600 dark:text-slate-300">“{{ \Illuminate\Support\Str::limit($back->remarks, 80) }}”</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $back->acted_by_name }}, {{ $back->acted_at?->format('d M Y') }}</span>
                                    @endif
                                @else
                                    {{ \App\Support\Bpa\BpaStage::label($application->stage) }}
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">Commissioned {{ $application->commissioned_at?->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                <x-row-actions>
                                    @if ($application->stage === 'architecture')
                                        <button type="button" @click.stop="openResend(@js(['url' => route('bpa.building-plans.resend', $application), 'bp' => $application->bp_file_no, 'developer' => $application->developer_name])); open = false"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                            Send back to Director (Arch.)
                                        </button>
                                    @endif
                                    <x-row-action :href="route('bpa.applications.show', $application)">
                                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-slot:icon>
                                        View file and plans
                                    </x-row-action>
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' ? 'No BP files match this search.' : 'No BP files commissioned yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $applications->links() }}

        {{-- Send a returned file back to the Director (Arch.) --}}
        <div x-show="file" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="resend-title">
            <form method="POST" :action="file ? file.url : ''" @click.outside="file = null"
                  class="w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <div>
                    <h2 id="resend-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Send back to the Director (Arch.)</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-indigo-700 dark:text-indigo-300" x-text="file ? file.bp : ''"></span>
                        <span x-text="file ? ' · ' + file.developer : ''"></span>
                    </p>
                </div>
                <div>
                    <label for="resend-remarks" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">What was corrected</label>
                    <textarea id="resend-remarks" name="remarks" rows="3" maxlength="2000" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="file = null" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Send back</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
