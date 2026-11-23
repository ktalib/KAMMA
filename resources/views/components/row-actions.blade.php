{{-- Row actions: one three-dots menu per row (KLAES rule 4). The menu is placed on <body> with fixed
     position, so a scrolling table can't clip it; it closes on Escape, scroll, resize or a click elsewhere.
     Put <x-row-action> items inside. --}}
<div
    x-data="{
        open: false, top: 0, left: 0,
        toggle(e) {
            const r = e.currentTarget.getBoundingClientRect();
            this.top = r.bottom + 4;
            this.left = Math.max(8, r.right - 208);
            this.open = ! this.open;
            // Near the bottom of the screen, open upwards instead.
            this.$nextTick(() => {
                const h = this.$refs.menu ? this.$refs.menu.offsetHeight : 0;
                if (this.open && r.bottom + 4 + h > window.innerHeight - 8) {
                    this.top = Math.max(8, r.top - h - 4);
                }
            });
        },
    }"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
    class="inline-block"
>
    <button type="button" @click.stop="toggle($event)" aria-label="Actions" :aria-expanded="open"
            class="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="1" /><circle cx="12" cy="5" r="1" /><circle cx="12" cy="19" r="1" />
        </svg>
    </button>

    <template x-teleport="body">
        <div x-ref="menu" x-show="open" x-cloak x-transition.opacity.duration.100ms @click.outside="open = false" @click="open = false"
             :style="`top: ${top}px; left: ${left}px`"
             class="fixed z-50 w-52 rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg dark:border-slate-700 dark:bg-slate-900">
            {{ $slot }}
        </div>
    </template>
</div>
