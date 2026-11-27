@extends('portal.layouts.portal')

@section('title', 'Site inspection')

@section('content')
@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900';
    $field = 'block w-full rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
    $h2 = 'text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400';
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Schedule a site inspection</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">When your file reaches the Zonal Office, pick a day and time for the officer to visit your site. The Zonal Office confirms the visit and tells you who is coming.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="min-w-0 lg:col-span-2">
        @if ($applications->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                <i data-lucide="calendar-x" class="mx-auto mb-4 h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                <p class="text-base font-semibold text-slate-900 dark:text-white">No application is ready for inspection</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">You can book a visit once the Deputy Director (Arch.) has passed your file to the Zonal Office.</p>
                <a href="{{ route('portal.bpa.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Back to my applications</a>
            </div>
        @else
        <script>
            // Month calendar of bookable days (weekdays between the earliest and latest booking day) with time slots.
            window.slotPicker = function (cfg) {
                const first = new Date(cfg.first + 'T00:00:00');
                return {
                    days: cfg.days, slots: cfg.slots,
                    year: first.getFullYear(), month: first.getMonth(),
                    date: cfg.old.date || '', time: cfg.old.time || '',
                    first: cfg.first, last: cfg.last,

                    iso(d) {
                        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    },
                    get title() {
                        return new Date(this.year, this.month, 1).toLocaleDateString('en-NG', { month: 'long', year: 'numeric' });
                    },
                    // Cells for the month, Monday first; blanks before the 1st.
                    get cells() {
                        const start = new Date(this.year, this.month, 1);
                        const blanks = (start.getDay() + 6) % 7;
                        const total = new Date(this.year, this.month + 1, 0).getDate();
                        const cells = Array.from({ length: blanks }, (_, i) => ({ key: 'b' + i, blank: true }));
                        for (let n = 1; n <= total; n++) {
                            const iso = this.iso(new Date(this.year, this.month, n));
                            cells.push({ key: iso, n, iso, open: iso in this.days });
                        }
                        return cells;
                    },
                    get canPrev() { return this.iso(new Date(this.year, this.month, 1)) > this.first; },
                    get canNext() { return this.iso(new Date(this.year, this.month + 1, 1)) <= this.last; },
                    move(step) {
                        const d = new Date(this.year, this.month + step, 1);
                        this.year = d.getFullYear(); this.month = d.getMonth();
                    },
                    pick(cell) { if (cell.open) { this.date = cell.iso; this.time = ''; } },
                    get summary() {
                        if (! this.date) return '';
                        const d = new Date(this.date + 'T00:00:00').toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                        return this.time ? d + ' at ' + this.time : d;
                    },
                };
            };
        </script>

        <form method="POST" action="{{ route('portal.bpa.inspections.store') }}" novalidate
              x-data="slotPicker(@js(['days' => $calendar['days'], 'slots' => $calendar['slots'], 'first' => $calendar['first'], 'last' => $calendar['last'], 'old' => ['date' => old('date'), 'time' => old('time')]]))"
              class="space-y-6">
            @csrf

            <div class="{{ $card }}">
                <label for="application" class="{{ $label }}">Application <span class="text-rose-500">*</span></label>
                <select id="application" name="application" required class="{{ $field }}">
                    @foreach ($applications as $a)
                        <option value="{{ $a['ref'] }}" @selected($selected && $selected['ref'] === $a['ref'])>{{ $a['ref'] }}{{ $a['bp_file_no'] ? ' · '.$a['bp_file_no'] : '' }} &middot; Plot {{ $a['plot_no'] ?: '—' }}, {{ $a['district'] }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('application')" class="mt-2" />
            </div>

            <div class="{{ $card }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white" x-text="title"></h2>
                    <div class="flex gap-1">
                        <button type="button" @click="move(-1)" :disabled="! canPrev" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 disabled:opacity-30 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Previous month">
                            <i data-lucide="chevron-left" class="h-5 w-5" aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="move(1)" :disabled="! canNext" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 disabled:opacity-30 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Next month">
                            <i data-lucide="chevron-right" class="h-5 w-5" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                        <span class="py-1">{{ $dayName }}</span>
                    @endforeach
                </div>
                <div class="mt-1 grid grid-cols-7 gap-1">
                    <template x-for="cell in cells" :key="cell.key">
                        <div>
                            <template x-if="cell.blank"><span class="block aspect-square"></span></template>
                            <template x-if="! cell.blank">
                                <button type="button" @click="pick(cell)" :disabled="! cell.open" :aria-pressed="date === cell.iso"
                                        class="flex aspect-square w-full items-center justify-center rounded-lg text-sm font-semibold transition"
                                        :class="date === cell.iso
                                            ? 'bg-emerald-600 text-white'
                                            : (cell.open
                                                ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20'
                                                : 'cursor-not-allowed text-slate-300 dark:text-slate-700')"
                                        x-text="cell.n"></button>
                            </template>
                        </div>
                    </template>
                </div>
                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Shaded days are open: working days from {{ \Illuminate\Support\Carbon::parse($calendar['first'])->format('j M') }} to {{ \Illuminate\Support\Carbon::parse($calendar['last'])->format('j M Y') }}.</p>
                <input type="hidden" name="date" :value="date">
                <x-input-error :messages="$errors->get('date')" class="mt-2" />

                <div x-show="date" x-cloak class="mt-5 border-t border-slate-100 pt-5 dark:border-slate-800">
                    <p class="{{ $label }}">Time</p>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        <template x-for="slot in slots" :key="slot">
                            <button type="button" @click="time = slot" :aria-pressed="time === slot"
                                    class="rounded-lg border px-3 py-2.5 text-sm font-semibold transition"
                                    :class="time === slot
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : 'border-slate-300 bg-white text-slate-700 hover:border-emerald-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-emerald-500'"
                                    x-text="slot"></button>
                        </template>
                    </div>
                    <input type="hidden" name="time" :value="time">
                    <x-input-error :messages="$errors->get('time')" class="mt-2" />
                </div>
            </div>

            <div class="{{ $card }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_phone" class="{{ $label }}">Phone number on site <span class="text-rose-500">*</span></label>
                        <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $applicant['phone'] ?? '') }}" required inputmode="tel" class="{{ $field }}">
                        <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="{{ $label }}">Directions or notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="2" maxlength="500" class="{{ $field }}" placeholder="e.g. Gate is on the north side, next to the mosque.">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-show="summary" x-text="summary"></p>
                <button type="submit" :disabled="! date || ! time" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                    <i data-lucide="calendar-check" class="h-4 w-4" aria-hidden="true"></i> Request this visit
                </button>
            </div>
        </form>
        @endif
    </div>

    <div class="min-w-0 space-y-6">
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">Your visits</h2>
            @forelse ($bookings as $b)
                <a href="{{ route('portal.bpa.applications.show', $b['ref']) }}" class="mt-3 flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3 hover:border-emerald-400 dark:border-slate-800 dark:hover:border-emerald-500">
                    <span class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <span class="text-[10px] font-bold uppercase">{{ $b['inspection']['date']->format('M') }}</span>
                        <span class="text-lg font-bold leading-none">{{ $b['inspection']['date']->format('j') }}</span>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-mono text-xs font-bold text-slate-900 dark:text-white">{{ $b['ref'] }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $b['inspection']['date']->format('D j M') }}, {{ $b['inspection']['time'] }} &middot; {{ $b['inspection']['status'] }}</span>
                    </span>
                </a>
            @empty
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">No visits requested yet.</p>
            @endforelse
        </div>
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">On the day</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-600 dark:text-slate-400">
                <li>Be on site, or have someone there, on the phone number above.</li>
                <li>Keep a copy of the approved drawings on site.</li>
                <li>The officer shows a KAMMA identity card. KAMMA officers never collect money on site.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
