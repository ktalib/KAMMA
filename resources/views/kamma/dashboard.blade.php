<x-app-layout title="KAMMA Dashboard">
    <div class="space-y-6">
        <x-ui.page-head icon="layout-grid" title="KAMMA Dashboard" subtitle="Planning recommendation overview" tone="emerald" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Shadow Files" value="0" hint="Preview mode" icon="files" tone="blue" />
            <x-ui.stat title="Site Visits" value="0" hint="Preview mode" icon="map-pin" tone="amber" />
            <x-ui.stat title="Recommendations" value="0" hint="Preview mode" icon="clipboard-check" tone="emerald" />
        </div>
    </div>
</x-app-layout>
