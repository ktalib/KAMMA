{{-- One figure card: title, value, hint, optional icon. --}}
@props(['title', 'value', 'hint' => null, 'icon' => null, 'tone' => 'blue', 'href' => null])

@php
    $tones = [
        'blue' => ['text-blue-700 dark:text-blue-300', 'border-blue-100 dark:border-blue-500/20', 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300'],
        'emerald' => ['text-emerald-700 dark:text-emerald-300', 'border-emerald-100 dark:border-emerald-500/20', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'],
        'amber' => ['text-amber-700 dark:text-amber-300', 'border-amber-100 dark:border-amber-500/20', 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'],
        'violet' => ['text-violet-700 dark:text-violet-300', 'border-violet-100 dark:border-violet-500/20', 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300'],
        'rose' => ['text-rose-700 dark:text-rose-300', 'border-rose-100 dark:border-rose-500/20', 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'],
        'cyan' => ['text-cyan-700 dark:text-cyan-300', 'border-cyan-100 dark:border-cyan-500/20', 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-300'],
        'slate' => ['text-slate-600 dark:text-slate-300', 'border-slate-200 dark:border-slate-700', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
    ];
    [$ink, $border, $chip] = $tones[$tone] ?? $tones['blue'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "block rounded-2xl border bg-white p-5 shadow-sm transition dark:bg-slate-900 {$border}".($href ? ' hover:-translate-y-0.5 hover:shadow-md' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-semibold {{ $ink }}">{{ $title }}</p>
        @if ($icon)
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span>
        @endif
    </div>
    <p class="mt-1 text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</{{ $tag }}>
