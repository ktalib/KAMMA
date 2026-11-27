{{-- The Head of Zonal Office's Instruction to Inspect, printable (laid out like KLAES's Instruction to Surveyor). --}}
<x-app-layout title="Instruction to Inspect">
    @php $a = $assignment->application; @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('bpa.zonal-approval', ['tab' => 'assigned']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"><i data-lucide="arrow-left" class="h-4 w-4"></i> Head of Zonal Office</a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
        </div>

        <article class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-8 text-sm leading-relaxed text-slate-900 shadow-sm print:border-0 print:p-0 print:shadow-none">
            <header class="flex items-center gap-4 border-b-2 border-slate-800 pb-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-16 w-16 object-contain">
                <div>
                    <p class="font-black uppercase tracking-wide">Kano State Development Control Agency (KAMMA)</p>
                    <p class="text-xs">Zonal Office</p>
                </div>
            </header>
            <h1 class="mt-5 text-center text-lg font-black uppercase tracking-widest">Instruction to Inspect</h1>

            <dl class="mt-6 grid grid-cols-[11rem_1fr] gap-y-2">
                <dt class="font-semibold">Instruction No.:</dt><dd class="font-mono">{{ $assignment->instruction_no }}</dd>
                <dt class="font-semibold">Date:</dt><dd>{{ $assignment->issued_at?->format('d F Y') }}</dd>
                <dt class="font-semibold">To (Inspecting Officer):</dt><dd>{{ $assignment->officer_name }}{{ $assignment->officer_rank ? ', '.$assignment->officer_rank : '' }}</dd>
                <dt class="font-semibold">BP File No.:</dt><dd class="font-mono">{{ $a->bp_file_no }}</dd>
                <dt class="font-semibold">Land File No.:</dt><dd class="font-mono">{{ $a->file_number }}</dd>
                <dt class="font-semibold">File Title:</dt><dd>{{ $a->developer_name }}</dd>
            </dl>

            <table class="mt-5 w-full border border-slate-400 text-xs">
                <tr class="bg-slate-100"><th class="border border-slate-400 px-2 py-1">Plot No.</th><th class="border border-slate-400 px-2 py-1">District</th><th class="border border-slate-400 px-2 py-1">LGA</th><th class="border border-slate-400 px-2 py-1">Location</th></tr>
                <tr><td class="border border-slate-400 px-2 py-1">{{ $a->plot_no ?: '—' }}</td><td class="border border-slate-400 px-2 py-1">{{ $a->district ?: '—' }}</td><td class="border border-slate-400 px-2 py-1">{{ $a->lga ?: '—' }}</td><td class="border border-slate-400 px-2 py-1">{{ $a->location ?: '—' }}</td></tr>
            </table>

            <p class="mt-5 font-semibold">Scope</p>
            <ul class="list-inside list-disc">@foreach ($assignment->scope as $s)<li>{{ $s }}</li>@endforeach</ul>

            @if ($assignment->instructions)
                <p class="mt-4 font-semibold">Instructions</p>
                <p class="whitespace-pre-line">{{ $assignment->instructions }}</p>
            @endif

            <p class="mt-4">Your report, with geocoded photographs of the site, is due by <span class="font-semibold">{{ $assignment->due_date?->format('d F Y') }}</span>.</p>

            <div class="mt-14 grid grid-cols-2 gap-10 text-xs">
                <div class="border-t border-slate-600 pt-1">{{ $assignment->issued_by_name }}<br>Head of Zonal Office</div>
                <div class="border-t border-slate-600 pt-1">{{ $assignment->officer_name }}<br>Received by (signature / date)</div>
            </div>
        </article>
    </div>
</x-app-layout>
