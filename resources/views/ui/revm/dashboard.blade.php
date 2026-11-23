<x-app-layout title="REV-M Dashboard">
    <div class="space-y-6">
        <x-ui.page-head icon="landmark" title="REV-M Dashboard" subtitle="Revenue and Information Products" tone="emerald" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Bills" value="0" hint="Preview mode" icon="receipt" tone="blue" />
            <x-ui.stat title="Paid" value="0" hint="Awaiting migrations" icon="badge-check" tone="emerald" />
            <x-ui.stat title="Outstanding" value="0" hint="Awaiting migration" icon="clock" tone="amber" />
        </div>
    </div>
</x-app-layout>
