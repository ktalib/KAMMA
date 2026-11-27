<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <x-theme-init />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#047857">

        <title>@yield('title', 'Applicant Portal') &mdash; KLAES-METRO</title>
        <x-favicon />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
        <x-layout.preloader :delay="350" />
        @yield('body')

        {{-- Lucide icons, as the KLAES portals load them. --}}
        <script src="https://unpkg.com/lucide@0.429.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
        <script>document.addEventListener('DOMContentLoaded', () => window.lucide && window.lucide.createIcons());</script>
        @stack('scripts')
    </body>
</html>
