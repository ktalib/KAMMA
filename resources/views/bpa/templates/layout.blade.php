<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />
    <title>{{ \App\Support\Bpa\BpaTemplates::title($slug) }} - {{ config('kamma.bpa.letterhead.short') }}</title>
    <style>
        /* A4 sheets that print as the paper forms (no app chrome). */
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #e2e8f0; }
        .toolbar { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 16px; background: #0f172a; color: #fff; font-size: 13px; }
        .toolbar a, .toolbar button { font: 600 13px Arial, sans-serif; padding: 7px 14px; border-radius: 6px; border: 1px solid #334155; background: #1e293b; color: #fff; text-decoration: none; cursor: pointer; }
        .toolbar .on { background: #047857; border-color: #047857; }
        .toolbar .print { background: #4f46e5; border-color: #4f46e5; margin-left: auto; }
        .toolbar .title { font-weight: 700; margin-right: 8px; }

        .page { position: relative; width: 210mm; min-height: 297mm; margin: 10mm auto; padding: 14mm 17mm 16mm; background: #fff; box-shadow: 0 2px 10px rgba(15, 23, 42, .18); font-size: 11pt; line-height: 1.45; display: flex; flex-direction: column; }
        .page-no { position: absolute; top: 3.5mm; right: 15mm; font-size: 10pt; line-height: 1; } /* above the header logos */

        /* Header with the seal (paper pages 1, 2). */
        .head { display: flex; align-items: center; gap: 5mm; padding-bottom: 2.5mm; border-bottom: 1.4px solid #000; margin-bottom: 5mm; }
        .head img { width: 27mm; height: 27mm; object-fit: contain; flex: none; }
        .head .txt { flex: 1; min-width: 0; }
        .head .name { font-weight: 900; font-size: 20pt; line-height: 1.12; }
        .head .name.caps { font-size: 12pt; text-transform: uppercase; }
        .head .addr { font-size: 10pt; margin-top: 1mm; }
        .head-plain { display: flex; align-items: center; gap: 4mm; text-align: center; margin-bottom: 5mm; }
        .head-plain img { width: 19mm; height: 19mm; object-fit: contain; flex: none; }
        .head-plain > div { flex: 1; }
        .head-pair { display: flex; align-items: center; justify-content: space-between; gap: 4mm; text-align: center; margin: 1mm 0 3mm; }
        .head-pair img { width: 20mm; height: 20mm; object-fit: contain; flex: none; }

        /* KLAES logo, pinned to the bottom right of the last sheet (outside the flow, so it never pushes the form onto a new sheet). */
        .tpl-foot { position: absolute; right: 15mm; bottom: 5mm; }
        .tpl-foot img { height: 8.5mm; width: auto; display: block; }

        /* QR + security code on the right of the letterhead (memo, conveyance), as on KLAES's Land RofO. */
        .qr-block { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 1mm; width: 24mm; }
        .qr-block .qr { width: 20mm; height: 20mm; }
        .qr-block .qr svg { width: 100%; height: 100%; display: block; }
        .qr-block .qr-blank { border: 1.2px dashed #64748b; display: flex; align-items: center; justify-content: center; font: 700 9pt Arial, sans-serif; color: #64748b; }
        .sec-code { display: inline-flex; align-items: center; gap: 1mm; color: #334155; font-family: Arial, sans-serif; line-height: 1; }
        .sec-code .frac { display: inline-flex; flex-direction: column; align-items: center; font-weight: 900; font-size: 6.5pt; }
        .sec-code .frac .top { border-bottom: 1.3px solid #334155; padding-bottom: .3mm; margin-bottom: .3mm; }
        .sec-code .end { font: 900 10.5pt 'Courier New', monospace; letter-spacing: .1em; }
        .sec-code.sec-blank { font-size: 6.5pt; font-weight: 700; color: #64748b; border-bottom: 1.2px dotted #64748b; width: 20mm; padding-bottom: .5mm; text-align: center; display: block; }
        .head-plain .name { font-weight: 900; font-size: 12pt; text-transform: uppercase; letter-spacing: .01em; white-space: nowrap; }
        .head-plain .addr { font-size: 9.5pt; font-weight: 600; }

        h1 { font-size: 13pt; text-align: center; text-decoration: underline; font-weight: 800; margin: 2mm 0 4mm; text-transform: uppercase; }
        h2 { font-size: 11pt; text-decoration: underline; font-weight: 800; margin: 4mm 0 2mm; text-transform: uppercase; }

        /* Blanks and boxes. */
        .dots { display: inline-block; min-width: 20mm; border-bottom: 1.3px dotted #000; padding: 0 1.5mm; font-weight: 700; line-height: 1.25; vertical-align: baseline; }
        .row { display: flex; align-items: baseline; gap: 1.5mm; margin: 1.2mm 0; }
        .row > .dots { flex: 1; }
        .row .lbl { flex: none; }
        .row .unit { flex: none; font-weight: 700; }
        .box { display: inline-flex; align-items: center; justify-content: center; width: 5.5mm; height: 4.6mm; border: 1.4px solid #000; border-radius: 1.3mm; font-size: 10pt; font-weight: 900; vertical-align: middle; margin-left: 2mm; }
        .yesno { font-weight: 700; flex: none; }
        .yesno .off { font-weight: 400; text-decoration: line-through; }
        .pick { font-weight: 900; text-decoration: underline; }
        .tick { font-weight: 900; margin-left: 2mm; }

        .sig { display: flex; justify-content: space-between; gap: 12mm; margin-top: 10mm; }
        .sig > div { flex: 1; text-align: center; }
        .sig .line { border-bottom: 1.3px dotted #000; min-height: 6mm; font-weight: 700; }
        .sig .cap { font-weight: 700; margin-top: 1mm; }
        .spacer { flex: 1; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; break-after: page; min-height: 296mm; } /* 1mm under A4 so rounding never spills a blank sheet */
            .page:last-of-type { break-after: auto; }
        }
        @media (max-width: 820px) { .page { transform-origin: top left; } }
    </style>
    @stack('styles')
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('bpa.templates.index') }}">&larr; Templates</a>
        <span class="title">{{ \App\Support\Bpa\BpaTemplates::title($slug) }}</span>
        <a href="{{ route('bpa.templates.show', $slug) }}" class="{{ $sample ? '' : 'on' }}">Blank</a>
        <a href="{{ route('bpa.templates.show', [$slug, 'sample' => 1]) }}" class="{{ $sample ? 'on' : '' }}">Sample data</a>
        <button type="button" class="print" onclick="window.print()">Print</button>
    </div>

    @yield('pages')
</body>
</html>
