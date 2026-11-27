{{-- MD KAMMA approvals (KLAES PP Director: Planning Recommendation Approval, Master JSI Approval and the Physical Planning
     Memo approval; Director of Physical Planning = MD KAMMA). $type "report" = KSI reports, "memo" = KAMMA Memos.
     Tabs Awaiting / Approved / Declined; S/N by row order; the decision opens in a pop-up from the three-dots menu. --}}
@php
    $isMemo = $type === 'memo';
    $title = $isMemo ? 'KAMMA Memo Approval' : ($kind === 'master' ? 'Master KSI Approval' : 'Planning Recommendation Approval');
    $section = 'KAMMA › MD KAMMA'.($kind === 'master' ? '' : ' › '.$k['label']);
    $th = 'whitespace-nowrap px-4 py-3';
    $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $params = $kind === 'master' ? [] : ['kind' => $kind];
    $reasonField = $isMemo ? 'md_recommendation' : 'remarks';
    $badge = fn (?string $s) => match ($s) {
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'declined' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
        default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    };
    $reportUrl = fn ($r) => $r ? \App\Http\Controllers\Kamma\KsiController::showUrl($r) : null;
    // After a failed save the pop-up reopens on the same row (URL rebuilt here, never taken from the request).
    $reopenId = $errors->any() ? (int) old('row_id') : 0;
    $reopen = $reopenId > 0 ? [
        'id' => $reopenId,
        'url' => $isMemo ? route('kamma.approvals.decide-memo', $reopenId) : route('kamma.approvals.decide', $reopenId),
        'label' => (string) old('row_label'),
    ] : null;
@endphp

<x-app-layout :title="$title">
    <div class="space-y-6"
         x-data="{
            pick: @js($reopen), decision: @js(old('decision', 'approved')),
            openDecision(row, d) { this.pick = row; this.decision = d; this.$nextTick(() => document.getElementById('md-reason')?.focus()); },
         }"
         @keydown.escape.window="pick = null">
        <x-ui.page-head icon="stamp" :title="$title" :section="$section" tone="emerald"
                        :subtitle="$isMemo ? 'The planner\'s memo on an approved Planning Recommendation, for the MD KAMMA\'s recommendation.' : 'KSI reports sent for the MD KAMMA\'s approval. A declined report needs the reason.'" />

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif

        <x-tabs :tabs="['awaiting' => ['Awaiting', $counts['awaiting']], 'approved' => ['Approved', $counts['approved']], 'declined' => ['Declined', $counts['declined']]]"
                :active="$tab" :route="$route" :query="$params + array_filter(['q' => $q])" />

        <x-ui.panel :title="$title.' · '.ucfirst($tab)" :subtitle="$rows->total() ? 'Showing '.$rows->firstItem().'–'.$rows->lastItem().' of '.$rows->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="relative">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="{{ $isMemo ? 'Memo no., file no., applicant…' : 'File no., applicant, plot, district…' }}" class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </x-slot:actions>

            @if ($rows->isEmpty())
                <x-ui.empty icon="stamp" :title="$q !== '' ? 'Nothing matches' : ($tab === 'awaiting' ? 'Nothing awaiting the MD KAMMA' : 'None '.$tab.' yet')">
                    {{ $isMemo ? 'Memos appear here once the planner raises them on an approved Planning Recommendation.' : 'KSI reports appear here when the field officer files them.' }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th>
                                @if ($isMemo)<th class="{{ $th }}">Memo No.</th>@endif
                                <th class="{{ $th }}">File No.</th>
                                <th class="{{ $th }}">Applicant</th>
                                @if ($kind === 'master')<th class="{{ $th }}">Post-CofO</th>@endif
                                <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th>
                                @if ($isMemo)
                                    <th class="{{ $th }}">Planner's recommendation</th><th class="{{ $th }}">Raised</th>
                                @else
                                    <th class="{{ $th }}">Inspected</th><th class="{{ $th }}">Compliance</th><th class="{{ $th }} text-right">Recommended area</th>
                                @endif
                                <th class="{{ $th }}">Status</th>
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $row)
                                @php
                                    $r = $isMemo ? $row->report : $row;
                                    $status = $isMemo ? $row->status : ($row->md_decision ?? 'awaiting');
                                    $pending = $isMemo ? $row->status === 'submitted' : $row->md_decision === null;
                                    $js = [
                                        'id' => $row->id,
                                        'url' => $isMemo ? route('kamma.approvals.decide-memo', $row->id) : route('kamma.approvals.decide', $row->id),
                                        'label' => ($isMemo ? $row->memo_no.' · ' : '').$row->file_number.' · '.$row->applicant_name,
                                    ];
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $rows->firstItem() + $loop->index }}</td>
                                    @if ($isMemo)<td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold">{{ $row->memo_no }}</td>@endif
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $row->file_number }}</td>
                                    <td class="px-4 py-3">{{ $row->applicant_name ?: '—' }}</td>
                                    @if ($kind === 'master')<td class="whitespace-nowrap px-4 py-3">{{ $row->post_cofo_type ?: '—' }}</td>@endif
                                    <td class="px-4 py-3">{{ $r?->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->district ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->lga ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->location ?: '—' }}</td>
                                    @if ($isMemo)
                                        <td class="max-w-xs px-4 py-3 text-slate-600 dark:text-slate-300"><span class="line-clamp-2">{{ $row->planner_recommendation }}</span></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $row->planner_recommendation_date?->format('d M Y') }}</td>
                                    @else
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $row->inspection_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $row->compliance_status ?: '—' }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ $row->recommended_site_area !== null ? number_format((float) $row->recommended_site_area, 2).' m²' : '—' }}</td>
                                    @endif
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($status) }}">{{ ucfirst($status) }}</span>
                                        @if (! $isMemo && $row->md_decided_at)
                                            <span class="mt-0.5 block text-xs text-slate-500">{{ $row->md_decided_by_name }} · {{ $row->md_decided_at->format('d M Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            @if ($pending)
                                                <button type="button" @click.stop="openDecision(@js($js), 'approved'); open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="check-circle-2" class="h-4 w-4 text-emerald-600"></i> Approve</button>
                                                <button type="button" @click.stop="openDecision(@js($js), 'declined'); open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="x-circle" class="h-4 w-4 text-rose-600"></i> Decline</button>
                                            @endif
                                            @if ($isMemo)
                                                <x-row-action :href="route('kamma.memos.show', [$kind, $row->id])"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> View memo</x-row-action>
                                            @endif
                                            @if ($r)
                                                <x-row-action :href="$reportUrl($r)"><x-slot:icon><i data-lucide="clipboard-check" class="h-4 w-4"></i></x-slot:icon> View KSI report</x-row-action>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-6 py-3 dark:border-slate-800">{{ $rows->links() }}</div>
            @endif
        </x-ui.panel>

        {{-- Decision pop-up --}}
        <div x-show="pick" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/50 p-4 sm:p-10" role="dialog" aria-modal="true" aria-labelledby="md-title">
            <form method="POST" :action="pick ? pick.url : ''" @click.outside="pick = null" class="w-full max-w-lg rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                @csrf
                <input type="hidden" name="decision" :value="decision">
                <input type="hidden" name="row_id" :value="pick ? pick.id : ''">
                <input type="hidden" name="row_label" :value="pick ? pick.label : ''">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 id="md-title" class="text-base font-bold text-slate-900 dark:text-white" x-text="decision === 'approved' ? 'Approve' : 'Decline'"></h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400" x-text="pick ? pick.label : ''"></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="flex gap-2">
                        <button type="button" @click="decision = 'approved'" :class="decision === 'approved' ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-slate-600 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1">Approve</button>
                        <button type="button" @click="decision = 'declined'" :class="decision === 'declined' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-white text-slate-600 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700'" class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1">Decline</button>
                    </div>
                    <div>
                        <label for="md-reason" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">
                            {{ $isMemo ? "MD KAMMA's recommendation" : 'Remarks' }}
                            <span x-show="decision === 'declined'" class="text-rose-500">* (reason)</span>
                        </label>
                        <textarea id="md-reason" name="{{ $reasonField }}" rows="4" maxlength="{{ $isMemo ? 5000 : 2000 }}" :required="decision === 'declined'" class="{{ $field }}">{{ old($reasonField) }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" @click="pick = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :class="decision === 'approved' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500'">Save decision</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
