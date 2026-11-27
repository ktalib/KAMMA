{{-- Paper page 3: Fee Computation - Analysis Sheet (page 2 of the application form). Data: the ticked application
     variables, use categories and location variables (Zone with its name), and the Assessment Officer. --}}
@extends('bpa.templates.layout')
@php use App\Support\Bpa\BpaTemplates as T; @endphp

@push('styles')
<style>
    .fc { font-family: 'Arial Narrow', Arial, sans-serif; font-size: 12pt; }
    .fc .big { text-align: center; font-family: Georgia, 'Times New Roman', serif; font-weight: 900; font-size: 17pt; letter-spacing: .5em; margin: 1mm 0 3mm; }
    .fc .sub { text-align: center; font-weight: 800; text-decoration: underline; font-size: 13pt; margin-bottom: 8mm; }
    .fc .grp { display: grid; grid-template-columns: 12mm 1fr; margin-bottom: 6mm; }
    .fc .grp .k { font-weight: 800; }
    .fc .grp .h { font-weight: 800; text-decoration: underline; text-transform: uppercase; margin-bottom: 1.5mm; }
    .fc .item { display: grid; grid-template-columns: 12mm auto; align-items: center; margin: 1.7mm 0; }
    .fc .box { width: 7mm; height: 5.4mm; margin-left: 3mm; }
</style>
@endpush

@section('pages')
<div class="page fc">
    <span class="page-no" style="font-size:16pt">3</span>
    @include('bpa.templates.partials.header', ['style' => 'seal-only'])
    <div class="big">FEE &nbsp;COMPUTATION</div>
    <div class="sub">ANALYSIS SHEET</div>

    @php
        $romans = ['i.', 'ii.', 'iii.', 'iv.', 'v.', 'vi.', 'vii.', 'viii.', 'ix.'];
        $groups = [
            ['A)', 'Application Variables:', T::APPLICATION_VARIABLES, $d['application_variables'] ?? []],
            ['B)', 'Use Categories:', T::USE_CATEGORIES, $d['use_categories'] ?? []],
            ['C)', 'Location Variables:', T::LOCATION_VARIABLES, $d['location'] ?? []],
        ];
    @endphp
    @foreach ($groups as [$k, $h, $items, $ticked])
        <div class="grp">
            <span class="k">{{ $k }}</span>
            <div>
                <div class="h">{{ $h }}</div>
                @foreach ($items as $i => $label)
                    <div class="item"><span>{{ $romans[$i] }}</span><span>{{ $label }}<x-tpl.box :on="in_array($label, $ticked, true)" />@if ($label === 'Zone' && ! empty($d['zone_name']))<x-tpl.dots :v="$d['zone_name']" style="margin-left:3mm" />@endif</span></div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="spacer"></div>
    <div style="align-self:flex-end;width:62mm;text-align:center;margin-bottom:0">
        <div class="line" style="border-bottom:1.3px dotted #000;min-height:6mm;font-weight:700">{{ $d['assessment_officer'] ?? '' }}</div>
        <div style="font-family:Georgia,serif;font-style:italic;font-weight:700;margin-top:1mm">Assessment Officer</div>
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
