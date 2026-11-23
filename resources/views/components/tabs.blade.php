{{-- Awaiting / done tabs over a list (the KLAES approval-tab pattern). $tabs: key => [label, count]. --}}
@props(['tabs', 'active', 'route', 'query' => []])

<div {{ $attributes->merge(['class' => 'flex gap-1 border-b border-slate-200 dark:border-slate-800']) }} role="tablist">
    @foreach ($tabs as $key => [$label, $count])
        <a href="{{ route($route, array_merge($query, ['tab' => $key])) }}" role="tab" aria-selected="{{ $active === $key ? 'true' : 'false' }}"
           class="-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium {{ $active === $key
                ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' }}">
            {{ $label }}
            <span class="rounded-full px-2 py-0.5 text-xs {{ $active === $key
                ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $count }}</span>
        </a>
    @endforeach
</div>
