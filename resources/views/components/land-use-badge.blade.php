{{-- Land-use check result: Match (green), Mismatch (red, flagged), Not checked (grey). --}}
@props(['check'])

@php
    $classes = match ($check) {
        'Match' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30',
        'Mismatch' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-500/30',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $check === 'Mismatch' ? 'Flagged: mismatch' : $check }}
</span>
