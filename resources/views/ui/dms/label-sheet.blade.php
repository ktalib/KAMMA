{{-- Printable folder labels for shadow files (100 x 60 mm each, or 3 x 7 on A4). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>File labels — {{ config('app.name', 'KAMMA') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Figtree, Arial, sans-serif; color: #0f172a; background: #f1f5f9; }
        .bar { padding: 12px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; gap: 12px; align-items: center; }
        .bar button { padding: 8px 14px; border: 0; border-radius: 8px; background: #4f46e5; color: #fff; font-weight: 600; cursor: pointer; }
        .sheet { display: flex; flex-wrap: wrap; gap: 6mm; padding: 10mm; }
        .label { width: 100mm; height: 60mm; background: #fff; border: 1px dashed #94a3b8; padding: 4mm; display: flex; gap: 3mm; page-break-inside: avoid; }
        .a4 .label { width: 63mm; height: 38mm; padding: 2.5mm; gap: 2mm; }
        .qr svg { width: 34mm; height: 34mm; }
        .a4 .qr svg { width: 22mm; height: 22mm; }
        .meta { min-width: 0; display: flex; flex-direction: column; justify-content: space-between; }
        .agency { font-size: 8pt; font-weight: 700; letter-spacing: .04em; color: #4338ca; }
        .no { font-size: 13pt; font-weight: 800; font-family: Consolas, monospace; word-break: break-all; }
        .a4 .no { font-size: 9pt; }
        .title { font-size: 9pt; font-weight: 600; }
        .a4 .title, .a4 .prop { font-size: 7pt; }
        .prop { font-size: 8pt; color: #334155; }
        .empty { padding: 40px; text-align: center; color: #64748b; }
        @media print { body { background: #fff; } .bar { display: none; } .sheet { padding: 0; } .label { border-color: #cbd5e1; } }
    </style>
</head>
<body>
    <div class="bar"><button onclick="window.print()">Print</button><span>{{ $files->count() }} label(s) · {{ $format === 'a4' ? 'A4 sheet, 3 × 7' : '100 × 60 mm' }}</span></div>
    @if ($files->isEmpty())
        <p class="empty">No files were selected.</p>
    @else
        <div class="sheet {{ $format === 'a4' ? 'a4' : '' }}">
            @foreach ($files as $f)
                <div class="label">
                    <div class="qr">{!! \App\Support\Qr::svg($f->file_number, 160) !!}</div>
                    <div class="meta">
                        <div class="agency">KLAES-METRO · KAMMA</div>
                        <div class="no">{{ $f->file_number }}</div>
                        <div class="title">{{ \Illuminate\Support\Str::limit($f->file_title, 60) }}</div>
                        <div class="prop">Plot {{ $f->plot_number ?: '—' }} · {{ $f->district ?: '—' }} · {{ $f->lga ?: '—' }}</div>
                        <div class="prop">Batch {{ $f->batch_no ?: '—' }} · Serial {{ $f->serial_no ?: '—' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
