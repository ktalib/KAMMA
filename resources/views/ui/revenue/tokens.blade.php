{{-- KLAES REV-M > Transaction Token Control (copied from KLAES system-admin/legal-search-tokens): figure cards, the generated
     tokens table and the "New verification credential" pop-up (General + Payment Information). KAMMA has no token store
     yet, so generating posts to the preview stub. --}}
<x-app-layout title="Transaction Token Control">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6" x-data="{ open: false }">
        <x-ui.page-head icon="ticket" title="Transaction Token Control (TTC)" subtitle="Manage search tokens and tracking." tone="emerald" section="KLAES REV-M">
            <button type="button" @click.stop="open = true" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="plus" class="h-4 w-4"></i> New verification credential</button>
        </x-ui.page-head>
        <x-ui.preview-note what="Generating a transaction token" />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Total Tokens" value="0" icon="ticket" tone="blue" />
            <x-ui.stat title="Used Tokens" value="0" icon="check-circle-2" tone="emerald" />
            <x-ui.stat title="Unused Tokens" value="0" icon="circle-dashed" tone="amber" />
        </div>

        <x-ui.panel title="Generated Tokens" :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            @foreach (['S/N', 'Token', 'File Number', 'Applicant', 'Client Name', 'Client Address', 'Transaction Purpose', 'Receipt', 'Usage', 'Status', 'Created At'] as $h)
                                <th class="whitespace-nowrap px-4 py-3">{{ $h }}</th>
                            @endforeach
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <x-ui.empty icon="ticket" title="No tokens generated yet.">A token is issued against a paid receipt and opens one transaction for 24 hours.</x-ui.empty>
        </x-ui.panel>

        {{-- New verification credential --}}
        <div x-show="open" x-cloak>
            <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-10" @keydown.escape.window="open = false">
                <form method="POST" action="{{ route('ui.preview', 'transaction-token') }}" @click.outside="open = false" @click.stop
                      class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h2 class="font-bold text-slate-900 dark:text-white">New verification credential</h2>
                        <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    <div class="space-y-6 p-6">
                        <fieldset class="space-y-4">
                            <legend class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">General</legend>
                            <x-ui.select-other name="requesting_body" label="Requesting Body" :options="['Applicant', 'Legal Practitioner', 'Bank / Financial Institution', 'Government Agency', 'Court']" required />
                            <x-ui.file-picker name="file_indexing_id" label="File Selection" />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label for="tt-app" class="{{ $label }}">Applicant Name <span class="text-rose-500">*</span></label><input id="tt-app" name="applicant_name" required class="{{ $field }}"></div>
                                <div><label for="tt-addr" class="{{ $label }}">Client Address</label><input id="tt-addr" name="client_address" class="{{ $field }}"></div>
                            </div>
                        </fieldset>
                        <fieldset class="space-y-4">
                            <legend class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Payment Information</legend>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label for="tt-rct" class="{{ $label }}">Receipt No. <span class="text-rose-500">*</span></label><input id="tt-rct" name="receipt_no" required class="{{ $field }}"></div>
                                <div><label for="tt-date" class="{{ $label }}">Date Paid <span class="text-rose-500">*</span></label><input id="tt-date" type="date" name="date_paid" required max="{{ now()->toDateString() }}" class="{{ $field }}"></div>
                            </div>
                            <x-ui.select-other name="reason" label="Reason for Payment" :options="['Application Processing Fee', 'Betterment Fee', 'Planning Recommendation', 'Search / Verification', 'Certified True Copy']" required />
                        </fieldset>
                        <div class="grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 dark:bg-slate-800/60">
                            <div><span class="{{ $label }}">Generated By</span>{{ auth()->user()->name ?? auth()->user()->username ?? '—' }}</div>
                            <div><span class="{{ $label }}">Date</span>{{ now()->format('d M Y') }}</div>
                            <div><span class="{{ $label }}">Time</span>{{ now()->format('H:i') }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                        <button type="button" @click="open = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Generate token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
