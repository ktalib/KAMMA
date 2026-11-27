<x-app-layout :title="$application->application_no">
    @php
        $card = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $h2 = 'text-base font-semibold text-slate-900 dark:text-slate-100';
        $dt = 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $dd = 'mt-1 break-words text-sm text-slate-900 dark:text-slate-100';
        $bill = $application->bill;
        $show = fn ($v) => filled($v) ? $v : '—';
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Building Plan Approval · Assessment Stage</p>
                <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ $application->application_no }}
                    @if ($application->bp_file_no)
                        <span class="ml-2 rounded-lg bg-indigo-50 px-2 py-0.5 text-base font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $application->bp_file_no }}</span>
                    @endif
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ $application->status }} · received {{ $application->created_at?->format('d M Y, g:i a') }}
                    @if ($application->commissioned_at)
                        · commissioned {{ $application->commissioned_at->format('d M Y') }} by {{ $application->commissioned_by_name }}
                    @endif
                </p>
            </div>
            <a href="{{ route('bpa.applications.index') }}" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Back to applications</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="{{ $card }} lg:col-span-2">
                <h2 class="{{ $h2 }}">Land file</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="{{ $dt }}">File No.</dt><dd class="{{ $dd }} font-medium">{{ $application->file_number }}</dd></div>
                    <div><dt class="{{ $dt }}">File title</dt><dd class="{{ $dd }}">{{ $show($application->file_title) }}</dd></div>
                    <div><dt class="{{ $dt }}">Plot No.</dt><dd class="{{ $dd }}">{{ $show($application->plot_no) }}</dd></div>
                    <div><dt class="{{ $dt }}">District</dt><dd class="{{ $dd }}">{{ $show($application->district) }}</dd></div>
                    <div><dt class="{{ $dt }}">LGA</dt><dd class="{{ $dd }}">{{ $show($application->lga) }}</dd></div>
                    <div><dt class="{{ $dt }}">Location</dt><dd class="{{ $dd }}">{{ $show($application->location) }}</dd></div>
                    <div><dt class="{{ $dt }}">Plot size</dt><dd class="{{ $dd }}">{{ $show($application->plot_size) }}</dd></div>
                    <div><dt class="{{ $dt }}">Title status</dt><dd class="{{ $dd }}">{{ $show($application->title_status) }}</dd></div>
                </dl>
            </section>

            <section class="{{ $card }}">
                <h2 class="{{ $h2 }}">Application bill</h2>
                @if ($bill)
                    <p class="mt-4 text-3xl font-semibold text-slate-900 dark:text-slate-100">₦{{ number_format((float) $bill->amount, 2) }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $bill->quantity }} plot(s) × ₦{{ number_format((float) $bill->rate, 2) }}</p>
                    <dl class="mt-4 space-y-3">
                        <div><dt class="{{ $dt }}">Bill No.</dt><dd class="{{ $dd }}">{{ $bill->bill_no }}</dd></div>
                        <div><dt class="{{ $dt }}">Status</dt>
                            <dd class="{{ $dd }} {{ $bill->status === 'Paid' ? '!text-emerald-600 dark:!text-emerald-400' : '!text-amber-600 dark:!text-amber-400' }}">{{ $bill->status }}</dd>
                        </div>
                        @if ($bill->status === 'Paid')
                            <div><dt class="{{ $dt }}">Receipt No.</dt><dd class="{{ $dd }}">{{ $bill->receipt_no }}</dd></div>
                            <div><dt class="{{ $dt }}">Paid</dt>
                                <dd class="{{ $dd }}">₦{{ number_format((float) $bill->amount_paid, 2) }} on {{ $bill->paid_on?->format('d M Y') }}, {{ $bill->payment_channel === 'Other' ? $bill->payment_channel_other : $bill->payment_channel }}</dd>
                            </div>
                            <div><dt class="{{ $dt }}">Recorded by</dt><dd class="{{ $dd }}">{{ $bill->payment_recorded_by_name }}</dd></div>
                        @endif
                    </dl>
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No bill raised.</p>
                @endif
            </section>
        </div>

        <section class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="{{ $h2 }}">Land-use check</h2>
                <x-land-use-badge :check="$application->land_use_check" />
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-3">
                <div><dt class="{{ $dt }}">Proposed use</dt><dd class="{{ $dd }}">{{ $application->proposed_use === 'Other' ? 'Other: '.$application->proposed_use_other : $application->proposed_use }}</dd></div>
                <div><dt class="{{ $dt }}">Land use (land file)</dt><dd class="{{ $dd }}">{{ $show($application->file_land_use) }}</dd></div>
                <div><dt class="{{ $dt }}">Result</dt><dd class="{{ $dd }}">{{ $application->land_use_note }}</dd></div>
            </dl>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">Developer</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-3"><dt class="{{ $dt }}">Name of developer</dt><dd class="{{ $dd }} font-medium">{{ $application->developer_name }}</dd></div>
                <div><dt class="{{ $dt }}">Cadastral zone</dt><dd class="{{ $dd }}">{{ $show($application->cadastral_zone) }}</dd></div>
                <div><dt class="{{ $dt }}">Cadastral zone / Plot No.</dt><dd class="{{ $dd }}">{{ $show($application->cadastral_zone_plot_no) }}</dd></div>
                <div><dt class="{{ $dt }}">Street name</dt><dd class="{{ $dd }}">{{ $show($application->street_name) }}</dd></div>
                <div><dt class="{{ $dt }}">Dwelling plot No. / Floor</dt><dd class="{{ $dd }}">{{ $show($application->dwelling_plot_floor) }}</dd></div>
                <div><dt class="{{ $dt }}">Dwelling unit</dt><dd class="{{ $dd }}">{{ $show($application->dwelling_unit) }}</dd></div>
                <div class="sm:col-span-2 lg:col-span-3"><dt class="{{ $dt }}">Current contact address</dt><dd class="{{ $dd }}">{{ $show($application->contact_address) }}</dd></div>
                <div><dt class="{{ $dt }}">Developer's mobile No.</dt><dd class="{{ $dd }}">{{ $show($application->developer_phone) }}</dd></div>
                <div><dt class="{{ $dt }}">Developer's email</dt><dd class="{{ $dd }}">{{ $show($application->developer_email) }}</dd></div>
                <div><dt class="{{ $dt }}">Representative's mobile No.</dt><dd class="{{ $dd }}">{{ $show($application->representative_phone) }}</dd></div>
                <div><dt class="{{ $dt }}">Representative's email</dt><dd class="{{ $dd }}">{{ $show($application->representative_email) }}</dd></div>
            </dl>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">Submission</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="{{ $dt }}">Type of submission</dt><dd class="{{ $dd }}">{{ $application->submission_type === 'Other' ? 'Other: '.$application->submission_type_other : $application->submission_type }}</dd></div>
                <div><dt class="{{ $dt }}">Submitted by</dt><dd class="{{ $dd }}">{{ $show($application->submitted_by_name) }}@if ($application->submitted_on), {{ $application->submitted_on->format('d M Y') }}@endif</dd></div>
                <div>
                    <dt class="{{ $dt }}">Documents submitted</dt>
                    <dd class="{{ $dd }}">
                        @forelse ($application->documents_submitted ?? [] as $doc)
                            <span class="block">{{ $doc === 'Other' ? 'Other: '.$application->documents_other : $doc }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="{{ $dt }}">Drawings / reports submitted</dt>
                    <dd class="{{ $dd }}">
                        @forelse ($application->drawings_submitted ?? [] as $drawing)
                            <span class="block">{{ $drawing }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
                <div><dt class="{{ $dt }}">Number of plots</dt><dd class="{{ $dd }}">{{ $application->number_of_plots }}</dd></div>
                @if ($application->portal_applicant_id && blank($application->received_by_name))
                    <div><dt class="{{ $dt }}">Receiving officer</dt><dd class="{{ $dd }}">Sent by the applicant on the KLAES-METRO portal</dd></div>
                @else
                    <div><dt class="{{ $dt }}">Receiving officer</dt><dd class="{{ $dd }}">{{ $show($application->received_by_name) }}@if (filled($application->received_by_rank)), {{ $application->received_by_rank }}@endif</dd></div>
                @endif
            </dl>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">Building plan</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $application->plan_received_as }}</p>
            <ul class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                @forelse ($application->files as $file)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $file->original_name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $file->kind === 'cad' ? 'CAD drawing' : 'Plan' }} · {{ strtoupper($file->extension) }} · {{ number_format($file->size_bytes / 1048576, 2) }} MB</p>
                        </div>
                        <a href="{{ route('bpa.applications.files.download', [$application, $file]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Download</a>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">No files.</li>
                @endforelse
            </ul>
        </section>

        <section class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="{{ $h2 }}">File movement</h2>
                <span class="text-sm text-slate-500 dark:text-slate-400">Now with: <span class="font-medium text-slate-900 dark:text-slate-100">{{ \App\Support\Bpa\BpaStage::label($application->stage) }}</span></span>
            </div>
            @if ($application->tracker)
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Physical file:
                    <a href="{{ route('tracking.show', $application->tracker) }}" class="font-mono font-semibold text-indigo-700 hover:underline dark:text-indigo-300">{{ $application->tracker->tracking_id }}</a>
                    <x-tracking.status :tracker="$application->tracker" class="ml-1" />
                </p>
            @endif
            @if ($application->movements->isEmpty())
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">The file has not moved between desks yet.</p>
            @else
                <ol class="mt-4 space-y-4 border-l border-slate-200 pl-5 dark:border-slate-700">
                    @foreach ($application->movements as $move)
                        @php
                            $what = match ($move->action) {
                                'commissioned' => 'Commissioned and sent to the Director (Arch.)',
                                'paid' => 'Paid; BP file '.$application->bp_file_no.' sent to the Director (Arch.)',
                                'recommended' => 'Recommended by the '.\App\Support\Bpa\BpaStage::label($move->from_stage).', sent to the '.\App\Support\Bpa\BpaStage::label($move->to_stage),
                                'not_recommended' => 'Not recommended by the '.\App\Support\Bpa\BpaStage::label($move->from_stage).', returned to '.\App\Support\Bpa\BpaStage::label($move->to_stage),
                                'resent' => 'Corrected and sent back to the Director (Arch.)',
                                default => $move->action,
                            };
                            $dot = match ($move->action) {
                                'not_recommended' => 'bg-rose-500',
                                'recommended' => 'bg-emerald-500',
                                default => 'bg-indigo-500',
                            };
                        @endphp
                        <li class="relative">
                            <span class="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full ring-4 ring-white dark:ring-slate-900 {{ $dot }}"></span>
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $what }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $move->acted_by_name }}@if ($move->acted_by_rank), {{ $move->acted_by_rank }}@endif · {{ $move->acted_at?->format('d M Y, g:i a') }}
                            </p>
                            @if ($move->remarks)
                                <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-slate-800/60 dark:text-slate-300">{{ $move->remarks }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-app-layout>
