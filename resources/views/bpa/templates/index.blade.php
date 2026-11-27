{{-- The Building Plan Approval print templates, for a layout check. Not on the sidebar yet (user 2026-11-24). --}}
<x-app-layout title="BPA Templates">
    @php $td = 'whitespace-nowrap px-4 py-3 text-sm text-slate-700 dark:text-slate-300'; @endphp
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Building Plan Approval templates</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">The paper forms (formerly KNUPDA) as KAMMA print templates, for KAMMA &gt; Development Permit. Open one blank, or with sample data to check how filled fields print. Not linked to any application yet.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3">S/N</th>
                        <th class="px-4 py-3">Template</th>
                        <th class="px-4 py-3">Paper page</th>
                        <th class="px-4 py-3 text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($templates as $slug => [$title, $pages])
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $loop->iteration }}</td>
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $title }}</td>
                            <td class="{{ $td }}">{{ $pages }}</td>
                            <td class="{{ $td }} text-right">
                                <x-row-actions>
                                    <x-row-action :href="route('bpa.templates.show', $slug)" target="_blank">
                                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg></x-slot:icon>
                                        Open blank
                                    </x-row-action>
                                    <x-row-action :href="route('bpa.templates.show', [$slug, 'sample' => 1])" target="_blank">
                                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg></x-slot:icon>
                                        Open with sample data
                                    </x-row-action>
                                </x-row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
