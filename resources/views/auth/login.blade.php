<x-guest-layout>
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="username" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Username') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </span>
                <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="{{ __('Your KLAES username') }}" required autofocus autocomplete="username" class="block w-full rounded-lg border-slate-300 py-2.5 pl-11 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:placeholder:text-slate-500 dark:text-slate-100">
            </div>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Password') }}</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <input id="password" :type="show ? 'text' : 'password'" name="password" placeholder="Enter your password" required autocomplete="current-password" class="block w-full rounded-lg border-slate-300 py-2.5 pl-11 pr-11 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:placeholder:text-slate-500 dark:text-slate-100">
                <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300">
                    <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center gap-2 py-3 text-sm">
            {{ __('Sign in') }}
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </x-primary-button>
    </form>

    <p class="mt-7 border-t border-slate-100 pt-6 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
        {{ __('No account, or forgot your password? Ask your KLAES administrator.') }}
    </p>
</x-guest-layout>
