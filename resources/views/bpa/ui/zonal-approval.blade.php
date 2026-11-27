{{-- KAMMA > Approvals > Head of Zonal Office. Briefing step 6: the head assigns the file to an officer with an assignment form
     "like I-to-S" (KLAES Cadastral Instruction to Surveyor: instruction no., date, officer, file, plot, job location, scope,
     instructions, issued by), then reviews the officer's Site Inspection Report: approve (on to Final Conveyance), return it to
     the officer, or refer the file back to the Deputy Director (Arch.). Tabs as KLAES approval pages. No role gating (Zonal
     Office roles on hold). Saves through Bpa\ZonalController. --}}
<x-app-layout title="Head of Zonal Office">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $tabs = ['awaiting' => 'Awaiting assignment', 'assigned' => 'Assigned to officers', 'reports' => 'Reports to review'];
        $th = 'whitespace-nowrap px-4 py-3';
        $menuBtn = 'flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800';
        $errorBag = $errors->getBag('assign')->any() ? $errors->getBag('assign') : ($errors->getBag('review')->any() ? $errors->getBag('review') : null);
        $approveRec = \App\Models\Bpa\SiteInspection::RECOMMEND_APPROVAL;
    @endphp

    <div class="space-y-6" x-data="{ assign: null, review: null, decision: '', officerQ: '', officers: [], officer: null,
            async findOfficer() {
                if (this.officerQ.trim().length < 2) { this.officers = []; return; }
                try { const r = await fetch(@js(route('tracking.log.officers')) + '?q=' + encodeURIComponent(this.officerQ), { headers: { Accept: 'application/json' } }); this.officers = r.ok ? ((await r.json()).officers || []) : []; } catch (e) { this.officers = []; }
            } }">
        <x-ui.page-head icon="user-cog" title="Head of Zonal Office" subtitle="Assign BP files to inspecting officers and review their reports" tone="violet" section="KAMMA › Approvals" />

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errorBag)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-inside list-disc">@foreach ($errorBag->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Awaiting assignment" :value="number_format($counts['awaiting'])" hint="Sent on by the Deputy Director (Arch.)" icon="inbox" tone="amber" :href="route('bpa.zonal-approval', ['tab' => 'awaiting'])" />
            <x-ui.stat title="Assigned to officers" :value="number_format($counts['assigned'])" hint="Site visit / inspection in progress" icon="user-check" tone="blue" :href="route('bpa.zonal-approval', ['tab' => 'assigned'])" />
            <x-ui.stat title="Reports to review" :value="number_format($counts['reports'])" hint="Filed by officers" icon="clipboard-check" tone="emerald" :href="route('bpa.zonal-approval', ['tab' => 'reports'])" />
        </div>

        <nav class="flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @foreach ($tabs as $k => $l)
                <a href="{{ route('bpa.zonal-approval', ['tab' => $k]) }}" @class(['flex-1 rounded-lg px-3 py-2 text-center font-medium', 'bg-violet-600 text-white' => $tab === $k, 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $tab !== $k])>{{ $l }} ({{ $counts[$k] }})</a>
            @endforeach
        </nav>

        <x-ui.panel :title="$tabs[$tab]" :padded="false">
            @if ($files->isEmpty())
                <x-ui.empty icon="{{ $tab === 'reports' ? 'clipboard-check' : 'user-check' }}" :title="['awaiting' => 'No file awaiting assignment', 'assigned' => 'No file with an officer', 'reports' => 'No report to review'][$tab]">
                    {{ ['awaiting' => 'Files arrive when the Deputy Director (Arch.) recommends them.', 'assigned' => 'Assign a file from the first tab.', 'reports' => 'Reports arrive when an officer files a Site Inspection Report.'][$tab] }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th><th class="{{ $th }}">BP File No.</th><th class="{{ $th }}">Land File No.</th><th class="{{ $th }}">Developer</th>
                                <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th>
                                @if ($tab === 'assigned')<th class="{{ $th }}">Instruction No.</th><th class="{{ $th }}">Officer</th><th class="{{ $th }}">Due</th>@endif
                                @if ($tab === 'reports')<th class="{{ $th }}">Officer</th><th class="{{ $th }}">Recommendation</th><th class="{{ $th }}">Filed</th>@endif
                                @if ($tab === 'awaiting')<th class="{{ $th }}">Received</th>@endif
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($files as $a)
                                @php
                                    $assignment = $a->assignments->first();
                                    $report = $a->inspections->first();
                                    $returned = $tab === 'assigned' && $report && $report->review_decision === 'returned';
                                    $overdue = $assignment?->due_date && $assignment->due_date->lt(today());
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $files->firstItem() + $loop->index }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $a->bp_file_no }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $a->file_number }}</td>
                                    <td class="px-4 py-3">{{ $a->developer_name }}</td>
                                    <td class="px-4 py-3">{{ $a->plot_no ?: '—' }}</td><td class="px-4 py-3">{{ $a->district ?: '—' }}</td><td class="px-4 py-3">{{ $a->lga ?: '—' }}</td><td class="px-4 py-3">{{ $a->location ?: '—' }}</td>
                                    @if ($tab === 'assigned')
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $assignment?->instruction_no ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $assignment?->officer_name ?? '—' }} @if ($returned)<span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" title="{{ $report->review_remarks }}">Report returned</span>@endif</td>
                                        <td @class(['whitespace-nowrap px-4 py-3', 'font-semibold text-rose-600 dark:text-rose-400' => $overdue])>{{ $assignment?->due_date?->format('d M Y') ?? '—' }}</td>
                                    @endif
                                    @if ($tab === 'reports')
                                        <td class="px-4 py-3">{{ $report?->officer_name ?? '—' }}</td>
                                        <td class="px-4 py-3"><span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $report?->recommendation === $approveRec, 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' => $report && $report->recommendation !== $approveRec])>{{ $report?->recommendation ?? '—' }}</span></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $report?->filed_at?->format('d M Y') }}</td>
                                    @endif
                                    @if ($tab === 'awaiting')
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500 dark:text-slate-400">{{ $a->updated_at?->format('d M Y') }}</td>
                                    @endif
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            @if ($tab === 'awaiting')
                                                <button type="button" class="{{ $menuBtn }}"
                                                        @click.stop="assign = @js(['action' => route('bpa.zonal.assign', $a), 'bp' => $a->bp_file_no, 'title' => $a->developer_name, 'plot' => $a->plot_no, 'loc' => collect([$a->district, $a->lga])->filter()->implode(', ')]); officer = null; officerQ = ''; officers = []; open = false">
                                                    <i data-lucide="user-plus" class="h-4 w-4 text-slate-400"></i> Assign officer
                                                </button>
                                            @endif
                                            @if ($tab === 'assigned' && $assignment)
                                                <x-row-action :href="route('bpa.zonal.instruction', $assignment)"><x-slot:icon><i data-lucide="printer" class="h-4 w-4"></i></x-slot:icon> Print instruction</x-row-action>
                                                <x-row-action :href="route('bpa.inspection', ['file' => $a->id])"><x-slot:icon><i data-lucide="camera" class="h-4 w-4"></i></x-slot:icon> File inspection report</x-row-action>
                                            @endif
                                            @if ($tab === 'reports' && $report)
                                                <button type="button" class="{{ $menuBtn }}"
                                                        @click.stop="review = @js(['action' => route('bpa.zonal.review', $a), 'bp' => $a->bp_file_no, 'officer' => $report->officer_name, 'rec' => $report->recommendation, 'canApprove' => $report->recommendation === $approveRec, 'remarks' => $report->remarks, 'report' => route('bpa.inspection.show', $report)]); decision = ''; open = false">
                                                    <i data-lucide="gavel" class="h-4 w-4 text-slate-400"></i> Review report
                                                </button>
                                                <x-row-action :href="route('bpa.inspection.show', $report)"><x-slot:icon><i data-lucide="clipboard-list" class="h-4 w-4"></i></x-slot:icon> Open report</x-row-action>
                                            @endif
                                            <x-row-action :href="route('bpa.applications.show', $a)"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> Open application</x-row-action>
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($files->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">{{ $files->links() }}</div>
                @endif
            @endif
        </x-ui.panel>

        {{-- Assignment form (like KLAES Instruction to Surveyor) --}}
        <div x-show="assign" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="assign = null">
            <form method="POST" :action="assign?.action" @click.outside="assign = null" class="w-full max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <div class="flex items-start justify-between">
                    <div><h2 class="text-lg font-bold text-slate-900 dark:text-white">Instruction to Inspect</h2><p class="text-sm text-slate-500 dark:text-slate-400">Zonal Office assignment</p></div>
                    <button type="button" @click="assign = null" class="text-xl leading-none text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close">&times;</button>
                </div>
                <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2 dark:bg-slate-800/60">
                    <div><dt class="{{ $label }}">Instruction No.</dt><dd class="text-slate-500 dark:text-slate-400">Given on save</dd></div>
                    <div><dt class="{{ $label }}">Date</dt><dd>{{ now()->format('d M Y') }}</dd></div>
                    <div><dt class="{{ $label }}">File Number</dt><dd class="font-mono" x-text="assign?.bp"></dd></div>
                    <div><dt class="{{ $label }}">File Title</dt><dd x-text="assign?.title"></dd></div>
                    <div><dt class="{{ $label }}">Plot Number</dt><dd x-text="assign?.plot || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Job Location</dt><dd x-text="assign?.loc || '—'"></dd></div>
                </dl>
                <div class="relative">
                    <label for="za-off" class="{{ $label }}">Inspecting officer <span class="text-rose-500">*</span></label>
                    <input type="hidden" name="officer_id" :value="officer?.id">
                    <template x-if="officer"><div class="flex items-center justify-between rounded-lg bg-violet-50 px-3 py-2 text-sm dark:bg-violet-500/10"><span><span class="font-semibold" x-text="officer.name"></span> <span class="text-xs text-slate-500 dark:text-slate-400" x-text="officer.rank || ''"></span></span><button type="button" @click.stop="officer = null" class="text-xs text-violet-700 hover:underline dark:text-violet-300">Change</button></div></template>
                    <div x-show="! officer">
                        <input id="za-off" type="search" x-model="officerQ" @input.debounce.350ms="findOfficer()" placeholder="Search staff by name or username" autocomplete="off" class="{{ $field }}">
                        <ul x-show="officers.length" class="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <template x-for="o in officers" :key="o.id"><li><button type="button" @click.stop="officer = o; officers = []" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><span x-text="o.name"></span> <span class="text-xs text-slate-500" x-text="o.rank || ''"></span></button></li></template>
                        </ul>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">KAMMA's zonal officer list is still to come; any active KLAES staff account can be picked for now.</p>
                    </div>
                </div>
                <fieldset>
                    <legend class="{{ $label }}">Scope <span class="text-rose-500">*</span></legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($scopes as $s)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"><input type="checkbox" name="scope[]" value="{{ $s }}" checked class="rounded text-violet-600 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800"> {{ $s }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="za-due" class="{{ $label }}">Report due by <span class="text-rose-500">*</span></label><input id="za-due" type="date" name="due_date" required min="{{ now()->toDateString() }}" value="{{ now()->addWeekdays(7)->toDateString() }}" class="{{ $field }}"></div>
                    <div><span class="{{ $label }}">Issued by</span><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/60">{{ auth()->user()->name ?? '—' }}</div></div>
                </div>
                <div><label for="za-ins" class="{{ $label }}">Instructions</label><textarea id="za-ins" name="instructions" rows="3" maxlength="3000" class="{{ $field }}" placeholder="Sir/Madam, you are required to visit the site and inspect the development against the approved drawings…"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="assign = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button :disabled="! officer" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50">Assign officer</button>
                </div>
            </form>
        </div>

        {{-- Report review --}}
        <div x-show="review" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="review = null">
            <form method="POST" :action="review?.action" @click.outside="review = null" class="w-full max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <div class="flex items-start justify-between">
                    <div><h2 class="text-lg font-bold text-slate-900 dark:text-white">Review inspection report</h2><p class="font-mono text-sm text-slate-500 dark:text-slate-400" x-text="review?.bp"></p></div>
                    <button type="button" @click="review = null" class="text-xl leading-none text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close">&times;</button>
                </div>
                <dl class="space-y-2 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">
                    <div><dt class="{{ $label }}">Inspecting officer</dt><dd x-text="review?.officer"></dd></div>
                    <div><dt class="{{ $label }}">Officer's recommendation</dt><dd class="font-semibold" x-text="review?.rec"></dd></div>
                    <div x-show="review?.remarks"><dt class="{{ $label }}">Officer's remarks</dt><dd class="whitespace-pre-line" x-text="review?.remarks"></dd></div>
                    <a :href="review?.report" class="inline-flex items-center gap-1 text-sm font-semibold text-violet-700 hover:underline dark:text-violet-300"><i data-lucide="external-link" class="h-4 w-4"></i> Read the full report and photos</a>
                </dl>
                <fieldset class="space-y-2">
                    <legend class="{{ $label }}">Decision <span class="text-rose-500">*</span></legend>
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700" :class="! review?.canApprove && 'opacity-50'">
                        <input type="radio" name="decision" value="approve" x-model="decision" :disabled="! review?.canApprove" class="mt-0.5 text-violet-600 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800">
                        <span><span class="font-semibold">Approve report</span>: the file goes to Final Conveyance. <span x-show="! review?.canApprove" class="block text-xs text-slate-500 dark:text-slate-400">Only when the officer recommends approval.</span></span>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                        <input type="radio" name="decision" value="return" x-model="decision" class="mt-0.5 text-violet-600 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800">
                        <span><span class="font-semibold">Return to officer</span>: inspect again or correct the report.</span>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                        <input type="radio" name="decision" value="refer" x-model="decision" class="mt-0.5 text-violet-600 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800">
                        <span><span class="font-semibold">Refer back to the Deputy Director (Arch.)</span>: e.g. a contravention, or the plans do not match the site.</span>
                    </label>
                </fieldset>
                <div><label for="rv-rem" class="{{ $label }}">Remarks <span x-show="decision && decision !== 'approve'" class="text-rose-500">*</span></label><textarea id="rv-rem" name="remarks" rows="3" maxlength="2000" :required="decision && decision !== 'approve'" class="{{ $field }}"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="review = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button :disabled="! decision" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50">Save decision</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
