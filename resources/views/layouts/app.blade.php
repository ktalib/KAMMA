@props(['title' => 'Dashboard', 'bare' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-favicon />
        <x-theme-init />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} &mdash; {{ config('app.name', 'KAMMA') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200" x-data="{ sidebarOpen: false }">
        <x-layout.preloader />

        <div class="min-h-screen lg:pl-64 print:pl-0">
            <x-layout.sidebar />

            <div class="flex min-h-screen flex-col">
                <x-layout.header :title="$title" />

                @if (\App\Support\Preview::active())
                    {{-- UI first: this screen's KAMMA tables are not created yet (migrations written, not run). --}}
                    <div class="print:hidden border-b border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800 sm:px-6 lg:px-8 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300" role="status">
                        <span class="font-semibold">Preview.</span>
                        KAMMA's tables for this screen are not created yet, so lists are empty and saves answer "Saved (preview)". KLAES data shown here is real.
                    </div>
                @endif

                @isset($header)
                    <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <main class="flex-1">
                    @if ($bare)
                        {{-- Screens copied from KLAES bring their own full-width band and container. --}}
                        {{ $slot }}
                    @else
                        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                            {{ $slot }}
                        </div>
                    @endif
                </main>

                <x-layout.footer />
            </div>
        </div>

        {{-- Lucide icons, as KLAES loads them, for screens copied from KLAES (data-lucide markup). --}}
        <script src="https://unpkg.com/lucide@0.429.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
        <script>document.addEventListener('DOMContentLoaded', () => window.lucide && window.lucide.createIcons());</script>
        @stack('scripts')
    </body>
</html>
