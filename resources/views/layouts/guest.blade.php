<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-theme-init />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>KLAES-METRO - Login</title>
        <x-favicon />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
            .hide-scrollbar::-webkit-scrollbar { display: none; }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased dark:text-gray-100">
        <x-layout.preloader :delay="350" />

        {{-- Laid out like the KLAES-CORE login, mirrored: the moving KLAES 2.0 text on the left, the sign-in form on the right. --}}
        <div class="flex h-screen overflow-hidden bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 dark:from-gray-950 dark:via-gray-900 dark:to-gray-900">
            <div id="klaes-suite-scroll" class="hide-scrollbar hidden h-screen overflow-y-auto bg-gray-100 md:block md:w-1/2 dark:bg-gray-900">
                @include('auth.partials.klaes-suite')
            </div>

            <div class="relative flex h-screen w-full flex-col items-center justify-center overflow-y-auto p-8 md:w-1/2">
                <x-theme-toggle class="absolute right-4 top-4" />

                <div class="w-full max-w-md space-y-8 rounded-xl bg-white p-8 shadow-lg dark:bg-slate-900">
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="relative mb-4 h-32 w-48">
                            <img src="{{ asset('images/logo.png') }}" alt="KAMMA KLAES-METRO logo" class="h-full w-full rounded-lg object-contain">
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Welcome to KLAES-METRO</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Part of the KLAES 2.0 Suite</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        <script>
            // Scroll the KLAES 2.0 text slowly, pause 2 seconds at the bottom, then start again (as on the KLAES-CORE login).
            document.addEventListener('DOMContentLoaded', function () {
                const panel = document.getElementById('klaes-suite-scroll');
                if (!panel || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                const speed = 0.1; // pixels per millisecond
                let last = null;

                function step(now) {
                    if (last === null) last = now;
                    panel.scrollTop += speed * (now - last);
                    last = now;

                    if (panel.scrollHeight - panel.scrollTop - panel.clientHeight < 1) {
                        setTimeout(function () {
                            panel.scrollTop = 0;
                            last = null;
                            requestAnimationFrame(step);
                        }, 2000);
                        return;
                    }

                    requestAnimationFrame(step);
                }

                requestAnimationFrame(step);
            });
        </script>
    </body>
</html>
