{{-- One level of the sidebar menu (recursive). Groups open and close; the branch holding the current page starts open.
     Items not built yet are greyed and open the "not built yet" page. --}}
@props(['items', 'depth' => 0])

<ul @class(['space-y-0.5', 'ml-3 border-l border-slate-200 pl-2 dark:border-slate-800' => $depth > 0])>
    @foreach ($items as $item)
        <li @if ($item['children']) x-data="{ open: @js($item['active']) }" @endif>
            @if ($item['children'])
                <button type="button" @click="open = ! open" :aria-expanded="open"
                        @class([
                            'group flex w-full items-center gap-3 rounded-lg px-3 text-left transition-colors duration-150',
                            'py-2.5 text-sm font-medium' => $depth === 0,
                            'py-1.5 text-[13px]' => $depth > 0,
                            'text-slate-900 dark:text-white' => $item['active'],
                            'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' => ! $item['active'] && $item['built'],
                            'text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300' => ! $item['active'] && ! $item['built'],
                        ])>
                    @if ($item['icon'])
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                    @endif
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-150 dark:text-slate-500" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5">
                    <x-layout.menu-items :items="$item['children']" :depth="$depth + 1" />
                </div>
            @else
                <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 transition-colors duration-150',
                       'py-2.5 text-sm font-medium' => $depth === 0,
                       'py-1.5 text-[13px]' => $depth > 0,
                       'bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-600 dark:text-white dark:shadow-lg dark:shadow-indigo-950/40' => $item['active'],
                       'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' => ! $item['active'] && $item['built'],
                       'text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300' => ! $item['active'] && ! $item['built'],
                   ])>
                    @if ($item['icon'])
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                    @endif
                    <span class="min-w-0 flex-1 truncate" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                    @unless ($item['built'])
                        <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-400 dark:bg-slate-800 dark:text-slate-500">Soon</span>
                    @endunless
                </a>
            @endif
        </li>
    @endforeach
</ul>
