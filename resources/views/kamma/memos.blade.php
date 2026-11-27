{{-- KAMMA Memo (KLAES Physical Planning Memo / "ST Applications for Memo"), per kind. Tab "Awaiting memo": Planning
     Recommendations the MD KAMMA approved with no memo yet (Generate Memo pop-up). Tab "Memos": memos raised and their
     MD KAMMA status. S/N by row order; row actions in the three-dots menu. --}}
@php
    $th = 'whitespace-nowrap px-4 py-3';
    $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
    $badge = fn (?string $s) => match ($s) {
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'declined' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
        default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    };
    $title = $kind === 'st' ? 'ST Applications for Memo' : $k['short'].' Memo';
    $reopenId = $errors->any() ? (int) old('ksi_report_id') : 0;
    $reopen = $reopenId > 0 ? ['id' => $reopenId, 'label' => (string) old('row_label')] : null;
@endphp

<x-app-layout :title="$title">
    <div class="space-y-6"
         x-data="{ pick: @js($reopen), openMemo(row) { this.pick = row; this.$nextTick(() => document.getElementById('memo-text')?.focus()); } }"
         @keydown.escape.window="pick = null">
        <x-ui.page-head icon="file-text" :title="$title" :section="'KAMMA › '.$k['label']" tone="violet"
                        subtitle="The planner's memo on a Planning Recommendation the MD KAMMA approved. Each memo goes to the MD KAMMA for approval." />

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                @foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif

        <x-tabs :tabs="['awaiting' => ['Awaiting memo', $counts['awaiting']], 'memos' => ['Memos', $counts['memos']]]" :active="$tab" route="kamma.memos.index" :query="['kind' => $kind] + array_filter(['q' => $q])" />

        <x-ui.panel :title="$tab === 'awaiting' ? 'Approved Planning Recommendations without a memo' : 'Memos raised'" :subtitle="$rows->total() ? 'Showing '.$rows->firstItem().'–'.$rows->lastItem().' of '.$rows->total() : null" :padded="false">
            <x-slot:actions>
                <form method="GET" class="relative">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ $q }}" placeholder="{{ $tab === 'awaiting' ? 'File no., applicant, plot, district…' : 'Memo no., file no., applicant…' }}" class="w-72 rounded-lg border-slate-300 py-2 pl-9 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </form>
            </x-slot:actions>

            @if ($rows->isEmpty())
                <x-ui.empty icon="file-text" :title="$q !== '' ? 'Nothing matches' : ($tab === 'awaiting' ? 'No approved Planning Recommendation is waiting for a memo' : 'No memos yet')">
                    {{ $tab === 'awaiting' ? 'A KSI report appears here once the MD KAMMA approves it.' : 'Raise a memo from the "Awaiting memo" tab.' }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="{{ $th }}">S/N</th>
                                @if ($tab === 'memos')<th class="{{ $th }}">Memo No.</th>@endif
                                <th class="{{ $th }}">File No.</th><th class="{{ $th }}">Applicant</th>
                                <th class="{{ $th }}">Plot No.</th><th class="{{ $th }}">District</th><th class="{{ $th }}">LGA</th><th class="{{ $th }}">Location</th>
                                @if ($tab === 'awaiting')
                                    <th class="{{ $th }}">Approved by MD</th>
                                @else
                                    <th class="{{ $th }}">Raised</th><th class="{{ $th }}">Status</th>
                                @endif
                                <th class="sticky right-0 bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($rows as $row)
                                @php $r = $tab === 'awaiting' ? $row : $row->report; @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3 text-slate-500">{{ $rows->firstItem() + $loop->index }}</td>
                                    @if ($tab === 'memos')<td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold">{{ $row->memo_no }}</td>@endif
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $row->file_number }}</td>
                                    <td class="px-4 py-3">{{ $row->applicant_name ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->plot_number ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->district ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->lga ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $r?->location ?: '—' }}</td>
                                    @if ($tab === 'awaiting')
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $row->md_decided_by_name }}<span class="block text-xs">{{ $row->md_decided_at?->format('d M Y') }}</span></td>
                                    @else
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $row->planner_recommendation_date?->format('d M Y') }}<span class="block text-xs">{{ $row->created_by_name }}</span></td>
                                        <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($row->status) }}">{{ $row->status === 'submitted' ? 'With MD KAMMA' : ucfirst($row->status) }}</span></td>
                                    @endif
                                    <td class="sticky right-0 bg-white px-4 py-2 text-right dark:bg-slate-900">
                                        <x-row-actions>
                                            @if ($tab === 'awaiting')
                                                <button type="button" @click.stop="openMemo(@js(['id' => $row->id, 'label' => $row->file_number.' · '.$row->applicant_name])); open = false" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i data-lucide="file-plus-2" class="h-4 w-4 text-violet-600"></i> Generate memo</button>
                                            @else
                                                <x-row-action :href="route('kamma.memos.show', [$kind, $row->id])"><x-slot:icon><i data-lucide="file-text" class="h-4 w-4"></i></x-slot:icon> View / print memo</x-row-action>
                                            @endif
                                            @if ($r)
                                                <x-row-action :href="\App\Http\Controllers\Kamma\KsiController::showUrl($r)"><x-slot:icon><i data-lucide="clipboard-check" class="h-4 w-4"></i></x-slot:icon> View KSI report</x-row-action>
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

        {{-- Generate memo pop-up --}}
        <div x-show="pick" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/50 p-4 sm:p-10" role="dialog" aria-modal="true" aria-labelledby="memo-title">
            <form method="POST" action="{{ route('kamma.memos.store', $kind) }}" @click.outside="pick = null" class="w-full max-w-xl rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                @csrf
                <input type="hidden" name="ksi_report_id" :value="pick ? pick.id : ''">
                <input type="hidden" name="row_label" :value="pick ? pick.label : ''">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 id="memo-title" class="text-base font-bold text-slate-900 dark:text-white">Generate KAMMA Memo</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400" x-text="pick ? pick.label : ''"></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="memo-text" class="{{ $lbl }}">Planner's recommendation <span class="text-rose-500">*</span></label>
                        <textarea id="memo-text" name="planner_recommendation" rows="6" maxlength="5000" required class="{{ $field }}">{{ old('planner_recommendation') }}</textarea>
                    </div>
                    <div class="sm:w-1/2">
                        <label for="memo-date" class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label>
                        <input id="memo-date" type="date" name="planner_recommendation_date" value="{{ old('planner_recommendation_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="{{ $field }}">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">The memo number (KMA-PM/{{ now()->format('Y') }}/…) is given on save, and the memo goes to the MD KAMMA.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                    <button type="button" @click="pick = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Generate memo</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
