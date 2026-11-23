{{-- The KAMMA sidebar: the full KAMMA menu from the KLAES menu document (config/kamma_menu.php). --}}
<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex print:hidden w-64 flex-col border-r border-slate-200 bg-white text-slate-700 transition-transform dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 duration-300 ease-in-out lg:translate-x-0"
>
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
        <img src="{{ asset('images/logo.png') }}" alt="KAMMA KLAES-METRO logo" class="h-10 w-10 shrink-0 object-contain">
        <div class="min-w-0">
            <div class="truncate text-sm font-semibold tracking-wide text-slate-900 dark:text-white">KLAES-METRO</div>
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-500">KAMMA</div>
        </div>
        <button @click="sidebarOpen = false" type="button" class="ml-auto rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Close navigation">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Main menu">
        <x-layout.menu-items :items="app(\App\Support\SidebarMenu::class)->items()" />
    </nav>

    <a href="{{ route('profile.edit') }}" class="block shrink-0 border-t border-slate-200 p-4 dark:border-slate-800">
        <div class="flex items-center gap-3 rounded-lg bg-slate-100 p-3 transition hover:bg-slate-200 dark:bg-slate-800/70 dark:hover:bg-slate-800">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ Auth::user()->name }}</p>
                <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">My account</p>
            </div>
        </div>
    </a>
</aside>

<div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"></div>
