{{-- System Admin > Digital Signature Control, copied from KLAES digital_signatures/index (settings/land-officers-signatures):
     "Signing Officers" table (Officer, Department, Method, Signature) and the Add Signing Officer pop-up (Name, Rank, Linked
     User, Default Verification Method, Digital Signature File). KAMMA has no signing officers stored yet: preview save. --}}
<x-app-layout title="Digital Signature Control">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6" x-data="{ add: false }">
        <x-ui.page-head icon="signature" title="Digital Signature Control" subtitle="Officers who sign KAMMA memos, planning recommendations and receipts" tone="slate" section="System Admin">
            <button type="button" @click.stop="add = true" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="plus" class="h-4 w-4"></i> Add Signing Officer</button>
        </x-ui.page-head>
        <x-ui.preview-note what="Adding a signing officer" />

        <x-ui.panel title="Signing Officers" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>@foreach (['S/N', 'Officer', 'Rank', 'Department', 'User', 'Method', 'Signature', 'Signs'] as $h)<th class="px-4 py-3">{{ $h }}</th>@endforeach<th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="signature" title="No signing officer yet">Add the MD KAMMA (memos, planning recommendations) and the officer who signs receipts.</x-ui.empty>
        </x-ui.panel>

        <div x-show="add" x-cloak class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="add = false">
            <form method="POST" action="{{ route('ui.preview', 'signing-officer') }}" enctype="multipart/form-data" @click.outside="add = false" class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                @csrf
                <div class="flex items-start justify-between"><h2 class="text-lg font-bold text-slate-900 dark:text-white">Add Signing Officer</h2><button type="button" @click="add = false" class="text-xl leading-none text-slate-400" aria-label="Close">&times;</button></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="so-name" class="{{ $label }}">Name <span class="text-rose-500">*</span></label><input id="so-name" name="name" required class="{{ $field }}"></div>
                    <div><label for="so-rank" class="{{ $label }}">Rank <span class="text-rose-500">*</span></label><input id="so-rank" name="rank" required class="{{ $field }}"></div>
                </div>
                <div><label for="so-user" class="{{ $label }}">Linked User</label><input id="so-user" name="username" placeholder="KLAES username" class="{{ $field }}"></div>
                <fieldset>
                    <legend class="{{ $label }}">Signs <span class="text-rose-500">*</span></legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['Planning Recommendation', 'KAMMA Memo', 'Official receipt'] as $d)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700"><input type="checkbox" name="signs[]" value="{{ $d }}" class="rounded dark:border-slate-600 dark:bg-slate-800"> {{ $d }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div><label for="so-method" class="{{ $label }}">Default Verification Method <span class="text-rose-500">*</span></label><select id="so-method" name="method" required class="{{ $field }}"><option value="">Select…</option><option>Email OTP</option><option>SMS OTP</option><option>Password Confirmation</option></select></div>
                <div><label for="so-file" class="{{ $label }}">Digital Signature File <span class="text-rose-500">*</span></label><input id="so-file" type="file" name="signature" accept="image/png,image/jpeg" required class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:text-slate-300 dark:file:bg-indigo-500/10 dark:file:text-indigo-300"><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PNG with a transparent background works best.</p></div>
                <div class="flex justify-end gap-3"><button type="button" @click="add = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700 dark:text-slate-300">Cancel</button><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save Signing Officer</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
