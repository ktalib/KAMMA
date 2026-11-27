{{-- DMS > Indexing > File SerialNo Grouping > Print Files Label: KLAES's printlabel/index COPIED (user 2026-11-25, "check
     the KLAES and copy it"). Page, tabs (Select Files, Generated Batches, Label Settings, Preview & Print), Batch Mode,
     rack/shelf, 30-in-1 A4 QR labels and the print window are KLAES's; data = KAMMA shadow files (Dms\PrintLabelController).
     KLAES's light-only classes get dark partners from the scoped .pfl rules below (light + dark on every KAMMA screen). --}}
<x-app-layout title="Print Files Label" :bare="true">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('dms.printlabel.assets.labels-css')
    <style>
        .dark .pfl .bg-white { background-color: #0f172a; }
        .dark .pfl .bg-gray-50, .dark .pfl .bg-slate-50 { background-color: #1e293b; }
        .dark .pfl .border, .dark .pfl .border-b, .dark .pfl .border-t, .dark .pfl .border-gray-200, .dark .pfl .border-gray-300,
        .dark .pfl .border-slate-200, .dark .pfl .border-slate-300, .dark .pfl .divide-y > * + * { border-color: #334155; }
        .dark .pfl, .dark .pfl .text-gray-700, .dark .pfl .text-gray-900, .dark .pfl .text-slate-700, .dark .pfl .text-slate-800 { color: #e2e8f0; }
        .dark .pfl .text-gray-500, .dark .pfl .text-gray-600, .dark .pfl .text-slate-500, .dark .pfl .text-slate-600 { color: #94a3b8; }
        .dark .pfl input:not([type=checkbox]):not([type=radio]), .dark .pfl select, .dark .pfl textarea { background-color: #1e293b; color: #e2e8f0; border-color: #475569; }
        .dark .pfl .from-slate-50 { --tw-gradient-from: #1e293b; --tw-gradient-to: #0f172a; }
        .dark .pfl .hover\:bg-gray-50:hover { background-color: #1e293b; }
        .dark .pfl .bg-blue-50 { background-color: rgba(37, 99, 235, .12); }
        .dark .pfl .bg-amber-50 { background-color: rgba(245, 158, 11, .1); }
        .dark .pfl .preview-shell { background: rgba(30, 41, 59, .6); border-color: #475569; }
        .dark .pfl .preview-heading { color: #93c5fd; }
    </style>

    <div class="pfl min-h-full bg-slate-50 dark:bg-slate-950">
        @include('dms.printlabel.partials.page', [
            'availableFilesCount' => $availableFilesCount ?? 0,
            'showOnlyST' => false,
        ])
    </div>

    @push('scripts')
        @include('dms.printlabel.assets.js')
    @endpush
</x-app-layout>
