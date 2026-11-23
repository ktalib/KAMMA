{{-- Marks a screen whose backend is not built yet (user 2026-11-23: UI first, backend next). --}}
@props(['what' => 'Saving on this screen'])

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300']) }}>
    <i data-lucide="flask-conical" class="mt-0.5 h-4 w-4 shrink-0"></i>
    <p>
        <span class="font-semibold">Preview.</span>
        {{ $what }} is not connected yet: a save answers "saved (preview)" and stores nothing.
        {{ $slot }}
    </p>
</div>
