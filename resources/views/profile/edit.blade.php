<x-app-layout title="My account">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('My account') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('You sign in with your KLAES account. To change these details or your password, use KLAES or ask your KLAES administrator.') }}
            </p>

            <dl class="mt-6 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @foreach ([
                    __('Name') => $user->name,
                    __('Username') => $user->username,
                    __('Email') => $user->email,
                    __('Phone number') => $user->phone_number,
                    __('Rank') => $user->rank,
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                        <dd class="col-span-2 break-words text-slate-900 dark:text-slate-100">{{ filled($value) ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</x-app-layout>
