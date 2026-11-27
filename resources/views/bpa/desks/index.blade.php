<x-app-layout :title="$title">
    @php
        $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
        $errs = $errors->getBag('decision');
        $pick = fn ($a) => [
            'id' => $a->id,
            'url' => route('bpa.desks.decide', [$desk, $a]),
            'bp' => $a->bp_file_no,
            'application_no' => $a->application_no,
            'file_number' => $a->file_number,
            'developer' => $a->developer_name,
            'check' => $a->land_use_check,
        ];
        $recommendTo = \App\Support\Bpa\BpaStage::label($onRecommend);
        $refuseTo = \App\Support\Bpa\BpaStage::label($onRefuse);
    @endphp

    <div class="space-y-6"
         x-data="{
            file: @js($reopen ? $pick($reopen) : null),
            decision: @js(old('decision', '')),
            openDecide(f) { this.file = f; this.decision = ''; },
         }"
         @keydown.escape.window="file = null">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Approvals</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $title }}: recommendation</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Recommend sends the file to the {{ $recommendTo }}. Not recommended returns it to {{ $refuseTo }}, with your reason.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errs->has('application'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errs->first('application') }}</div>
        @endif

        <x-tabs :tabs="['awaiting' => ['Awaiting', $counts['awaiting']], 'decided' => ['Decided', $counts['decided']]]" :active="$tab" route="bpa.desks.index" :query="['desk' => $desk]" />

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label for="search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="BP No., application no., file no., developer, plot…" class="{{ $field }} w-96">
            </div>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Search</button>
            @if ($search !== '')
                <a href="{{ route('bpa.desks.index', [$desk, 'tab' => $tab]) }}" class="px-2 py-2 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Clear</a>
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
                        <th class="{{ $th }}">{{ $tab === 'decided' ? 'Decision' : 'Received' }}</th>
                        <th class="{{ $th }} sticky right-0 bg-slate-50 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($applications as $application)
                        @php
                            $decision = $latest[$application->id] ?? null;
                            $came = $arrived[$application->id] ?? null;
                        @endphp
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
                            <td class="{{ $td }} min-w-[10rem] !whitespace-normal">
                                @if ($tab === 'decided' && $decision)
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $decision->action === 'recommended'
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30'
                                        : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30' }}">
                                        {{ $decision->action === 'recommended' ? 'Recommended' : 'Not recommended' }}
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ $decision->acted_by_name }}, {{ $decision->acted_at?->format('d M Y') }}</span>
                                    @if ($decision->remarks)
                                        <span class="mt-1 block text-xs text-slate-600 dark:text-slate-300">“{{ \Illuminate\Support\Str::limit($decision->remarks, 90) }}”</span>
                                    @endif
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Now: {{ \App\Support\Bpa\BpaStage::label($application->stage) }}</span>
                                @else
                                    {{ $came?->acted_at?->format('d M Y') }}
                                    @if ($came?->action === 'resent')
                                        <span class="block text-xs font-medium text-amber-600 dark:text-amber-400">Re-sent after correction</span>
                                    @elseif ($came?->action === 'not_recommended')
                                        <span class="block text-xs font-medium text-rose-600 dark:text-rose-400">Returned by {{ \App\Support\Bpa\BpaStage::label($came->from_stage) }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="{{ $td }} sticky right-0 bg-white text-right shadow-[-6px_0_6px_-6px_rgba(15,23,42,0.15)] dark:bg-slate-900">
                                <x-row-actions>
                                    @if ($application->stage === $stage)
                                        <button type="button" @click.stop="openDecide(@js($pick($application))); open = false"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                            Recommend / Not recommend
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
                            <td colspan="11" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ $search !== '' ? 'No files match this search.' : ($tab === 'decided' ? 'No decisions yet.' : 'No files are waiting on this desk.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $applications->links() }}

        {{-- Decision pop-up --}}
        <div x-show="file" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="decide-title">
            <form method="POST" :action="file ? file.url : ''" @click.outside="file = null"
                  class="w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <input type="hidden" name="application_id" :value="file ? file.id : ''">
                <div>
                    <h2 id="decide-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title }}: recommendation</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        <span class="font-semibold text-indigo-700 dark:text-indigo-300" x-text="file ? file.bp : ''"></span>
                        <span x-text="file ? ' · ' + file.file_number : ''"></span><br>
                        <span x-text="file ? file.developer : ''"></span>
                    </p>
                    <p x-show="file && file.check === 'Mismatch'" class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                        Flagged at application: the proposed use does not match the land file's land use.
                    </p>
                </div>
                @if ($errs->any() && ! $errs->has('application'))
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        @foreach ($errs->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif
                <fieldset class="space-y-2">
                    <legend class="mb-1 text-sm font-medium text-slate-700 dark:text-slate-300">Decision <span class="text-rose-500">*</span></legend>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700" :class="decision === 'recommended' ? '!border-emerald-400 bg-emerald-50 dark:!border-emerald-500/60 dark:bg-emerald-500/10' : ''">
                        <input type="radio" name="decision" value="recommended" x-model="decision" required class="mt-0.5 h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800">
                        <span><span class="font-medium text-slate-900 dark:text-slate-100">Recommend</span><span class="block text-slate-500 dark:text-slate-400">Send the file to the {{ $recommendTo }}.</span></span>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700" :class="decision === 'not_recommended' ? '!border-rose-400 bg-rose-50 dark:!border-rose-500/60 dark:bg-rose-500/10' : ''">
                        <input type="radio" name="decision" value="not_recommended" x-model="decision" required class="mt-0.5 h-4 w-4 border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-slate-600 dark:bg-slate-800">
                        <span><span class="font-medium text-slate-900 dark:text-slate-100">Not recommended</span><span class="block text-slate-500 dark:text-slate-400">Return the file to {{ $refuseTo }}. A reason is required.</span></span>
                    </label>
                </fieldset>
                <div>
                    <label for="remarks" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        <span x-text="decision === 'not_recommended' ? 'Reason' : 'Remarks'"></span>
                        <span x-show="decision === 'not_recommended'" class="text-rose-500">*</span>
                    </label>
                    <textarea id="remarks" name="remarks" rows="3" maxlength="2000" :required="decision === 'not_recommended'" class="{{ $field }}">{{ old('remarks') }}</textarea>
                </div>
                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" @click="file = null" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-50"
                            :disabled="! decision"
                            :class="decision === 'not_recommended' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'">
                        <span x-text="decision === 'not_recommended' ? 'Return the file' : 'Recommend'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
