{{-- Paper page 6: Recommendation for Building Plan Approval/Registration, Zonal Officer to the Director Architecture,
     then the Director Architecture to the Managing Director. Data: every dotted line on items 1-8, both signatures and dates,
     and the closing line under the MD block (md_remark). --}}
@extends('bpa.templates.layout')
@php use App\Support\Bpa\BpaTemplates as T; @endphp

@push('styles')
<style>
    .page.rc { padding-top: 11mm; padding-bottom: 10mm; }
    .rc { font-size: 9.5pt; line-height: 1.42; }
    .rc .ttl { text-align: center; font-family: Georgia, 'Times New Roman', serif; font-weight: 900; text-decoration: underline; font-size: 11pt; margin: 0 0 4mm; }
    .rc .to { font-weight: 700; font-size: 10.5pt; margin-bottom: 3mm; }
    .rc .it { display: grid; grid-template-columns: 8mm 9mm 1fr; align-items: baseline; }
    .rc .it .row { margin: .3mm 0; }
    .rc .dr { display: grid; grid-template-columns: 17mm 8mm 1fr; align-items: baseline; }
    .rc .dr .row { margin: .2mm 0; max-width: 115mm; }
    .rc .gap { height: 2mm; }
</style>
@endpush

@section('pages')
<div class="page rc">
    <span class="page-no" style="font-size:18pt;font-family:Georgia,serif">6</span>
    @include('bpa.templates.partials.header', ['style' => 'plain'])
    <div class="ttl">RECOMMENDATION FOR BUILDING PLAN APPROVAL/REGISTRATION</div>
    <div class="to">Director Architecture</div>

    <div class="row"><span class="lbl">1. BP Number</span><x-tpl.dots :v="$d['bp_number'] ?? null" /></div>
    <div class="row"><span class="lbl">2. Ownership document are in order</span><x-tpl.dots :v="$d['ownership_in_order'] ?? null" /></div>
    <div class="it"><span></span><span>a)</span><span class="row"><span class="lbl">Change of Ownership (if any) Document are in order</span><x-tpl.dots :v="$d['change_of_ownership'] ?? null" /></span></div>
    <div class="it"><span></span><span>b)</span><span class="row"><span class="lbl">Purpose change or proposal in same with the Certificate of Occupancy</span><x-tpl.dots :v="$d['purpose_conforms'] ?? null" /></span></div>
    <div class="gap"></div>

    <div class="it"><span>3.</span><span>(a)</span><span class="row"><span class="lbl">Appropriate application fees has been paid</span><x-tpl.dots :v="$d['fees_paid'] ?? null" /></span></div>
    <div class="it"><span></span><span>(b)</span><span class="row"><span class="lbl">Levy has been paid</span><x-tpl.dots :v="$d['levy_paid'] ?? null" /></span></div>
    <div class="gap"></div>

    <div class="it"><span>4.</span><span>(a)</span><span class="row"><span class="lbl">The site has been charted</span><x-tpl.dots :v="$d['site_charted'] ?? null" /></span></div>
    <div class="it"><span></span><span>(b)</span><span class="row"><span class="lbl">The site has been visited and found to be</span><x-tpl.dots :v="$d['site_found_to_be'] ?? null" /></span></div>
    <div class="it"><span></span><span>(c)</span><span class="row"><span class="lbl">Any previous approval</span><x-tpl.dots :v="$d['previous_approval'] ?? null" /></span></div>
    <div class="gap"></div>

    <div class="it"><span>5.</span><span style="grid-column:2/4">The drawings submitted consist of the following:-</span></div>
    <div class="gap"></div>
    <div class="it"><span></span><span>(a)</span><span class="row"><span class="lbl">Title type</span><x-tpl.dots :v="$d['title_type'] ?? null" /></span></div>
    <div class="it"><span></span><span>(b)</span><span class="row"><span class="lbl">Certificate of Occupancy No</span><x-tpl.dots :v="$d['cofo_no'] ?? null" /></span></div>
    @php $romans = ['i.', 'ii.', 'iii.', 'iv.', 'v.', 'vi.', 'vii.']; $dw = $d['drawings'] ?? []; @endphp
    @foreach (T::RECOMMENDATION_DRAWINGS as $key => $label)
        <div class="dr"><span></span><span>{{ $romans[$loop->index] }}</span><span class="row"><span class="lbl" style="width:30mm">{{ $label }}</span><x-tpl.dots :v="$dw[$key] ?? null" /></span></div>
    @endforeach
    <div class="gap"></div>
    <div class="it"><span></span><span>(a)</span><span class="row"><span class="lbl">No. of blocks</span><x-tpl.dots :v="$d['no_of_blocks'] ?? null" /></span></div>
    <div class="it"><span></span><span>(b)</span><span class="row"><span class="lbl">Nature of each Building</span><x-tpl.dots :v="$d['nature_of_building'] ?? null" /></span></div>
    <div class="it"><span></span><span>(c)</span><span class="row"><span class="lbl">No. of floors</span><x-tpl.dots :v="$d['no_of_floors'] ?? null" /></span></div>
    <div class="it"><span></span><span>(d)</span><span class="row"><span class="lbl">Others e.g. Structure/Sanitary are all in order</span><x-tpl.dots :v="$d['others_in_order'] ?? null" /></span></div>
    <div class="gap"></div>

    <div class="it"><span>6.</span><span class="row" style="grid-column:2/4"><span class="lbl">Vetting of structural drawings is in order</span><x-tpl.dots :v="$d['structural_vetting'] ?? null" /></span></div>
    {{-- The comment is written on the continuation line, where there is room for it. --}}
    <div class="it"><span>7.</span><span class="row" style="grid-column:2/4"><span class="lbl">Comments from Ministry of Environment impact analysis (EIA) report (for Industrial) approved/not approved</span><x-tpl.dots style="min-width:6mm" /></span></div>
    <div class="it"><span></span><span class="row" style="grid-column:2/4"><x-tpl.dots :v="$d['eia_comment'] ?? null" /></span></div>
    <div class="it"><span>8.</span><span class="row" style="grid-column:2/4"><span class="lbl">In the light of the above, I therefore recommend drawings to be approved</span><x-tpl.dots :v="$d['recommend'] ?? null" /></span></div>

    <div class="sig" style="margin-top:7mm">
        <div style="flex:0 0 62mm"><div class="line">{{ $d['zonal_officer'] ?? '' }}</div><div class="cap">ZONAL OFFICER</div></div>
        <div style="flex:0 0 45mm"><div class="line">{{ $d['zonal_date'] ?? '' }}</div><div class="cap">DATE</div></div>
    </div>

    <div style="margin-top:6mm">
        <div style="font-weight:800">MANAGING DIRECTOR</div>
        <div style="font-weight:700;text-align:justify">I have cross checked the submission above and recommend the drawings for approval/registration for your kind consideration, please.</div>
    </div>
    <div class="sig" style="margin-top:7mm">
        <div style="flex:0 0 70mm;text-align:left"><div class="line">{{ $d['director'] ?? '' }}</div><div class="cap">DIRECTOR ARCHITECTURE</div></div>
        <div style="flex:0 0 45mm"><div class="line">{{ $d['director_date'] ?? '' }}</div><div class="cap">DATE</div></div>
    </div>
    <div class="row" style="margin-top:4mm"><x-tpl.dots :v="$d['md_remark'] ?? null" /></div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
