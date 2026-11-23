{{-- CRM > Customer Manager > Appointment Calendar: a month view of appointments (and, later, booked site inspections). --}}
<x-app-layout title="Appointment Calendar">
    @php
        $start = $month->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) { $days[] = $d->copy(); }
        $btn = 'inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800';
    @endphp

    <div class="space-y-6" x-data="{ picked: null }">
        <x-ui.page-head icon="calendar-days" title="Appointment Calendar" subtitle="Appointments by day; pick a day to see or book its slots" tone="indigo" section="CRM › Customer Manager">
            <a href="{{ route('crm.appointments') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="calendar-plus" class="h-4 w-4"></i> New appointment</a>
        </x-ui.page-head>

        <x-ui.preview-note what="The appointment book" />

        <x-ui.panel :padded="false">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $month->format('F Y') }}</h2>
                <div class="flex items-center gap-2">
                    <a class="{{ $btn }}" href="{{ route('crm.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month"><i data-lucide="chevron-left" class="h-4 w-4"></i></a>
                    <a class="{{ $btn }}" href="{{ route('crm.calendar') }}">Today</a>
                    <a class="{{ $btn }}" href="{{ route('crm.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month"><i data-lucide="chevron-right" class="h-4 w-4"></i></a>
                </div>
            </div>
            <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $w)<div class="py-2">{{ $w }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    @php $in = $day->month === $month->month; $today = $day->isToday(); $weekend = $day->isWeekend(); @endphp
                    <button type="button" @click="picked = @js($day->format('l, j F Y'))"
                            @class(['min-h-[88px] border-b border-r border-slate-100 p-2 text-left align-top transition hover:bg-indigo-50/60 dark:border-slate-800 dark:hover:bg-indigo-500/10',
                                    'bg-slate-50/60 text-slate-400 dark:bg-slate-900/40 dark:text-slate-600' => ! $in,
                                    'text-slate-700 dark:text-slate-200' => $in,
                                    'bg-amber-50/40 dark:bg-amber-500/5' => $in && $weekend])>
                        <span @class(['inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold', 'bg-indigo-600 text-white' => $today])>{{ $day->day }}</span>
                        @if ($in && ! $weekend)
                            <span class="mt-2 block text-[11px] text-slate-400 dark:text-slate-500">0 booked</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </x-ui.panel>

        <x-ui.panel x-show="picked" x-cloak>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white" x-text="picked"></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">No appointments on this day.</p>
                </div>
                <a href="{{ route('crm.appointments') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="plus" class="h-4 w-4"></i> Book on this day</a>
            </div>
            <div class="mt-4 grid gap-2 sm:grid-cols-3 lg:grid-cols-6">
                @foreach (['09:00', '10:00', '11:00', '12:00', '14:00', '15:00'] as $t)
                    <div class="rounded-lg border border-dashed border-slate-200 px-3 py-2 text-sm dark:border-slate-700"><span class="font-semibold text-slate-700 dark:text-slate-200">{{ $t }}</span> <span class="text-slate-400 dark:text-slate-500">· free</span></div>
                @endforeach
            </div>
        </x-ui.panel>
    </div>
</x-app-layout>
