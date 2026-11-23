{{-- CRM > Customer Manager > Appointment: book a customer in (building plan submission, site inspection, collection ...).
     KLAES's menu links /appointment but has no screen, so this one is new, in KLAES style. Wizard: Customer, Appointment, Review. --}}
<x-app-layout title="Appointment">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $card = 'space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $steps = ['Customer', 'Appointment', 'Review'];
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="calendar-plus" title="Appointment" subtitle="Book a customer in with a KAMMA desk" tone="indigo" section="CRM › Customer Manager">
            <a href="{{ route('crm.calendar') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="calendar-days" class="h-4 w-4"></i> Appointment Calendar</a>
        </x-ui.page-head>

        <x-ui.preview-note what="The appointment book" />

        <form method="POST" action="{{ route('ui.preview', 'crm-appointment') }}" novalidate class="space-y-6"
              x-data="formWizard({ name: '', phone: '', email: '', purpose: '', date: '', time: '', unit: '', file: null }, { steps: 3 })"
              @submit="submitAll($event)" @file-picked="file = $event.detail">
            @csrf
            <x-wizard-steps :steps="$steps" />

            <section data-step="1" x-show="step === 1" class="{{ $card }}">
                <h2 class="font-bold text-slate-900 dark:text-white">Customer</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="ap-name" class="{{ $label }}">Customer name <span class="text-rose-500">*</span></label>
                        <input id="ap-name" name="customer_name" x-model="name" required maxlength="200" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="ap-phone" class="{{ $label }}">Phone <span class="text-rose-500">*</span></label>
                        <input id="ap-phone" name="phone" x-model="phone" required inputmode="tel" pattern="[0-9+ ]{7,20}" class="{{ $field }}" placeholder="080…">
                    </div>
                    <div>
                        <label for="ap-email" class="{{ $label }}">Email</label>
                        <input id="ap-email" type="email" name="email" x-model="email" class="{{ $field }}">
                    </div>
                    <div class="sm:col-span-2">
                        <x-ui.file-picker name="shadow_file_id" source="shadow" label="KAMMA file (if the customer already has one)" :required="false" />
                    </div>
                </div>
            </section>

            <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
                <h2 class="font-bold text-slate-900 dark:text-white">Appointment</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select-other name="purpose" label="Purpose" :options="$purposes" required x-on:change="purpose = $event.target.value" />
                    <div>
                        <label for="ap-unit" class="{{ $label }}">Desk / unit <span class="text-rose-500">*</span></label>
                        <select id="ap-unit" name="unit" x-model="unit" required class="{{ $field }}">
                            <option value="">Select…</option>
                            @foreach ($units as $u)<option>{{ $u }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="ap-date" class="{{ $label }}">Date <span class="text-rose-500">*</span></label>
                        <input id="ap-date" type="date" name="date" x-model="date" required min="{{ now()->toDateString() }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="ap-time" class="{{ $label }}">Time <span class="text-rose-500">*</span></label>
                        <select id="ap-time" name="time" x-model="time" required class="{{ $field }}">
                            <option value="">Select…</option>
                            @foreach (['09:00', '10:00', '11:00', '12:00', '14:00', '15:00'] as $t)<option>{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="ap-notes" class="{{ $label }}">Notes</label>
                        <textarea id="ap-notes" name="notes" rows="3" maxlength="1000" class="{{ $field }}"></textarea>
                    </div>
                </div>
            </section>

            <section data-step="3" x-show="step === 3" x-cloak class="{{ $card }}">
                <h2 class="font-bold text-slate-900 dark:text-white">Review</h2>
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="{{ $label }}">Customer</dt><dd class="text-slate-800 dark:text-slate-200" x-text="name || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Phone / email</dt><dd class="text-slate-800 dark:text-slate-200" x-text="[phone, email].filter(Boolean).join(' · ') || '—'"></dd></div>
                    <div><dt class="{{ $label }}">KAMMA file</dt><dd class="text-slate-800 dark:text-slate-200" x-text="file ? file.file_number : 'None'"></dd></div>
                    <div><dt class="{{ $label }}">Desk / unit</dt><dd class="text-slate-800 dark:text-slate-200" x-text="unit || '—'"></dd></div>
                    <div><dt class="{{ $label }}">Date and time</dt><dd class="text-slate-800 dark:text-slate-200" x-text="[date, time].filter(Boolean).join(' at ') || '—'"></dd></div>
                </dl>
            </section>

            <x-wizard-nav :cancel="route('crm.dashboard')">Book appointment</x-wizard-nav>
        </form>

        <x-ui.panel title="Upcoming appointments" :padded="false">
            <x-ui.empty icon="calendar-x" title="No appointments booked">Booked appointments will be listed here with the desk, time and customer once the appointment book is connected.</x-ui.empty>
        </x-ui.panel>
    </div>
</x-app-layout>
