{{-- A KPI card. --}}
@props(['label', 'value' => 0, 'accent' => 'slate', 'icon' => null, 'hint' => null, 'href' => null])

@php
    $tones = [
        'slate' => 'text-slate-900 dark:text-slate-100',
        'emerald' => 'text-emerald-700 dark:text-emerald-400',
        'teal' => 'text-teal-700 dark:text-teal-400',
        'blue' => 'text-blue-700 dark:text-blue-400',
        'indigo' => 'text-indigo-700 dark:text-indigo-400',
        'violet' => 'text-violet-700 dark:text-violet-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'rose' => 'text-rose-700 dark:text-rose-400',
        'lime' => 'text-lime-700 dark:text-lime-400',
        'sky' => 'text-sky-700 dark:text-sky-400',
    ];
    $iconTones = [
        'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        'teal' => 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        'lime' => 'bg-lime-50 text-lime-600 dark:bg-lime-500/10 dark:text-lime-400',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-900'.($href ? ' hover:border-slate-300 hover:shadow dark:hover:border-slate-700' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconTones[$accent] ?? $iconTones['slate'] }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span>
        @endif
    </div>
    <p class="mt-1 text-2xl font-black {{ $tones[$accent] ?? $tones['slate'] }}">{{ is_numeric($value) ? number_format((float) $value) : $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</{{ $tag }}>
