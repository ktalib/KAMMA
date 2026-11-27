{{-- Landing page, copied from KLAES-CORE's (klas resources/views/layouts/landing.blade.php): sliding photo carousel
     under the blue-to-green overlay that alternates with a light one every 3 s, the agency logo and name in the middle,
     Login top right and the LAND ADmin logo bottom right. Photos: Kano (user 2026-11-23). Self-contained CSS so it does
     not depend on the compiled app styles. --}}
@php
    $agency = 'KLAES-METRO';
    $agencyName = 'Kano State Metropolitan Area Management Agency';
    $tagline = 'Kano State LAnd ADmin Enterprise System';
    $photos = ['kano-1.webp', 'kano-2.webp', 'kano-3.webp'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $agency }}</title>
    <meta name="description" content="{{ $agency }}: {{ $agencyName }} on the {{ $tagline }}.">
    <meta name="theme-color" content="#006837">
    <x-favicon />
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        body { min-height: 100vh; background: #0b3d2a; }
        .bg { position: fixed; inset: 0; z-index: 0; }
        .carousel { position: relative; width: 100%; height: 100%; overflow: hidden; }
        .carousel-item { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transform: translateX(-100%); transition: all .8s ease-in-out; }
        .carousel-item.active { opacity: 1; transform: translateX(0); }
        .overlay { position: absolute; inset: 0; transition: background 1s ease; }
        .theme1 { background: linear-gradient(to bottom, rgba(0, 120, 212, .658), rgba(0, 104, 55, .8)); }
        .theme2 { background: linear-gradient(to bottom, rgba(255, 255, 255, .39), rgba(247, 247, 247, .322)); }
        .page { position: relative; z-index: 10; display: flex; flex-direction: column; min-height: 100vh; }
        header { display: flex; justify-content: flex-end; padding: 1.5rem 2rem; }
        .login { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.4rem; border-radius: .5rem; background: #006837; color: #fff;
                 font-weight: 600; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,.25); transition: background .3s, transform .3s; }
        .login:hover { background: #15803d; transform: translateY(-1px); }
        .login svg { width: 1.15rem; height: 1.15rem; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1rem 6rem; text-align: center; }
        .logo { width: min(30vw, 220px); min-width: 140px; height: auto; margin: 0 auto 2rem; display: block; filter: drop-shadow(0 4px 12px rgba(0,0,0,.35)); }
        h1 { margin: 0 0 .5rem; font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800; font-style: italic; letter-spacing: -.3px; transition: color 1s ease; }
        .t1 { color: #fff; text-shadow: 0 0 1px #000; -webkit-text-stroke: .5px #000; }
        .t2 { color: #006837; text-shadow: 0 0 1px #006837; -webkit-text-stroke: .5px #006837; }
        .agency, .tagline { display: inline-block; color: #fff; font-weight: 800; text-shadow: 0 1px 3px rgba(0,0,0,.9), 0 0 8px rgba(0,0,0,.5); }
        .agency { font-size: clamp(1rem, 2.4vw, 1.4rem); margin: 0 0 .35rem; }
        .tagline { font-size: clamp(1.05rem, 2.6vw, 1.5rem); line-height: 1.6; margin: 0; }
        footer img { position: absolute; right: 2rem; bottom: 2rem; width: clamp(120px, 12vw, 160px); height: auto; opacity: .85; transition: opacity .3s; }
        footer img:hover { opacity: 1; }
        @media (max-width: 640px) { header { padding: 1rem; } footer img { right: 1rem; bottom: 1rem; } }
        @media (prefers-reduced-motion: reduce) { .carousel-item, .overlay, h1 { transition: none; } }
    </style>
</head>
<body>
    <div class="bg">
        <div id="carousel" class="carousel">
            @foreach ($photos as $photo)
                <img src="{{ asset('images/landing/'.$photo) }}" alt="Kano" class="carousel-item {{ $loop->first ? 'active' : '' }}">
            @endforeach
        </div>
        <div id="overlay" class="overlay theme1"></div>
    </div>

    <div class="page">
        <header>
            @auth
                <a href="{{ route('dashboard') }}" class="login">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7Z"/></svg>
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="login">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-.293.707L12 11.414V15a1 1 0 0 1-.293.707l-2 2A1 1 0 0 1 8 17v-5.586L3.293 6.707A1 1 0 0 1 3 6V3Z" clip-rule="evenodd"/></svg>
                    Login
                </a>
            @endauth
        </header>

        <main>
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="{{ $agency }} logo" class="logo">
                <h1 id="title" class="t1">{{ $agency }}</h1>
                <p class="agency">{{ $agencyName }}</p><br>
                <p class="tagline">{{ $tagline }}</p>
            </div>
        </main>

        <footer>
            <img src="{{ asset('images/lade-logo.png') }}" alt="LAnd ADmin Enterprise System">
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // KLAES: the overlay and title colour alternate every 3 seconds.
            const overlay = document.getElementById('overlay');
            const title = document.getElementById('title');
            let theme = 1;
            setInterval(function () {
                theme = theme === 1 ? 2 : 1;
                overlay.classList.toggle('theme1', theme === 1);
                overlay.classList.toggle('theme2', theme === 2);
                title.classList.toggle('t1', theme === 1);
                title.classList.toggle('t2', theme === 2);
            }, 3000);

            // KLAES: the next photo slides in from the left every 5 seconds.
            const items = Array.from(document.querySelectorAll('#carousel .carousel-item'));
            let current = 0, busy = false;
            items.forEach(function (el, i) { el.style.opacity = i === 0 ? '1' : '0'; el.style.transform = i === 0 ? 'translateX(0)' : 'translateX(-100%)'; });
            function next() {
                if (busy || items.length < 2) return;
                busy = true;
                const nextIndex = (current + 1) % items.length, out = items[current], into = items[nextIndex];
                into.style.transition = 'none'; into.style.opacity = '0'; into.style.transform = 'translateX(-100%)';
                void into.offsetWidth;
                into.style.transition = 'all 0.8s ease-in-out';
                out.style.transform = 'translateX(100%)';
                into.style.opacity = '1'; into.style.transform = 'translateX(0)';
                setTimeout(function () {
                    out.style.transition = 'none'; out.style.opacity = '0'; out.style.transform = 'translateX(-100%)';
                    current = nextIndex; busy = false;
                }, 800);
            }
            setTimeout(function () { setInterval(next, 5000); }, 1000);
        });
    </script>
</body>
</html>
