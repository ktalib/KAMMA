<x-app-layout title="Units">
    @php
        $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $th = 'whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $td = 'whitespace-nowrap px-3 py-3 text-sm text-slate-700 dark:text-slate-300';
        $errs = $errors->getBag('unit');
    @endphp

    <div class="mx-auto max-w-4xl space-y-6" x-data="{ adding: @js($errs->any() && ! old('unit_id')), edit: null }" @keydown.escape.window="adding = false; edit = null">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">File/Mail Movement</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">Units</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">The units files and mails move between. KAMMA has not given its department list yet, so this starts with the desks we know; add or rename units here.</p>
            </div>
            <button type="button" @click.stop="adding = true" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add unit</button>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errs->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errs->first() }}</div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr><th class="{{ $th }}">S/N</th><th class="{{ $th }}">Unit</th><th class="{{ $th }}">Linked desk</th><th class="{{ $th }}">Status</th><th class="{{ $th }}"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($units as $u)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="{{ $td }}">{{ $loop->iteration }}</td>
                            <td class="{{ $td }} font-medium text-slate-900 dark:text-slate-100">{{ $u->name }}</td>
                            <td class="{{ $td }}">{{ $u->stage ? \App\Support\KammaDesk::label($u->stage) : '—' }}</td>
                            <td class="{{ $td }}">{{ $u->is_active ? 'Active' : 'Not in use' }}</td>
                            <td class="{{ $td }} text-right">
                                <x-row-actions>
                                    <button type="button" @click.stop="edit = @js(['url' => route('tracking.units.update', $u), 'name' => $u->name, 'active' => $u->is_active ? '1' : '0']); open = false"
                                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                        Edit
                                    </button>
                                </x-row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="adding" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true">
            <form method="POST" action="{{ route('tracking.units.store') }}" @click.outside="adding = false" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Add unit</h2>
                <div><label for="new-name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Name <span class="text-rose-500">*</span></label><input id="new-name" name="name" value="{{ old('unit_id') ? '' : old('name') }}" required maxlength="150" class="{{ $field }}"></div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="adding = false" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add</button>
                </div>
            </form>
        </div>

        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true">
            <form method="POST" :action="edit ? edit.url : ''" @click.outside="edit = null" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                @method('PATCH')
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Edit unit</h2>
                <div><label for="edit-name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Name <span class="text-rose-500">*</span></label><input id="edit-name" name="name" :value="edit ? edit.name : ''" required maxlength="150" class="{{ $field }}"></div>
                <div>
                    <label for="edit-active" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                    <select id="edit-active" name="is_active" :value="edit ? edit.active : '1'" class="{{ $field }}">
                        <option value="1">Active</option>
                        <option value="0">Not in use</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="edit = null" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
