{{-- Light / dark theme. Goes first in <head> so the page never flashes the wrong theme.
     The viewer's own choice (theme toggle, saved in this browser) wins; without one the device setting decides. --}}
<script>
    (function () {
        var saved = null;
        try { saved = localStorage.getItem('theme'); } catch (e) {}
        var dark = saved === 'dark' || (saved !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
