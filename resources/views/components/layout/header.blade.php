@props(['title' => 'Dashboard'])

<header class="print:hidden sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8 dark:border-slate-800 dark:bg-slate-900/90">
    <button @click="sidebarOpen = true" type="button" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100" aria-label="Open navigation">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</p>
        <p class="hidden text-xs text-slate-500 sm:block dark:text-slate-400">KLAES-METRO · Kano State Metropolitan Area Management Agency</p>
    </div>

    <label class="relative hidden md:block">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>
        <input type="search" placeholder="Search..." class="w-52 rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-indigo-400 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300 dark:focus:bg-slate-900">
    </label>

    <x-theme-toggle />

    <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100" aria-label="Notifications">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-900"></span>
    </button>

    <div class="hidden h-7 w-px bg-slate-200 sm:block dark:bg-slate-700"></div>

    <x-dropdown align="right" width="56">
        <x-slot name="trigger">
            <button type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden max-w-32 truncate font-medium text-slate-700 sm:block dark:text-slate-300">{{ Auth::user()->name }}</span>
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</p>
            </div>

            <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</header>
