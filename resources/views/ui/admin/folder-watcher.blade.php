{{-- System Admin > Folder Watcher, copied from KLAES system-admin/folder-watcher ("Folder Watcher Control Center": the
     Python watcher's own dashboard embedded from http://127.0.0.1:5001/, with an open-in-new-tab link when it is stopped).
     Below it, where this app's uploads land (config disks), read only. --}}
<x-app-layout title="Folder Watcher">
    <div class="space-y-6">
        <x-ui.page-head icon="folder-search" title="Folder Watcher Control Center" subtitle="Start or stop the watcher, change its configuration and read its statistics" tone="slate" section="System Admin" />

        <x-ui.panel title="Watcher service">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                The live dashboard below is rendered directly from the Python watcher service running on
                <code class="rounded bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">http://127.0.0.1:5001/</code>.
                If it does not load (for example, if the service is stopped), <a href="http://127.0.0.1:5001/" target="_blank" rel="noopener" class="font-semibold text-indigo-600 underline dark:text-indigo-400">open the dashboard in a new tab</a>.
            </p>
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                <embed src="http://127.0.0.1:5001/" type="text/html" class="w-full bg-white" style="min-height: 60vh;">
            </div>
        </x-ui.panel>

        <x-ui.panel title="Where uploads are stored" :padded="false">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr><th class="px-4 py-3">S/N</th><th class="px-4 py-3">Store</th><th class="px-4 py-3">Disk</th><th class="px-4 py-3">Root</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ([['Scanned pages (DMS)', $disk], ['Other uploads', $uploads]] as $i => [$what, $name])
                        <tr>
                            <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $what }}</td>
                            <td class="px-4 py-3"><code class="text-xs">{{ $name }}</code></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400"><code class="text-xs">{{ config('filesystems.disks.'.$name.'.root', '—') }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.panel>
    </div>
</x-app-layout>
