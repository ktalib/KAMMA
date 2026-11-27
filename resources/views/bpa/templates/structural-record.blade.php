{{-- Paper pages 4-5: Structural Drawing Record (two sheets). Data: owner, plot, floors, every YES/NO answer, every
     required/provided steel area (mm2/m) and link size/spacing, general comments, date and reporting officer.
     Item keys are q<item><letter>, e.g. q6b = item 6 (b). --}}
@extends('bpa.templates.layout')

@push('styles')
<style>
    .sr { font-size: 9.6pt; font-weight: 700; line-height: 1.55; }
    .sr h1 { font-size: 11pt; }
    .sr .lead { text-align: center; font-weight: 800; font-size: 11.5pt; margin: 4mm 0 5mm; }
    .sr .q { display: grid; grid-template-columns: 7mm 1fr; margin: 2mm 0; }
    .sr .sub { display: grid; grid-template-columns: 5mm 1fr; }
    .sr .row { margin: .6mm 0; }
    .sr .row .dots { min-width: 12mm; text-align: center; }
    .sr .top .row { margin: 4mm 0; }
    .sr .span5 { display: grid; grid-template-columns: 5mm 60mm 1fr; align-items: baseline; }
    .sr .span5 .row { margin: 0; }
    .sr .comment { display: block; border-bottom: 1.3px dotted #000; min-height: 7mm; margin-top: 3mm; font-weight: 700; }
    .sr .formula { display: inline-grid; text-align: center; vertical-align: middle; margin-left: 4mm; }
    .sr .formula u { text-decoration: underline; }
</style>
@endpush

@php
    // A line ending in a value: "label .......... unit" (unit mm2/m, or a YES/NO answer).
    $line = fn (string $key, string $label, string $unit = 'mm2/m') => ['key' => $key, 'label' => $label, 'unit' => $unit];
@endphp

@section('pages')
{{-- Sheet 1 (paper page 4) --}}
<div class="page sr">
    <span class="page-no" style="font-size:16pt">4</span>
    @include('bpa.templates.partials.header', ['style' => 'title'])
    <h1>Structural Drawing Record</h1>

    <div class="top">
        <div class="row"><span class="lbl">1)&nbsp; NAME OF OWNER</span><x-tpl.dots :v="$d['owner_name'] ?? null" /></div>
        <div class="row"><span class="lbl">2)&nbsp; PLOT NO.</span><x-tpl.dots :v="$d['plot_no'] ?? null" /></div>
        <div class="row"><span class="lbl">3)&nbsp; NUMBER OF FLOORS</span><x-tpl.dots :v="$d['floors'] ?? null" /></div>
    </div>

    <div class="q"><span>4)</span><div>
        <div>a) IS THE GENERAL ARRANGEMENT FOR FOUNDATION AND FLOOR</div>
        <div class="row" style="padding-left:9mm;margin-top:2mm"><span class="lbl">PLANS SATISFACTORY?</span><x-tpl.dots /><x-tpl.yesno :v="$d['q4a'] ?? null" /></div>
        <div class="row"><span class="lbl">b) IS THE PROVISION OF EXPANSION JOINT NECESSARY</span><x-tpl.dots /><x-tpl.yesno :v="$d['q4b'] ?? null" /></div>
    </div></div>
    <div class="q"><span>5)</span><div>
        <div>IS THE SPAN/EFFECTIVE DEPTH RATIO WITHIN THE ACCEPTABLE LIMIT<br>FOR THE FOLLOWING:-</div>
        @foreach (['a' => 'CANTILEVER &nbsp;7', 'b' => 'SIMPLY SUPPORTED &nbsp;20', 'c' => 'CONTINUOUS &nbsp;26'] as $k => $label)
            <div class="span5"><span>{{ $k }})</span><span>{!! $label !!}</span><span class="row"><x-tpl.dots /><x-tpl.yesno :v="$d['q5'.$k] ?? null" /></span></div>
        @endforeach
    </div></div>

    <h1 style="font-size:10.5pt;margin-top:5mm">Beams (Clause 3.11.4)</h1>
    <div class="q" style="font-size:10.6pt"><span>6)</span><div>
        @foreach ([
            $line('q6a', 'a) The minimum area of high tensile steel for beam is 0.15% bd'),
            $line('q6b', 'b) The area provided in the drawing is'),
            $line('q6c', 'c) The minimum area of mild steel for beam is 0.25% bd'),
            $line('q6d', 'd) The area provided in the drawing is'),
            $line('q6e', 'e) Is the diameter of links satisfactory?', 'yesno'),
            $line('q6f', 'f) The maximum link spacing allowed is 12 x (steel diameter)', ''),
            $line('q6g', 'g) The links spacing provided in the drawing is', ''),
        ] as $l)
            <div class="row"><span class="lbl">{{ $l['label'] }}</span>@if ($l['unit'] === 'yesno')<x-tpl.dots /><x-tpl.yesno :v="$d[$l['key']] ?? null" />@else<x-tpl.dots :v="$d[$l['key']] ?? null" />@if ($l['unit'])<span class="unit">{{ $l['unit'] }}</span>@endif @endif</div>
        @endforeach
    </div></div>

    <h1 style="font-size:10.5pt;margin-top:5mm">Slabs (Clause 3.11.4) CP 110</h1>
    <div class="q" style="font-size:9pt"><span>7)</span><div>
        @foreach ([
            $line('q7a', 'a) The minimum area of high tensile steel for main reinforcement is 0.15% btd'),
            $line('q7b', 'b) The area provided in the drawing is'),
            $line('q7c', 'c) The minimum of mild steel for beam is 0.25% btd'),
            $line('q7d', 'd) The area provided in the drawing is'),
            $line('q7e', 'e) The minimum area of high tensile steel for distribution reinforcement is 0.12%btd'),
            $line('q7f', 'f) The area provided in the drawing is'),
            $line('q7g', 'g) The minimum area of mild steel as distribution is 0.15%btd'),
            $line('q7h', 'h) The area provided in the drawing is'),
        ] as $l)
            <div class="row"><span class="lbl">{{ $l['label'] }}</span><x-tpl.dots :v="$d[$l['key']] ?? null" /><span class="unit">{{ $l['unit'] }}</span></div>
        @endforeach
    </div></div>
</div>

{{-- Sheet 2 (paper page 5) --}}
<div class="page sr">
    <span class="page-no" style="font-size:16pt">5</span>
    <h1 style="margin-top:6mm">Columns (Clause 3.5.1.1. &amp; 3.11.5) CP 110</h1>

    @foreach ([
        ['8)', [$line('q8a', 'a) The minimum area of steel allowed for lightly loaded col. is 1% bd (1-3 Floors)'), $line('q8b', 'b) The area provided in the drawing is')]],
        ['9)', [$line('q9a', 'a) The minimum area of steel allowed for medium loaded col. is 2%bd (4-7 Floors)'), $line('q9b', 'b) The area provided in the drawing is')]],
    ] as [$n, $lines])
        <div class="q"><span>{{ $n }}</span><div>
            @foreach ($lines as $l)
                <div class="row"><span class="lbl">{{ $l['label'] }}</span><x-tpl.dots :v="$d[$l['key']] ?? null" /><span class="unit">{{ $l['unit'] }}</span></div>
            @endforeach
        </div></div>
    @endforeach
    <div class="q"><span>10)</span><div>
        <div>a) The minimum area of steel allowed for lightly heavy/heavily loaded col. is 3-6%bd</div>
        <div class="row" style="padding-left:4mm"><span class="lbl">(8 Floors &amp; above)</span><x-tpl.dots :v="$d['q10a'] ?? null" style="flex:0 0 40mm" /><span class="unit">mm2/m</span></div>
        <div class="row"><span class="lbl">b) The area provided in the drawing is</span><x-tpl.dots :v="$d['q10b'] ?? null" /><span class="unit">mm2/m</span></div>
    </div></div>
    <div class="q"><span>11)</span><div>
        <div class="row"><span class="lbl">a) Is the diameter of links satisfactory?</span><x-tpl.dots /><x-tpl.yesno :v="$d['q11a'] ?? null" /></div>
        <div class="row"><span class="lbl">b) The maximum link spacing allowed is 12 x (steel diameter)</span><x-tpl.dots :v="$d['q11b'] ?? null" /></div>
        <div class="row"><span class="lbl">c) The links spacing provided in the drawing is</span><x-tpl.dots :v="$d['q11c'] ?? null" /></div>
    </div></div>
    <p style="margin:3mm 0 4mm 7mm">NB The above depends on the size of the structure (i.e. moment of inertia, size of columns, grade of concrete and type of reinforcement</p>

    <h2>Foundation</h2>
    <div class="q"><span>12)</span><div>
        <div class="row"><span class="lbl">a) Is the area of base satisfactory?</span><x-tpl.dots /><x-tpl.yesno :v="$d['q12a'] ?? null" /></div>
        <div>b) Bond length minimum= &nbsp;L = <span class="formula"><u>dia.fs2</u><span>4fbs</span></span></div>
        <div style="margin:3mm 0 0 8mm">For fs2, clause 3.5.5.3.<br>For fbs table 22</div>
    </div></div>

    <h2>Walls</h2>
    <div class="q"><span>13)</span><div>
        @foreach ([
            $line('q13a', 'a) Shear wall minimum area of steel to be 0.4bh'),
            $line('q13b', 'b) The area provided in the drawing is'),
            $line('q13c', 'c) Shear wall (for fire resistance) minimum area is 1%'),
            $line('q13d', 'd) The area provided in the drawing is'),
        ] as $l)
            <div class="row"><span class="lbl">{{ $l['label'] }}</span><x-tpl.dots :v="$d[$l['key']] ?? null" /><span class="unit">{{ $l['unit'] }}</span></div>
        @endforeach
    </div></div>

    <h2>Staircase</h2>
    <p style="margin:2mm 0 4mm 7mm">To be treated as slab.</p>

    <h2 style="margin-left:7mm">General Comments:</h2>
    <div style="margin-left:7mm">
        <span class="comment">{{ $d['comments'] ?? '' }}</span>
        <span class="comment"></span>
    </div>

    <div class="row" style="margin:9mm 0 0 7mm">
        <span class="lbl">DATE</span><x-tpl.dots :v="$d['date'] ?? null" style="flex:0 0 45mm" />
        <span class="spacer"></span>
        <span class="lbl">REPORTING OFFICER</span><x-tpl.dots :v="$d['officer'] ?? null" style="flex:0 0 60mm" />
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
