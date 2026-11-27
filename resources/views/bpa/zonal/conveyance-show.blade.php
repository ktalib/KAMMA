{{-- The Development Permit (KAMMA FORM.pdf template 1) as raised, printable. KAMMA / Kano wording in place of the paper
     template's "Gombe State Urban Planning and Development Authority" (template defect, kadca-workflow skill). --}}
<x-app-layout title="Development Permit">
    @php $c = $conveyance; $a = $c->application; @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('bpa.conveyance') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"><i data-lucide="arrow-left" class="h-4 w-4"></i> Final Conveyance</a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i data-lucide="printer" class="h-4 w-4"></i> Print permit</button>
        </div>
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <article class="mx-auto max-w-2xl rounded-xl border-4 border-double border-emerald-700 bg-white p-10 text-center text-sm text-slate-900 print:rounded-none">
            <img src="{{ asset('images/logo.png') }}" alt="" class="mx-auto h-20 w-20 object-contain">
            <p class="mt-2 font-black uppercase tracking-wide">Kano State Development Control Agency (KAMMA)</p>
            <p class="mt-4 text-2xl font-black tracking-widest text-emerald-800">DEVELOPMENT PERMIT</p>
            <p class="mt-5">This is to certify that Permission is hereby granted to:</p>
            <p class="mt-1 text-lg font-bold">{{ $c->applicant }}</p>
            <p class="mt-2">To undertake the approved development at Plot No. <span class="font-semibold">{{ $a->plot_no ?: '______' }}</span>{{ collect([$a->district, $a->lga, $a->location])->filter()->map(fn ($v) => ', '.$v)->implode('') }}</p>
            <p class="mt-1 text-xs">In accordance with the approved plans, documents and conditions attached to this permit.</p>

            <dl class="mx-auto mt-6 grid max-w-md grid-cols-2 gap-1 text-left">
                <dt>Development Permit No.:</dt><dd class="font-mono font-semibold">{{ $c->permit_no }}</dd>
                <dt>Date of Approval:</dt><dd>{{ $c->date_of_approval?->format('d F Y') }}</dd>
                <dt>Permit Commencement Date:</dt><dd>{{ $c->commencement_date?->format('d F Y') }}</dd>
                <dt>Permit Expiry Date:</dt><dd>{{ $c->expiry_date?->format('d F Y') }}</dd>
                <dt>BP File No.:</dt><dd class="font-mono">{{ $a->bp_file_no }}</dd>
                <dt>Land File No.:</dt><dd class="font-mono">{{ $a->file_number }}</dd>
            </dl>

            <div class="mt-6 text-left">
                <p class="font-semibold">Note that this permit shall become invalid if:</p>
                <ul class="mt-1 list-inside list-disc text-xs">@foreach ($c->conditions as $cond)<li>{{ $cond }}</li>@endforeach</ul>
                @if ($c->additional_conditions)<p class="mt-2 whitespace-pre-line text-xs">{{ $c->additional_conditions }}</p>@endif
                <p class="mt-2 text-xs">The permit may be renewed, suspended or revoked in accordance with applicable laws and regulations of Kano State Development Control Agency (KAMMA).</p>
            </div>

            <p class="mt-6 text-left text-xs">I/We confirm that the information supplied in this application is true and complete.</p>
            <div class="mt-12 grid grid-cols-2 gap-8 text-xs">
                <div class="border-t border-slate-500 pt-1">{{ $c->declarant_name }}<br>Applicant/Developer {{ $c->declarant_date ? '· '.$c->declarant_date->format('d/m/Y') : '' }}</div>
                <div class="border-t border-slate-500 pt-1">{{ $c->authorized_officer }}<br>Authorized Officer {{ $c->officer_date ? '· '.$c->officer_date->format('d/m/Y') : '' }}</div>
            </div>
        </article>
    </div>
</x-app-layout>
