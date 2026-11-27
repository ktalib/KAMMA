{{-- A saved KAMMA Memo, printable (same layout as the memo screen's live preview). --}}
<x-app-layout title="KAMMA Memo">
    @php $a = $memo->application; @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('bpa.memo') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"><i data-lucide="arrow-left" class="h-4 w-4"></i> KAMMA Memo</a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
        </div>
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <article class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-8 text-sm leading-relaxed text-slate-900 shadow-sm print:border-0 print:p-0 print:shadow-none">
            <header class="flex items-center gap-4 border-b-2 border-slate-800 pb-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-14 w-14 object-contain">
                <div><p class="font-black uppercase tracking-wide">Kano State Development Control Agency</p><p class="text-xs">Internal Memo</p></div>
            </header>
            <dl class="mt-4 grid grid-cols-[6rem_1fr] gap-y-1">
                <dt class="font-semibold">To:</dt><dd>{{ $memo->to }}</dd>
                @if ($memo->through)<dt class="font-semibold">Through:</dt><dd>{{ $memo->through }}</dd>@endif
                <dt class="font-semibold">From:</dt><dd>{{ $memo->from }}</dd>
                <dt class="font-semibold">Date:</dt><dd>{{ $memo->memo_date?->format('j F Y') }}</dd>
                <dt class="font-semibold">Ref:</dt><dd class="font-mono">{{ $memo->memo_no }} · {{ $a->bp_file_no }}</dd>
            </dl>
            <p class="mt-4 border-y border-slate-300 py-2 font-bold uppercase">{{ $memo->subject }}</p>
            <table class="mt-4 w-full border border-slate-300 text-xs">
                <tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1">Plot No.</th><th class="border border-slate-300 px-2 py-1">District</th><th class="border border-slate-300 px-2 py-1">LGA</th><th class="border border-slate-300 px-2 py-1">Location</th></tr>
                <tr><td class="border border-slate-300 px-2 py-1">{{ $a->plot_no ?: '—' }}</td><td class="border border-slate-300 px-2 py-1">{{ $a->district ?: '—' }}</td><td class="border border-slate-300 px-2 py-1">{{ $a->lga ?: '—' }}</td><td class="border border-slate-300 px-2 py-1">{{ $a->location ?: '—' }}</td></tr>
            </table>
            <p class="mt-4 whitespace-pre-line">{{ $memo->body }}</p>
            @if ($memo->recommendation)
                <div class="mt-4"><p class="font-bold">Recommendation</p><p class="whitespace-pre-line">{{ $memo->recommendation }}</p></div>
            @endif
            <div class="mt-12 w-56 border-t border-slate-500 pt-1 text-xs">{{ $memo->from }}</div>
        </article>
    </div>
</x-app-layout>
