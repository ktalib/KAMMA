{{-- A white card with an optional heading row (title, subtitle, slot "actions"). --}}
@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($title)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['p-6' => $padded])>
        {{ $slot }}
    </div>
</section>
