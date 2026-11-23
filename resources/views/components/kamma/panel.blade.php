{{-- A white / dark card with a header row. --}}
@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['p-5' => $padded])>
        {{ $slot }}
    </div>
</section>
