{{-- One item in <x-row-actions>: an icon (slot "icon") and a label. --}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800']) }}>
    @isset($icon)
        <span class="h-4 w-4 shrink-0 text-slate-400">{{ $icon }}</span>
    @endisset
    <span>{{ $slot }}</span>
</a>
