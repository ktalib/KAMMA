{{-- KAMMA's recommendation letter to the Honourable Commissioner on an application for a Statutory Right of Occupancy
     (photo sent 2026-11-24). The paper is typed on letterhead; here the letterhead is the KAMMA seal with the QR and security
     code on the right, as on the memo. "Ag. Director Urban Planning" prints as Director Physical Planning, as on the memo.
     Data: file no. and date, the addressee, the application type, applicant, use, reasons, site-plan page, the site's
     sides, and both signatories with their titles. Blank prints dotted lines.
     $plain (the -letterhead version, user 2026-11-24): no header at all, the top left empty for KAMMA's printed letterhead paper. --}}
@extends('bpa.templates.layout')

@push('styles')
<style>
    .page.kr { font-size: 11.5pt; line-height: 1.5; }
    .kr .head { border-bottom: 0; margin-bottom: 2mm; }
    .kr .head img { width: 24mm; height: 24mm; }
    .kr .head .name.caps { font-size: 11.5pt; }
    .kr .head .addr { font-size: 8.6pt; margin-top: .3mm; }
    .kr .letterhead-space { height: 42mm; flex: none; }
    .kr .refline { display: flex; justify-content: flex-end; gap: 14mm; margin: 8mm 0 9mm; }
    .kr .refline .dots { min-width: 40mm; }
    .kr .to { margin-bottom: 7mm; }
    .kr .to .hon { font-family: 'Old English Text MT', 'Lucida Blackletter', 'Monotype Corsiva', Georgia, serif; font-weight: 700; font-style: italic; font-size: 13pt; }
    .kr .subj { font-weight: 700; text-transform: uppercase; text-decoration: underline; margin-bottom: 6mm; }
    .kr .subj .dots { text-decoration: none; }
    .kr p { margin: 0 0 5mm; text-align: justify; }
    .kr .num { font-weight: 700; }
    .kr .full { display: block; width: 100%; min-height: 1.4em; margin-top: 1mm; }
    .kr .sigs { display: flex; justify-content: space-between; gap: 18mm; margin-top: 7mm; }
    .kr .sigs > div { flex: 0 0 72mm; }
    .kr .sigs .by { font-family: Georgia, 'Times New Roman', serif; font-weight: 700; font-size: 12pt; letter-spacing: .02em; }
    .kr .sigs .line { border-bottom: 1.3px dashed #000; height: 20mm; }
    .kr .sigs .who { font-weight: 700; text-transform: uppercase; margin-top: 1mm; min-height: 6mm; }
</style>
@endpush

@php
    $fileNo = $d['file_no'] ?? null;
    $dot = fn ($key, $min = null) => '<span class="dots"'.($min ? ' style="min-width:'.$min.'"' : '').'>'.e($d[$key] ?? '').'</span>';
    // The site's sides, as typed on the letter ("20.00m x 35.00m x ..."): plain when filled (as on the memo), dots when blank.
    $sides = array_values(array_filter((array) ($d['sides'] ?? []), 'filled'));
@endphp

@section('pages')
<div class="page kr">
    @if ($plain ?? false)
        <div class="letterhead-space"></div>
    @else
        @include('bpa.templates.partials.header', ['style' => 'seal-caps', 'qr' => ['qr' => $fileNo, 'code' => $d['security_code'] ?? null]])
    @endif

    <div class="refline">
        <x-tpl.dots :v="$fileNo" />
        <x-tpl.dots :v="$d['date'] ?? null" />
    </div>

    <div class="to">
        <div class="hon">{{ $d['addressee'] ?? 'The Honourable Commissioner' }},</div>
        @foreach ($d['addressee_lines'] ?? ['Ministry of Land and Physical Planning,', 'No. 2 Dr. Bala Muhammad Road,', 'Kano.'] as $line)
            <div>{{ $line }}</div>
        @endforeach
    </div>

    <div class="subj">RE: Application for {!! $dot('application_type', '62mm') !!}: <x-tpl.dots :v="$fileNo" style="min-width:38mm" /> by {!! $dot('applicant', '55mm') !!}</div>

    <p>Above subject matter refers, please.</p>

    <p>This is to convey the Authority's recommendation to this application for {!! $dot('use', '30mm') !!} title, in view of the fact that,
        @if (filled($d['reasons'] ?? null)){{ $d['reasons'] }}@else<x-tpl.dots class="full" /><x-tpl.dots class="full" />@endif</p>

    <p>The recommended site plan at page @if (filled($d['site_plan_page'] ?? null))<span class="num">{{ $d['site_plan_page'] }}</span>@else{!! $dot('site_plan_page', '12mm') !!}@endif
        and back cover showing the dimension of the site as;
        @if ($sides){{ implode(' x ', array_map(fn ($s) => $s.'m', $sides)) }}@else<x-tpl.dots class="full" />@endif
        is forwarded for guidance.</p>

    <p>Above is for your information and further necessary action, please.</p>

    <div class="sigs">
        <div>
            <div class="by">RECOMMENDED BY:</div>
            <div class="line"></div>
            <div class="who">{{ $d['recommended_by'] ?? '' }}</div>
            <div>{{ $d['recommended_title'] ?? 'Director Physical Planning' }}.</div>
        </div>
        <div>
            <div class="by">ENDORSED BY:</div>
            <div class="line"></div>
            <div class="who">{{ $d['endorsed_by'] ?? '' }}</div>
            <div>{{ $d['endorsed_title'] ?? 'Managing Director' }}.</div>
        </div>
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
