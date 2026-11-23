{{-- Empty state: an icon, a title and a line of explanation (slot). --}}
@props(['icon' => 'inbox', 'title' => 'Nothing to show yet'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"><i data-lucide="{{ $icon }}" class="h-6 w-6"></i></span>
    <p class="mt-3 font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
    @if (trim($slot) !== '')
        <p class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ $slot }}</p>
    @endif
</div>
