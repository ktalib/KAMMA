{{-- KLAES REV-M > Automated Billing > Contravention Bill (KLAES spa_bills source=contravention; same screen as KLAES-CONTROL (KADCA)
     ). Wizard: Property, Contravention, Charge, Review. --}}
<x-app-layout title="Contravention Bill">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $card = 'space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="siren" title="Contravention Bill" subtitle="Charge a development that contravenes control rules; no approval = 4 × the normal price" tone="rose" section="KLAES REV-M › Automated Billing" />
        <x-ui.preview-note what="Raising a contravention bill and its notice" />

        <form method="POST" action="{{ route('ui.preview', 'contravention-bill') }}" novalidate class="space-y-6"
              x-data="formWizard({ file: null, base: '', approval: 'No approval', get factor() { return this.approval === 'No approval' ? 4 : 1 }, get amount() { return (parseFloat(this.base) || 0) * this.factor }, guard(n) { if (n === 1 && ! this.file) { alert('Pick the land file first.'); return false } } }, { steps: 4 })"
              @submit="submitAll($event)" @file-picked="file = $event.detail">
            @csrf
            <x-wizard-steps :steps="['Property', 'Contravention', 'Charge', 'Review']" />

            <section data-step="1" x-show="step === 1" class="{{ $card }}">
                <x-ui.file-picker name="file_indexing_id" label="Land file (KLAES)" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cv-owner" class="{{ $label }}">Developer / occupier <span class="text-rose-500">*</span></label>
                        <input id="cv-owner" name="developer" required class="{{ $field }}">
                    </div>
                    <div>
                        <label for="cv-phone" class="{{ $label }}">Phone</label>
                        <input id="cv-phone" name="phone" inputmode="tel" class="{{ $field }}">
                    </div>
                </div>
            </section>

            <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select-other name="nature" label="Nature of contravention" :options="['Building without approval', 'Deviation from approved plan', 'Setback violation', 'Building on right of way', 'Change of use without approval', 'Outdoor advertisement without permit']" required />
                    <div>
                        <label for="cv-approval" class="{{ $label }}">Approval status <span class="text-rose-500">*</span></label>
                        <select id="cv-approval" name="approval_status" x-model="approval" required class="{{ $field }}"><option>No approval</option><option>Approved, but deviated</option></select>
                    </div>
                    <div>
                        <label for="cv-date" class="{{ $label }}">Date observed <span class="text-rose-500">*</span></label>
                        <input id="cv-date" type="date" name="observed_on" required max="{{ now()->toDateString() }}" class="{{ $field }}">
                    </div>
                    <x-ui.select-other name="stage" label="Stage of development" :options="['Foundation', 'DPC', 'Lintel', 'Roofing', 'Completed', 'In use']" required />
                    <div class="sm:col-span-2">
                        <label for="cv-obs" class="{{ $label }}">Observation <span class="text-rose-500">*</span></label>
                        <textarea id="cv-obs" name="observation" rows="3" required class="{{ $field }}"></textarea>
                    </div>
                </div>
            </section>

            <section data-step="3" x-show="step === 3" x-cloak class="{{ $card }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cv-base" class="{{ $label }}">Normal fee (₦) <span class="text-rose-500">*</span></label>
                        <input id="cv-base" type="number" min="0.01" step="0.01" name="base_amount" x-model="base" required class="{{ $field }}">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The fee the development would pay with approval.</p>
                    </div>
                    <div>
                        <span class="{{ $label }}">Penalty factor</span>
                        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200" x-text="factor + ' ×'"></p>
                    </div>
                    <div>
                        <label for="cv-due" class="{{ $label }}">Pay within (days) <span class="text-rose-500">*</span></label>
                        <input id="cv-due" type="number" min="1" max="90" name="due_days" value="14" required class="{{ $field }}">
                    </div>
                    <div class="flex items-end"><label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="checkbox" name="serve_notice" value="1" checked class="rounded text-indigo-600"> Generate Contravention Notice with the bill</label></div>
                </div>
            </section>

            <section data-step="4" x-show="step === 4" x-cloak class="{{ $card }}">
                <div class="rounded-xl bg-rose-50 p-5 text-center dark:bg-rose-500/10">
                    <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">Contravention bill for <span x-text="file ? file.file_number : '—'"></span></p>
                    <p class="text-3xl font-black text-rose-800 dark:text-rose-200" x-text="'₦' + amount.toLocaleString('en-NG', { minimumFractionDigits: 2 })"></p>
                    <p class="text-xs text-rose-700/80 dark:text-rose-300/80" x-text="'₦' + (parseFloat(base) || 0).toLocaleString('en-NG') + ' × ' + factor"></p>
                </div>
            </section>

            <x-wizard-nav :cancel="route('revm.bill-master')">Generate bill</x-wizard-nav>
        </form>

        <x-ui.panel title="Contravention bills" :padded="false">
            <x-ui.empty icon="siren" title="No contravention bills yet">Bills and their notices will be listed here.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
