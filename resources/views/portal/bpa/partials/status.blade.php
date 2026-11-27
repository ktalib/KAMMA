{{-- Status pill for an application ($label from PortalApplications: Approved, Awaiting payment, Returned for correction, In progress). --}}
@php
    $tone = match ($label) {
        'Approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'Awaiting payment' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'Returned for correction' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        default => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    };
@endphp
<span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $tone }}">{{ $label }}</span>
