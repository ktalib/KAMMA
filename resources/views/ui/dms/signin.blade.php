{{-- DMS > Print Sign In & Out Sheet (KLAES fileindexing/signin): pick a batch (or the date files were indexed), then print
     the SIGN IN & OUT SHEET: FILE NO, FILE NAME, PLOT NO, DISTRICT, LGA, INDEXED DATE, INDEXED BY, signatures in and out. --}}
<x-app-layout title="Sign In & Out">
    @php $field = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp

    <div class="space-y-6">
        <div class="print:hidden">
            <x-ui.page-head icon="clipboard-signature" title="Sign In & Out" subtitle="Print the sign in and out sheet for a batch of indexed files" tone="blue" section="DMS" />
        </div>

        @if ($error)<x-ui.klaes-note :error="$error" />@endif

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm print:hidden dark:border-slate-800 dark:bg-slate-900">
            <div>
                <label for="si-b" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Select batch</label>
                <select id="si-b" name="batch" class="{{ $field }} min-w-[12rem]"><option value="">Select batch</option>@foreach ($batches as $b)<option @selected($batch === $b)>{{ $b }}</option>@endforeach</select>
            </div>
            <div>
                <label for="si-d" class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">or date indexed</label>
                <input id="si-d" type="date" name="date" value="{{ $date }}" class="{{ $field }}">
            </div>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Show sheet</button>
            @if ($files->isNotEmpty())
                <button type="button" onclick="window.print()" class="ml-auto inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="printer" class="h-4 w-4"></i> Print</button>
            @endif
        </form>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print:border-0 print:p-0 print:shadow-none dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Kano State Metropolitan Area Management Agency (KLAES-METRO)</p>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">SIGN IN &amp; OUT SHEET</h2>
                @if ($batch !== '' || $date !== '')
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $batch !== '' ? 'Batch '.$batch : '' }}{{ $batch !== '' && $date !== '' ? ' · ' : '' }}{{ $date !== '' ? 'Indexed '.\Illuminate\Support\Carbon::parse($date)->format('d M Y') : '' }}</p>
                @endif
            </div>
            @if ($files->isEmpty())
                <x-ui.empty icon="clipboard-list" :title="$batch !== '' || $date !== '' ? 'No files in this batch' : 'Select a batch'">
                    {{ $batch !== '' || $date !== '' ? 'No shadow file matches.' : 'Pick a batch or a date to list its files.' }}
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-slate-300 text-xs dark:border-slate-700">
                        <thead class="bg-slate-50 text-left font-semibold uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <tr>
                                @foreach (['S/N', 'File No', 'File Name', 'Plot No', 'District', 'LGA', 'Indexed Date', 'Indexed By', 'Signed Out (Name / Sign / Date)', 'Signed In (Name / Sign / Date)'] as $h)
                                    <th class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="text-slate-800 dark:text-slate-200">
                            @foreach ($files as $f)
                                <tr>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap border border-slate-300 px-2 py-2 font-mono font-semibold dark:border-slate-700">{{ $f->file_number }}</td>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->file_title }}</td>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->plot_number }}</td>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->district }}</td>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->lga }}</td>
                                    <td class="whitespace-nowrap border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->date_matched ? \Illuminate\Support\Carbon::parse($f->date_matched)->format('d M Y') : '' }}</td>
                                    <td class="border border-slate-300 px-2 py-2 dark:border-slate-700">{{ $f->matched_by_name }}</td>
                                    <td class="w-40 border border-slate-300 px-2 py-2 dark:border-slate-700"></td>
                                    <td class="w-40 border border-slate-300 px-2 py-2 dark:border-slate-700"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
