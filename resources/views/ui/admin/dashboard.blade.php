{{-- System Admin > Dashboard (KLAES module-dashboard for system_admin): the system's figures and a tile for every System
     Admin screen. --}}
<x-app-layout title="System Admin">
    @php
        $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
        $tiles = [
            ['Entities & Rates', 'Fees, serial generators and lookups', 'sliders-horizontal', route('admin.entities')],
            ['Activity Logs', 'Who did what, and when', 'scroll-text', route('admin.activity-logs')],
            ['Activity Monitoring', 'Activity by user and day', 'activity', route('activity.monitoring', ['scope' => 'admin'])],
            ['User Roles', 'Modules and staff accounts', 'shield-check', route('admin.roles')],
            ['Digital Signature Control', 'Signing officers and their signatures', 'signature', route('admin.signatures')],
            ['System Settings', 'Name, contact, email and security', 'settings', route('admin.settings')],
            ['Folder Watcher', 'Scan folders picked up for upload', 'folder-sync', route('admin.folder-watcher')],
            ['Bulk SMS Management', 'Messages sent as KLAES-METRO', 'message-square-text', route('admin.sms')],
            ['Verify QRCode', 'Check a KAMMA document is genuine', 'scan-qr-code', route('admin.verify')],
            ['KLAES Mobile Apps', 'The KLAES-METRO field app', 'smartphone', route('admin.mobile-apps')],
            ['KLAES Documentation', 'Workflow, forms and menu documents', 'book-open', route('admin.documentation')],
        ];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="settings" title="System Admin" subtitle="KLAES-METRO administration" tone="slate" section="System Admin" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-ui.stat title="Staff accounts" :value="$n($figures['users'])" hint="Active KLAES users who can sign in" icon="users" tone="blue" :href="route('admin.roles')" />
            <x-ui.stat title="KSI reports" :value="$n($figures['applications'])" icon="file-text" tone="violet" :href="route('kamma.master-ksi.index')" />
            <x-ui.stat title="Bills" :value="$n($figures['bills'])" icon="receipt" tone="emerald" :href="route('revm.bill-master')" />
            <x-ui.stat title="Tracked files" :value="$n($figures['trackers'])" icon="map-pin" tone="amber" :href="route('tracking.dashboard')" />
            <x-ui.stat title="Shadow files" :value="$n($figures['shadow'])" hint="DMS indexing" icon="files" tone="cyan" :href="route('dms.indexing.index')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($tiles as [$title, $text, $icon, $href])
                <a href="{{ $href }}" class="group flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-indigo-500/10 dark:group-hover:text-indigo-300"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                    <span><span class="block font-semibold text-slate-900 dark:text-white">{{ $title }}</span><span class="text-sm text-slate-500 dark:text-slate-400">{{ $text }}</span></span>
                </a>
            @endforeach
        </div>

        <x-ui.panel title="System">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Application</dt><dd class="font-semibold">{{ config('app.name') }} (KLAES-METRO)</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Environment</dt><dd class="font-semibold">{{ $env }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Database</dt><dd class="font-mono">{{ $database }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Laravel / PHP</dt><dd>{{ app()->version() }} / {{ PHP_VERSION }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Time zone</dt><dd>{{ config('app.timezone') }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Roles and permissions</dt><dd class="text-amber-700 dark:text-amber-300">On hold: every signed-in user sees every screen</dd></div>
            </dl>
        </x-ui.panel>
    </div>
</x-app-layout>
