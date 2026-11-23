{{-- Where a tracked file or mail is: in a unit (green) or in transit to one (amber); urgent and overdue are flagged. --}}
@props(['tracker'])

<span class="inline-flex flex-wrap items-center gap-1">
    @if ($tracker->status === 'in_transit')
        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/30">
            In transit to {{ $tracker->toUnit?->name }}
        </span>
    @else
        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30">
            In {{ $tracker->currentUnit?->name }}
        </span>
    @endif
    @if ($tracker->priority === 'High')
        <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30">High priority</span>
    @endif
    @if ($tracker->isOverdue())
        <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30">Overdue {{ $tracker->due_on->format('d M') }}</span>
    @endif
</span>
