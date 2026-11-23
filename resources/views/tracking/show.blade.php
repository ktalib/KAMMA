<x-app-layout :title="$tracker->tracking_id">
    @php
        $field = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300';
        $card = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $dt = 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $dd = 'mt-1 break-words text-sm text-slate-900 dark:text-slate-100';
        $show = fn ($v) => filled($v) ? $v : '—';
        $a = $tracker->shadowFile;
        $sendErrors = $errors->getBag('send');
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">File Tracker · {{ $tracker->kind === 'mail' ? 'Mail' : 'KAMMA file' }}</p>
                <h1 class="mt-1 font-mono text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $tracker->tracking_id }}</h1>
                <div class="mt-2"><x-tracking.status :tracker="$tracker" /></div>
            </div>
            <a href="{{ route('tracking.search') }}" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Back to Quick Search</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->has('tracker'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first('tracker') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="{{ $card }} lg:col-span-2">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Details</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="{{ $dt }}">Reference</dt><dd class="{{ $dd }} font-medium">{{ $tracker->reference }}</dd></div>
                    <div><dt class="{{ $dt }}">{{ $tracker->kind === 'mail' ? 'Subject' : 'File title' }}</dt><dd class="{{ $dd }}">{{ $show($tracker->title) }}</dd></div>
                    @if ($a)
                        <div><dt class="{{ $dt }}">Land file (KLAES)</dt><dd class="{{ $dd }}">{{ $a->file_number }}</dd></div>
                        <div><dt class="{{ $dt }}">Shadow file</dt><dd class="{{ $dd }}">Commissioned {{ $a->date_matched?->format('d M Y') }} by {{ $show($a->matched_by_name) }}</dd></div>
                        <div class="sm:col-span-2"><dt class="{{ $dt }}">Plot No. · District · LGA · Location</dt><dd class="{{ $dd }}">{{ collect([$a->plot_number, $a->district, $a->lga, $a->location])->filter()->implode(' · ') ?: '—' }}</dd></div>
                    @else
                        <div><dt class="{{ $dt }}">From</dt><dd class="{{ $dd }}">{{ $show($tracker->mail_from) }}</dd></div>
                    @endif
                    <div><dt class="{{ $dt }}">Priority</dt><dd class="{{ $dd }}">{{ $tracker->priority }}</dd></div>
                    <div><dt class="{{ $dt }}">Due by</dt><dd class="{{ $dd }}">{{ $tracker->due_on?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="{{ $dt }}">Shelf / box</dt><dd class="{{ $dd }}">{{ $show($tracker->shelf_location) }}</dd></div>
                    <div><dt class="{{ $dt }}">Logged by</dt><dd class="{{ $dd }}">{{ $tracker->created_by_name }}, {{ $tracker->created_at?->format('d M Y') }}</dd></div>
                    @if ($tracker->notes)
                        <div class="sm:col-span-2"><dt class="{{ $dt }}">Notes</dt><dd class="{{ $dd }}">{{ $tracker->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="{{ $card }} flex flex-col items-center text-center">
                <div class="rounded-lg bg-white p-2">{!! \App\Support\Qr::svg($tracker->tracking_id, 150) !!}</div>
                <p class="mt-2 font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tracker->tracking_id }}</p>
                <a href="{{ route('tracking.label', $tracker) }}" target="_blank" class="mt-4 inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Print QR label</a>
            </section>
        </div>

        @if ($tracker->status === 'in_transit')
            <form method="POST" action="{{ route('tracking.receive', $tracker) }}" class="{{ $card }} space-y-4">
                @csrf
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Receive in {{ $tracker->toUnit?->name }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Confirm the {{ $tracker->kind === 'mail' ? 'mail' : 'file' }} has arrived. Until then it shows as in transit.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label for="shelf" class="{{ $label }}">Shelf / box</label><input id="shelf" name="shelf_location" value="{{ $tracker->shelf_location }}" maxlength="150" class="{{ $field }} uppercase"></div>
                    <div class="sm:col-span-2"><label for="rnotes" class="{{ $label }}">Notes</label><input id="rnotes" name="notes" maxlength="2000" class="{{ $field }}"></div>
                </div>
                <div class="flex justify-end"><button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Receive</button></div>
            </form>
        @else
            <form method="POST" action="{{ route('tracking.send', $tracker) }}" class="{{ $card }} space-y-4">
                @csrf
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Send to another unit</h2>
                @if ($sendErrors->any())
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        @foreach ($sendErrors->all() as $message)<p>{{ $message }}</p>@endforeach
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="to_unit_id" class="{{ $label }}">To <span class="text-rose-500">*</span></label>
                        <select id="to_unit_id" name="to_unit_id" required class="{{ $field }}">
                            <option value="">Select…</option>
                            @foreach ($units->where('id', '!=', $tracker->current_unit_id) as $u)
                                <option value="{{ $u->id }}" @selected((int) old('to_unit_id') === $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="purpose" class="{{ $label }}">Purpose <span class="text-rose-500">*</span></label>
                        <input id="purpose" name="purpose" value="{{ old('purpose') }}" required maxlength="300" placeholder="e.g. For recommendation, for filing" class="{{ $field }}">
                    </div>
                    <div class="sm:col-span-3"><label for="snotes" class="{{ $label }}">Notes</label><input id="snotes" name="notes" value="{{ old('notes') }}" maxlength="2000" class="{{ $field }}"></div>
                </div>
                <div class="flex justify-end"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Send</button></div>
            </form>
        @endif

        <section class="{{ $card }}">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Movement</h2>
            <ol class="mt-4 space-y-4 border-l border-slate-200 pl-5 dark:border-slate-700">
                @foreach ($tracker->movements as $m)
                    <li class="relative">
                        <span class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full ring-4 ring-white dark:ring-slate-900 {{ $m->action === 'received' ? 'bg-emerald-500' : ($m->action === 'sent' ? 'bg-amber-500' : 'bg-indigo-500') }}"></span>
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                            @if ($m->action === 'logged') Logged into {{ $m->toUnit?->name }}
                            @elseif ($m->action === 'sent') Sent from {{ $m->fromUnit?->name }} to {{ $m->toUnit?->name }}
                            @else Received in {{ $m->toUnit?->name }}
                            @endif
                            @if ($m->purpose)<span class="font-normal text-slate-600 dark:text-slate-400">· {{ $m->purpose }}</span>@endif
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $m->acted_by_name }} · {{ $m->acted_at?->format('d M Y, g:i a') }}</p>
                        @if ($m->notes)
                            <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-slate-800/60 dark:text-slate-300">{{ $m->notes }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-app-layout>
