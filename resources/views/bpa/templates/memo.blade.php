{{-- Planning memo (photo sent 2026-11-24): the planning department's memo to the Managing Director on an application for
     a Statutory Right of Occupancy, minuted by hand in the margin. KNUPDA's "Urban Planning Department" is KAMMA's Physical
     Planning (user 2026-11-24). Data: the memo's file no. and date, To / From, the application type, every value beside a
     label, the signatory and their title. Blank prints dotted lines; the margin stays clear for the minutes. --}}
@extends('bpa.templates.layout')

@push('styles')
<style>
    .page.mm { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.38; }
    .mm .memo-head { border-bottom: 0; margin-bottom: 1mm; align-items: flex-start; }
    .mm .memo-head img { width: 24mm; height: 24mm; }
    .mm .head .name.caps { font-size: 11pt; }
    .mm .head .addr { font-size: 8.5pt; margin-top: .3mm; }
    .mm .memo-ref { font-weight: 800; font-size: 9pt; text-transform: uppercase; text-decoration: underline; margin-top: .6mm; }
    .mm .memo-ref .dots { text-decoration: none; font-size: 9pt; }
    .mm .date { text-align: right; font-weight: 700; font-size: 9.5pt; margin: 0 0 4mm; }
    .mm .date .dots { min-width: 42mm; }
    .mm .f { display: grid; grid-template-columns: 37mm 1fr; column-gap: 2mm; margin: 0 0 3.2mm; }
    .mm .f > .k { font-weight: 800; }
    .mm .f > .v { font-weight: 800; text-align: justify; }
    .mm .f > .v .dots { min-width: 25mm; }
    .mm .f.tight { margin-bottom: .4mm; }
    .mm .num { font-weight: 800; }
    .mm .f > .v.subj { text-decoration: underline; text-transform: uppercase; text-align: left; }
    .mm .subj .dots { text-decoration: none; }
    .mm .full { display: block; width: 100%; min-height: 1.3em; margin-bottom: .9mm; }
    .mm .wide { grid-template-columns: 1fr; }
    .mm .wide > .k { display: inline; }
    .mm .closing { font-weight: 800; margin: 4mm 0 0 39mm; text-align: justify; }
    .mm .signed { margin-top: 13mm; width: 75mm; }
    .mm .signed .line { border-bottom: 1.3px dashed #000; height: 9mm; }
    .mm .signed .who { font-weight: 800; font-size: 11.5pt; margin-top: 1mm; text-transform: uppercase; min-height: 5mm; }
    .mm .signed .what { font-weight: 800; }
</style>
@endpush

@php
    $department = $d['department'] ?? 'Physical Planning Department';
    $from = $d['from'] ?? 'Director Physical Planning';
    $fileNo = $d['file_no'] ?? null;
    // The site report sentence writes the use in lower case ("for a residential use").
    $d['proposed_use_lc'] = isset($d['proposed_use']) ? mb_strtolower($d['proposed_use']) : null;
    $dot = fn ($key, $min = null) => '<span class="dots"'.($min ? ' style="min-width:'.$min.'"' : '').'>'.e($d[$key] ?? '').'</span>';
    // Dimension (user 2026-11-24): a filled value prints plain, with no line under it; blank keeps the dots. Road reservation keeps its line.
    $num = fn ($key, $min = null) => filled($d[$key] ?? null) ? '<span class="num">'.e($d[$key]).'</span>' : $dot($key, $min);
@endphp

@section('pages')
<div class="page mm">
    @include('bpa.templates.partials.header', ['style' => 'memo', 'department' => $department, 'memoOn' => $fileNo, 'qr' => ['qr' => $fileNo, 'code' => $d['security_code'] ?? null]])
    <div class="date"><x-tpl.dots :v="$d['date'] ?? null" /></div>

    <div class="f tight"><span class="k">To:</span><span class="v">{{ $d['to'] ?? 'Managing Director' }}</span></div>
    <div class="f"><span class="k">From:</span><span class="v">{{ $from }}.</span></div>

    <div class="f">
        <span class="k">Subject:</span>
        <span class="v subj">RE: Application for {!! $dot('application_type', '55mm') !!}: <x-tpl.dots :v="$fileNo" style="min-width:38mm" /></span>
    </div>

    <div class="f tight"><span class="k">Applicant:</span><span class="v"><x-tpl.dots :v="$d['applicant'] ?? null" class="full" /></span></div>
    <div class="f tight"><span class="k">Proposed use:</span><span class="v"><x-tpl.dots :v="isset($d['proposed_use']) ? $d['proposed_use'].' use' : null" class="full" /></span></div>
    <div class="f"><span class="k">Location:</span><span class="v"><x-tpl.dots :v="$d['location'] ?? null" class="full" /></span></div>

    <div class="f">
        <span class="k">Site report:</span>
        <span class="v">This is a request for planning advice in respect of an application for {!! $dot('application_type', '45mm') !!}
            for a {!! $dot('proposed_use_lc', '28mm') !!} use over a piece of land situated at {!! $dot('location', '35mm') !!}
            of {!! $dot('lga', '30mm') !!} Local Government Area.</span>
    </div>

    <div class="f">
        <span class="k">Dimension:</span>
        <span class="v">{!! $num('side_1', '12mm') !!}m x {!! $num('side_2', '12mm') !!}m x {!! $num('side_3', '12mm') !!}m x {!! $num('side_4', '12mm') !!}m = {!! $num('area', '16mm') !!}m²</span>
    </div>

    <div class="f"><span class="k">Layout if any:</span><span class="v"><x-tpl.dots :v="$d['layout'] ?? null" class="full" /></span></div>

    <div class="f">
        <span class="k">Existing road reservation:</span>
        <span class="v">{!! $dot('road_to_centre', '14mm') !!}m to the centre, {!! $dot('road_corridor', '14mm') !!}m total road corridor.</span>
    </div>

    <div class="f">
        <span class="k">Other remarks:</span>
        <span class="v">@if (! empty($d['other_remarks'])){{ $d['other_remarks'] }}@else<x-tpl.dots class="full" /><x-tpl.dots class="full" /><x-tpl.dots class="full" />@endif</span>
    </div>

    <div class="f">
        <span class="k">Recommendation:</span>
        <span class="v">@if (! empty($d['recommendation'])){{ $d['recommendation'] }}@else<x-tpl.dots class="full" /><x-tpl.dots class="full" />@endif</span>
    </div>

    <div class="closing">Above is for your consideration/approval OR further appropriate directives, please.</div>

    <div class="signed">
        <div class="line"></div>
        <div class="who">{{ $d['signatory'] ?? '' }}</div>
        <div class="what">{{ $from }}.</div>
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
