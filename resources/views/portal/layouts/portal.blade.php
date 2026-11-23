@extends('portal.layouts.app')

@section('body')
@php
    $applicant = \App\Support\Portal\PortalSession::applicant();
    $recentUpdates = app(\App\Support\Portal\PortalApplications::class)->notifications($applicant)
        ->filter(fn ($e) => $e['at']->gte(now()->subDays(14)))->count();

    // [route, label, icon, routes that also light it up]
    $links = [
        ['portal.dashboard', 'My requests', 'layout-dashboard', ['portal.applications.show']],
        ['portal.applications.create', 'New request', 'file-plus-2', []],
        ['portal.bills', 'Bills', 'receipt', []],
        ['portal.notifications', 'Notifications', 'bell', []],
    ];
    // Phones: the things an applicant does most, along the bottom of the screen.
    $tabs = [
        ['portal.dashboard', 'Home', 'house', ['portal.applications.show']],
        ['portal.applications.create', 'Apply', 'file-plus-2', []],
        ['portal.bills', 'Bills', 'receipt', []],
        ['portal.notifications', 'Updates', 'bell', []],
        ['portal.profile', 'Profile', 'user', []],
    ];
    $navBase = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition';
    $navOn = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
    $navOff = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100';
@endphp

{{-- Copied from the KLAES LAAS portal shell: fixed sidebar on large screens, slide-in sidebar behind the menu button on
     smaller ones. Phones also get a bottom tab bar. --}}
<div x-data="{ menu: false }" @keydown.escape.window="menu = false" class="min-h-screen lg:pl-64">

    <div x-show="menu" x-cloak x-transition.opacity @click="menu = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0 dark:border-slate-800 dark:bg-slate-900"
           aria-label="Portal">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-4 dark:border-slate-800">
            <a href="{{ route('portal.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="KAMMA KLAES-METRO logo" class="h-11 w-11 shrink-0 object-contain">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-extrabold tracking-wide text-slate-900 dark:text-white">KLAES-METRO</span>
                    <span class="block truncate text-[11px] font-semibold text-slate-500 dark:text-slate-400">Applicant Portal</span>
                </span>
            </a>
            <button type="button" @click="menu = false" class="rounded-lg p-2 text-slate-500 lg:hidden dark:text-slate-400">
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                <span class="sr-only">Close menu</span>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500">Menu</p>
            @foreach ($links as [$route, $label, $icon, $also])
                @php $active = request()->routeIs($route, ...$also); @endphp
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif class="{{ $navBase }} {{ $active ? $navOn : $navOff }}">
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                    <span class="flex-1">{{ $label }}</span>
                    @if ($route === 'portal.notifications' && $recentUpdates > 0)
                        <span class="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-black text-white">{{ $recentUpdates }}</span>
                    @endif
                </a>
            @endforeach

            <p class="px-3 pb-2 pt-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500">Account</p>
            @php $active = request()->routeIs('portal.profile*'); @endphp
            <a href="{{ route('portal.profile') }}" @if ($active) aria-current="page" @endif class="{{ $navBase }} {{ $active ? $navOn : $navOff }}">
                <i data-lucide="user" class="h-4 w-4 shrink-0" aria-hidden="true"></i> My profile
            </a>
            <div class="flex items-center justify-between px-3 py-1.5">
                <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Theme</span>
                <x-theme-toggle />
            </div>
        </nav>

        <div class="border-t border-slate-200 p-3 dark:border-slate-800">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                    {{ mb_strtoupper(mb_substr($applicant['name'] ?? '?', 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100">{{ $applicant['name'] ?? '' }}</span>
                    <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $applicant['username'] ?? '' }}</span>
                </span>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100" title="Sign out">
                        <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                        <span class="sr-only">Sign out</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col">
        {{-- Agency identification strip --}}
        <div class="bg-emerald-950 dark:bg-black">
            <p class="px-4 py-2 text-[11px] font-semibold tracking-wide text-emerald-200 sm:px-6">
                An official portal of the <span class="text-white">Kano State Metropolitan Area Management Agency (KAMMA)</span>
            </p>
        </div>

        @if (config('kamma.portal.preview', true))
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-xs font-medium text-amber-900 sm:px-6 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                <i data-lucide="eye" class="mr-1 inline h-3.5 w-3.5 align-[-2px]" aria-hidden="true"></i>
                Preview: the portal screens are ready; saving opens with the backend. Nothing you submit is stored yet.
                @if (! empty($applicant['is_demo'])) You are signed in as the demo applicant. @endif
            </div>
        @endif

        {{-- Top bar: small screens only, where the sidebar is hidden. --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 py-2.5 backdrop-blur lg:hidden dark:border-slate-800 dark:bg-slate-900/95">
            <button type="button" @click="menu = true" class="rounded-lg p-2 text-slate-700 dark:text-slate-200">
                <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
                <span class="sr-only">Open menu</span>
            </button>
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true" class="h-8 w-8 object-contain">
                <span class="text-sm font-extrabold tracking-wide text-slate-900 dark:text-white">KLAES-METRO</span>
            </a>
            <div class="flex items-center">
                <x-theme-toggle />
                <a href="{{ route('portal.notifications') }}" class="relative rounded-lg p-2 text-slate-500 dark:text-slate-400">
                    <i data-lucide="bell" class="h-5 w-5" aria-hidden="true"></i>
                    @if ($recentUpdates > 0)
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                    @endif
                    <span class="sr-only">Notifications</span>
                </a>
            </div>
        </header>

        <main id="portal-main" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-28 pt-6 sm:px-6 sm:pt-8 lg:pb-10">
            @if (session('status'))
                <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                    <p class="text-sm font-medium text-emerald-900 dark:text-emerald-200">{{ session('status') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10">
                    <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                    <p class="text-sm font-medium text-rose-900 dark:text-rose-200">{{ session('error') }}</p>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="hidden border-t border-slate-200 bg-white py-6 lg:block dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 sm:flex-row">
                <p class="text-xs text-slate-500 dark:text-slate-400">&copy; {{ date('Y') }} Kano State Metropolitan Area Management Agency &mdash; KLAES-METRO Applicant Portal. Part of the KLAES 2.0 Suite.</p>
                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">KAMMA never asks for payment through private accounts or agents.</p>
            </div>
        </footer>
    </div>

    {{-- Bottom tab bar: phones and tablets. --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden dark:border-slate-800 dark:bg-slate-900/95" aria-label="Quick menu">
        <ul class="mx-auto grid max-w-lg grid-cols-5">
            @foreach ($tabs as [$route, $label, $icon, $also])
                @php $active = request()->routeIs($route, ...$also); @endphp
                <li>
                    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                       class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-semibold {{ $active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                        <i data-lucide="{{ $icon }}" class="h-5 w-5" aria-hidden="true"></i>
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
@endsection
