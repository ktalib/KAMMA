{{-- KAMMA page header band (the KLAES coloured page band, Tailwind 3 + dark). Accent is one of the keys below. --}}
@props(['title', 'subtitle' => null, 'icon' => 'layout-dashboard', 'accent' => 'emerald', 'eyebrow' => 'KLAES-METRO'])

@php
    $tones = [
        'emerald' => 'from-emerald-600 to-teal-600 dark:from-emerald-800 dark:to-teal-900',
        'indigo' => 'from-indigo-600 to-violet-600 dark:from-indigo-800 dark:to-violet-900',
        'blue' => 'from-blue-600 to-sky-600 dark:from-blue-800 dark:to-sky-900',
        'lime' => 'from-lime-600 to-green-600 dark:from-lime-800 dark:to-green-900',
        'amber' => 'from-amber-500 to-orange-600 dark:from-amber-700 dark:to-orange-900',
        'rose' => 'from-rose-600 to-pink-600 dark:from-rose-800 dark:to-pink-900',
        'violet' => 'from-violet-600 to-fuchsia-600 dark:from-violet-800 dark:to-fuchsia-900',
        'teal' => 'from-teal-600 to-cyan-600 dark:from-teal-800 dark:to-cyan-900',
        'slate' => 'from-slate-700 to-slate-900 dark:from-slate-800 dark:to-slate-950',
        'purple' => 'from-purple-600 to-indigo-700 dark:from-purple-800 dark:to-indigo-900',
        'sky' => 'from-sky-600 to-blue-700 dark:from-sky-800 dark:to-blue-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-r text-white print:hidden '.($tones[$accent] ?? $tones['emerald'])]) }}>
    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute -bottom-20 right-40 h-40 w-40 rounded-full bg-white/5"></div>
    <div class="relative mx-auto flex max-w-7xl flex-col gap-4 px-4 py-7 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
        <div class="flex min-w-0 items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                <i data-lucide="{{ $icon }}" class="h-6 w-6"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-white/70">{{ $eyebrow }}</p>
                <h1 class="mt-0.5 text-2xl font-bold tracking-tight">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 max-w-3xl text-sm text-white/80">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
