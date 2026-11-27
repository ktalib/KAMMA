<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-favicon />
    <title>Label {{ $tracker->tracking_id }} - KLAES-METRO</title>
    <style>
        /* A file label: 100 × 60 mm, printed as is (no app chrome). */
        @page { size: 100mm 60mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #0f172a; background: #f1f5f9; }
        .sheet { width: 100mm; height: 60mm; margin: 12mm auto; background: #fff; border: 0.3mm solid #cbd5e1; padding: 4mm; display: flex; gap: 4mm; }
        .qr { width: 34mm; flex: none; display: flex; flex-direction: column; align-items: center; }
        .qr svg { width: 34mm; height: 34mm; }
        .tid { margin-top: 1.5mm; font: 700 8.5pt/1.1 'Courier New', monospace; letter-spacing: .02em; text-align: center; }
        .info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .brand { display: flex; align-items: center; gap: 2mm; border-bottom: 0.3mm solid #e2e8f0; padding-bottom: 1.5mm; margin-bottom: 2mm; }
        .brand img { width: 9mm; height: 9mm; object-fit: contain; }
        .brand b { font-size: 8pt; letter-spacing: .04em; }
        .brand span { display: block; font-size: 6pt; color: #475569; }
        .ref { font-size: 13pt; font-weight: 700; line-height: 1.15; }
        .kind { font-size: 6.5pt; text-transform: uppercase; letter-spacing: .08em; color: #4f46e5; margin-bottom: .5mm; }
        .line { font-size: 7.5pt; line-height: 1.3; margin-top: 1mm; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .muted { color: #475569; }
        .actions { text-align: center; margin: 0 auto 10mm; }
        .actions button { font: 600 10pt Arial, sans-serif; padding: 2.5mm 6mm; border-radius: 2mm; border: 0; background: #4f46e5; color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .sheet { margin: 0; border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    @php $a = $tracker->shadowFile; @endphp
    <div class="sheet">
        <div class="qr">
            {!! \App\Support\Qr::svg($tracker->tracking_id, 200) !!}
            <div class="tid">{{ $tracker->tracking_id }}</div>
        </div>
        <div class="info">
            <div class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <div><b>KLAES-METRO</b><span>Kano State Metropolitan Area Management Agency</span></div>
            </div>
            <div class="kind">{{ $tracker->kind === 'mail' ? 'Mail' : 'KAMMA file' }}</div>
            <div class="ref">{{ $tracker->reference }}</div>
            <div class="line">{{ $tracker->title }}</div>
            @if ($a)
                <div class="line muted">{{ collect([$a->plot_number ? 'Plot '.$a->plot_number : null, $a->district, $a->lga])->filter()->implode(' · ') }}</div>
                <div class="line muted">KLAES land file {{ $a->file_number }}</div>
            @elseif ($tracker->mail_from)
                <div class="line muted">From {{ $tracker->mail_from }}</div>
            @endif
        </div>
    </div>
    <div class="actions"><button type="button" onclick="window.print()">Print label</button></div>
</body>
</html>
