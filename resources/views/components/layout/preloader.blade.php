@props(['delay' => 650])

{{-- Agency preloader (user 2026-11-23: the KLAES-METRO logo is the preloader). Plain CSS + JS, so it shows before the
     built assets load and never depends on Alpine. Hides once the page has loaded (at least $delay ms, at most 6 s). --}}
<div id="app-preloader" class="app-preloader" role="status" aria-label="Loading">
    <div class="app-preloader__mark">
        <span class="app-preloader__ring"></span>
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'KLAES-METRO') }}" class="app-preloader__logo">
    </div>
    <div class="app-preloader__name">{{ config('app.name', 'KLAES-METRO') }}</div>
    <div class="app-preloader__dots"><span></span><span></span><span></span></div>
</div>

<style>
    .app-preloader { position: fixed; inset: 0; z-index: 100; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #ffffff; transition: opacity .4s ease; }
    .dark .app-preloader { background: #020617; }
    .app-preloader.is-done { opacity: 0; pointer-events: none; }
    .app-preloader__mark { position: relative; width: 11rem; height: 11rem; display: flex; align-items: center; justify-content: center; }
    .app-preloader__ring { position: absolute; inset: -0.6rem; border-radius: 9999px; border: 4px solid rgba(21, 128, 61, .15); border-top-color: #15803d; border-right-color: #ca8a04; animation: app-preloader-spin 1.1s linear infinite; }
    .app-preloader__logo { width: 100%; height: 100%; object-fit: contain; animation: app-preloader-breathe 1.8s ease-in-out infinite; }
    .app-preloader__name { margin-top: 1.75rem; font-size: .8rem; font-weight: 700; letter-spacing: .3em; text-transform: uppercase; color: #14532d; }
    .dark .app-preloader__name { color: #e2e8f0; }
    .app-preloader__dots { margin-top: .75rem; display: flex; gap: .4rem; }
    .app-preloader__dots span { width: .45rem; height: .45rem; border-radius: 9999px; background: #ca8a04; animation: app-preloader-dot 1.2s ease-in-out infinite; }
    .app-preloader__dots span:nth-child(2) { animation-delay: .15s; background: #15803d; }
    .app-preloader__dots span:nth-child(3) { animation-delay: .3s; background: #7f1d1d; }
    @keyframes app-preloader-spin { to { transform: rotate(360deg); } }
    @keyframes app-preloader-breathe { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
    @keyframes app-preloader-dot { 0%, 80%, 100% { opacity: .25; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-4px); } }
    @media (prefers-reduced-motion: reduce) { .app-preloader__ring, .app-preloader__logo, .app-preloader__dots span { animation: none; } }
    @media print { .app-preloader { display: none; } }
</style>

<script>
    (function () {
        var el = document.getElementById('app-preloader');
        if (!el) return;
        var done = false;
        function hide() {
            if (done) return;
            done = true;
            el.classList.add('is-done');
            setTimeout(function () { el.remove(); }, 450);
        }
        function later() { setTimeout(hide, {{ (int) $delay }}); }
        if (document.readyState === 'complete') later(); else window.addEventListener('load', later, { once: true });
        setTimeout(hide, 6000);
    })();
</script>
