{{-- System Admin > Verify QRCode, copied from KLAES information_products/verification (paste a QR payload or type a
     reference, see the matching record, or "not found"). Looks up this app's own documents by the number its QR carries. --}}
<x-app-layout title="Verify QRCode">
    @php
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    @endphp

    <div class="space-y-6">
        <x-ui.page-head icon="scan-qr-code" title="Verify QRCode" subtitle="Check that a printed document was issued by this system" tone="slate" section="System Admin" />

        <x-ui.panel title="Document reference">
            <form method="GET" action="{{ route('admin.verify') }}" class="flex flex-col gap-3 sm:flex-row">
                <label for="verify-ref" class="sr-only">Reference</label>
                <input id="verify-ref" name="ref" value="{{ $ref }}" maxlength="100" autofocus placeholder="Paste the QR payload or type a reference: {{ implode(', ', $examples) }}" class="{{ $field }}">
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"><i data-lucide="search-check" class="h-4 w-4"></i> Verify</button>
            </form>
        </x-ui.panel>

        @if ($ref !== '')
            @if (($result['kind'] ?? null) === 'found')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white"><i data-lucide="badge-check" class="h-5 w-5"></i></span>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Genuine</p><h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $result['title'] }}</h2></div>
                    </div>
                    <dl class="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($result['rows'] as $k => $v)
                            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $k }}</dt><dd class="mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-200">{{ filled($v) ? $v : '—' }}</dd></div>
                        @endforeach
                    </dl>
                    @if (! empty($result['link']))
                        <a href="{{ $result['link'] }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-300"><i data-lucide="external-link" class="h-4 w-4"></i> Open record</a>
                    @endif
                </div>
            @elseif (($result['kind'] ?? null) === 'error')
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">The records could not be searched just now. Try again.</div>
            @else
                <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-6 dark:border-rose-500/30 dark:bg-rose-500/10">
                    <i data-lucide="shield-alert" class="mt-0.5 h-5 w-5 text-rose-600 dark:text-rose-300"></i>
                    <div><p class="font-semibold text-rose-800 dark:text-rose-200">Not found</p><p class="text-sm text-rose-700 dark:text-rose-300">No document with reference <span class="font-mono">{{ $ref }}</span> was issued by this system. Check the reference, and treat the document as suspect until it is confirmed.</p></div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
