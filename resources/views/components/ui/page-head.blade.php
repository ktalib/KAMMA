{{-- Page heading card used across the KAMMA screens: gradient icon tile, title, subtitle, actions (slot),
     and the flash message of the last save. Tone names map to full class strings so Tailwind compiles them. --}}
@props(['icon' => 'layout-grid', 'title', 'subtitle' => null, 'tone' => 'indigo', 'section' => null])

@php
    $tiles = [
        'indigo' => 'from-indigo-500 to-indigo-700 shadow-indigo-600/25',
        'blue' => 'from-blue-500 to-blue-700 shadow-blue-600/25',
        'emerald' => 'from-emerald-500 to-emerald-700 shadow-emerald-600/25',
        'teal' => 'from-teal-500 to-teal-700 shadow-teal-600/25',
        'cyan' => 'from-cyan-500 to-cyan-700 shadow-cyan-600/25',
        'amber' => 'from-amber-500 to-amber-600 shadow-amber-600/25',
        'orange' => 'from-orange-500 to-orange-700 shadow-orange-600/25',
        'rose' => 'from-rose-500 to-rose-700 shadow-rose-600/25',
        'violet' => 'from-violet-500 to-violet-700 shadow-violet-600/25',
        'slate' => 'from-slate-500 to-slate-700 shadow-slate-600/25',
        'lime' => 'from-lime-500 to-lime-700 shadow-lime-600/25',
        'sky' => 'from-sky-500 to-sky-700 shadow-sky-600/25',
    ];
@endphp

<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex min-w-0 items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg {{ $tiles[$tone] ?? $tiles['indigo'] }}"><i data-lucide="{{ $icon }}" class="h-6 w-6"></i></span>
            <div class="min-w-0">
                @if ($section)
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $section }}</p>
                @endif
                <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if (trim($slot) !== '')
            <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
        @endif
    </div>

    @if (session('status'))
        <div role="status" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            <i data-lucide="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif
</div>
