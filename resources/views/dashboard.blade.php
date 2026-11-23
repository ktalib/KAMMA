<x-app-layout title="Dashboard">
    <div class="space-y-6">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-700 px-6 py-8 text-white shadow-lg shadow-slate-300/60 sm:px-8 dark:shadow-none">
            <div class="absolute -right-10 -top-16 h-56 w-56 rounded-full bg-indigo-500/20 blur-2xl"></div>
            <div class="absolute -bottom-24 right-32 h-48 w-48 rounded-full bg-sky-400/10 blur-2xl"></div>
            <div class="relative">
                <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-indigo-300">Overview</p>
                <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">{{ __('Welcome back,') }} {{ Auth::user()->name }}</h1>
                <p class="mt-2 max-w-xl text-sm text-slate-300">{{ __("You're logged in. Here is what is happening across your workspace today.") }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-600 dark:bg-emerald-500/10">+8%</span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">24</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Total agents') }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/10">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-600 dark:bg-emerald-500/10">+12%</span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">128</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Active clients') }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-950 dark:text-slate-400">12</span>
                </div>
                <p class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">12</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Generated reports') }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                </div>
                <p class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Operational') }}</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('System status') }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent activity') }}</h2>
                </div>
                <div class="divide-y divide-slate-100 px-6 dark:divide-slate-800">
                    <div class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ __('New agent registered') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Amina Hassan joined the team') }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ __('2h ago') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ __('Monthly report ready') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('November summary is available') }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ __('5h ago') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ __('Client profile updated') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Contact details were changed') }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ __('Yesterday') }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Quick actions') }}</h2>
                </div>
                <div class="grid gap-3 p-6 sm:grid-cols-2">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-800 dark:text-slate-300">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        {{ __('Edit profile') }}
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-800 dark:text-slate-300">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        {{ __('Invite agent') }}
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-800 dark:text-slate-300">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        {{ __('View reports') }}
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-800 dark:text-slate-300">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.603-.444.966a4.226 4.226 0 0 1 0 .455c.006.365.151.727.444.966l1.003.828a1.125 1.125 0 0 1 .26 1.43l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.602.444-.966a4.226 4.226 0 0 1 0-.455c-.007-.365-.151-.727-.444-.966l-1.003-.828a1.125 1.125 0 0 1-.26-1.43l1.296-2.247a1.125 1.125 0 0 1 1.37-.49l1.217.456c.355.133.75.072 1.075-.124a6.52 6.52 0 0 1 .22-.128c.332-.183.582-.495.644-.869l.213-1.281Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        {{ __('Settings') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
