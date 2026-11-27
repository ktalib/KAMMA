{{-- Paper page 2: Building Plan Application Form. Data: the use in the title (Residential / Commercial / Industry), the
     application-type boxes, every dotted line, the sets per architectural sheet, the drawings and ownership documents
     submitted, and the official-use fees. --}}
@extends('bpa.templates.layout')
@php use App\Support\Bpa\BpaTemplates as T; @endphp

@push('styles')
<style>
    .page.af { padding: 9mm 15mm 8mm; font-size: 9.6pt; line-height: 1.28; }
    .af .head { padding-bottom: 1.5mm; margin-bottom: 2.5mm; gap: 4mm; }
    .af .head img { width: 21mm; height: 21mm; }
    .af .head .name.caps { font-size: 11.5pt; }
    .af .head .addr { font-size: 8.6pt; margin-top: .3mm; }
    .af .row { margin: .55mm 0; }
    .af .formtitle { text-align: center; font-weight: 800; font-size: 10.3pt; margin: 0 0 2mm; white-space: nowrap; }
    .af .formtitle .u { text-decoration: underline; margin-left: 4mm; }
    .af .formtitle .u.pick { background: #000; color: #fff; padding: 0 1.5mm; }
    .types { display: flex; align-items: center; gap: 4mm; margin-bottom: 1.5mm; }
    .types .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2mm 4mm; flex: 1; }
    .types .t { border: 1.3px solid #000; text-align: center; font-weight: 800; font-size: 7.6pt; padding: .5mm 1mm; box-shadow: 1px 1px 0 #000; white-space: nowrap; }
    .types .t.on { background: #000; color: #fff; }
    .sec { display: grid; grid-template-columns: 8mm 1fr; gap: 0 2mm; margin-top: 1.6mm; }
    .sec .k { font-weight: 800; }
    .sec .h { font-weight: 800; text-decoration: underline; text-transform: uppercase; }
    .draw { display: flex; gap: 2mm; margin-top: .5mm; }
    .draw .lead { display: grid; grid-template-columns: 8mm 1fr; gap: 0 2mm; width: 70mm; flex: none; align-self: flex-start; }
    .draw .sheets { flex: 1; display: grid; grid-template-columns: 8mm 1fr; gap: 0 2mm; }
    .draw .sheet { display: flex; align-items: baseline; gap: 1.5mm; }
    .draw .sheet .dots { flex: 1; min-width: 0; text-align: center; }
    .list { display: grid; grid-template-columns: 8mm 1fr; gap: 0 2mm; }
    .official { margin-top: 2mm; border-top: 2px solid #000; padding-top: 1.2mm; }
    .official .ttl { text-align: center; font-weight: 800; text-decoration: underline; margin-bottom: .5mm; }
    .official .row { margin: 1mm 0; }
    .official .cont { display: block; border-bottom: 1.3px dotted #000; height: 4.3mm; }
</style>
@endpush

@section('pages')
<div class="page af">
    <span class="page-no">2</span>
    @include('bpa.templates.partials.header', ['style' => 'seal-caps'])

    <p class="formtitle">BUILDING PLAN APPLICATION FORM FOR:
        @foreach (T::FORM_USES as $u)
            <span class="u {{ ($d['form_use'] ?? null) === $u ? 'pick' : '' }}">{{ $u }}@if (! $loop->last),@endif</span>
        @endforeach
    </p>

    <div class="types">
        <span style="flex:none">State Type of Application:</span>
        <div class="grid">
            @foreach (T::APPLICATION_TYPES as $t)
                <span class="t {{ in_array($t, $d['application_types'] ?? [], true) ? 'on' : '' }}">{{ $t }}</span>
            @endforeach
        </div>
    </div>

    <div class="row"><span class="lbl">State Building Plan No. (If Available):</span><x-tpl.dots :v="$d['bp_no'] ?? null" /></div>
    <div class="row"><span class="lbl">Name of Owner:</span><x-tpl.dots :v="$d['owner_name'] ?? null" /></div>
    <div class="row"><span class="lbl">Address of Owner/Authority:</span><x-tpl.dots :v="$d['owner_address'] ?? null" /><span class="lbl">Phone No.</span><x-tpl.dots :v="$d['phone'] ?? null" style="flex:0 0 45mm" /></div>
    <div class="row"><span class="lbl">Description Plot/Property:</span><x-tpl.dots :v="$d['plot_description'] ?? null" /></div>
    <div class="row"><span class="lbl">Intended Use of Proposal:</span><x-tpl.dots :v="$d['intended_use'] ?? null" /></div>
    <div class="row"><span class="lbl">Approved Use of C. of O.:</span><x-tpl.dots :v="$d['approved_use'] ?? null" /></div>

    <div class="sec"><span class="k">A)</span><span class="h">Drawing Submitted</span></div>
    @php $sets = $d['arch_sets'] ?? []; $romans = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii']; @endphp
    <div class="draw">
        <div class="lead"><span>(a)</span><span>Architectural Drawing of<br>existing, proposal or both</span></div>
        <div class="sheets">
            @foreach (T::ARCH_SHEETS as $key => $label)
                <span>({{ $romans[$loop->index] }})</span>
                <span class="sheet">{{ $label }} <x-tpl.dots :v="$sets[$key] ?? null" /> Sets</span>
            @endforeach
        </div>
    </div>
    <div class="list" style="margin-left:10mm">
        @foreach (T::OTHER_DRAWINGS as $i => $label)
            <span>({{ chr(98 + $i) }})</span><span>{{ $label }}@if (in_array($label, $d['other_drawings'] ?? [], true))<span class="tick">&#10003;</span>@endif</span>
        @endforeach
    </div>

    <div class="sec"><span class="k">B)</span><span class="h">Ownership Document Submitted</span></div>
    <div class="list" style="margin-left:10mm">
        @foreach (T::OWNERSHIP_DOCUMENTS as $i => $label)
            <span>{{ $i + 1 }}.</span><span>{{ $label }}@if (in_array($label, $d['ownership_documents'] ?? [], true))<span class="tick">&#10003;</span>@endif</span>
        @endforeach
    </div>

    <div class="sec"><span class="k">C)</span><span class="row" style="margin:0"><span class="h lbl">Any Other Documents.</span>@if (! empty($d['other_documents']))<x-tpl.dots :v="$d['other_documents']" />@endif</span></div>

    <div class="sec"><span class="k">D)</span><span class="h">Undertaking</span></div>
    <div class="sec"><span></span><span style="text-align:justify">I/We undertake to build in accordance with approved drawings in case of Contravention of any of the approval, I/We agree that the approval is withdrawn, thereby rendering the development/building as illegal and liable to be treated in accordance with the existing laws. Also, the processing fee lapses after three (3) years of payment and approval is not valid until the approved fees is paid.</span></div>

    <div class="row" style="margin:3mm 0 1mm 18mm">
        <b class="lbl" style="font-family:Georgia,serif;font-style:italic">Applicant:</b><x-tpl.dots :v="$d['applicant'] ?? null" style="flex:0 0 62mm" />
        <span class="spacer"></span>
        <b class="lbl" style="font-family:Georgia,serif;font-style:italic">Date:</b><x-tpl.dots :v="$d['applicant_date'] ?? null" style="flex:0 0 38mm" />
    </div>

    <div class="official">
        <div class="ttl">FOR OFFICIAL USE ONLY</div>
        <div class="row"><span class="lbl">Processing Fee (25%)</span><x-tpl.dots :v="$d['processing_fee'] ?? null" /></div>
        <span class="cont"></span>
        <div class="row"><span class="lbl">Approval Fee (50%)</span><x-tpl.dots :v="$d['approval_fee'] ?? null" /></div>
        <span class="cont"></span><span class="cont"></span>
        <div class="row"><span class="lbl">Consultancy Fee (25%)</span><x-tpl.dots :v="$d['consultancy_fee'] ?? null" /></div>
        <span class="cont"></span>
        <div class="row" style="margin-top:3mm"><span class="lbl">Total Fee: (100%)</span><x-tpl.dots :v="$d['total_fee'] ?? null" /></div>
        <div><i>(See Page 2, for fee computation and analysis).</i></div>
    </div>

    <div class="sig" style="margin-top:7mm;padding:0 12mm">
        <div><div class="line">{{ $d['director_name'] ?? '' }}</div><div class="cap" style="font-family:Georgia,serif;font-style:italic">Director Architecture</div></div>
        <div style="flex:0 0 55mm"><div class="line">{{ $d['director_date'] ?? '' }}</div><div class="cap" style="font-family:Georgia,serif;font-style:italic">Date</div></div>
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
