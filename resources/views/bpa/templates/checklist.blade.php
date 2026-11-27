{{-- Paper page 1: Check List of Application for Building Plan Approval. Fixed wording (a handout); no blanks on the paper. --}}
@extends('bpa.templates.layout')

@push('styles')
<style>
    .cl { font-size: 13.5pt; line-height: 1.75; }
    .cl ol { margin: 0; padding-left: 8mm; }
    .cl ol ol { padding-left: 8mm; }
    .cl > ol > li { margin-bottom: 2.5mm; text-align: justify; padding-left: 1mm; }
    .cl ol.alpha { list-style: lower-alpha; }
    .cl ol.roman { list-style: lower-roman; }
    .cl h1 { font-size: 15pt; line-height: 1.3; }
</style>
@endpush

@section('pages')
<div class="page">
    @include('bpa.templates.partials.header', ['style' => 'seal'])

    <div class="cl">
        <h1>Check List of Application for Building<br>Plan Approval</h1>
        <ol>
            <li>Tenable supporting documents i.e. (C of O / Letter of Grant) should be presented.
                <ol class="alpha">
                    <li>Customary title i.e.
                        <ol class="roman">
                            <li>Sales agreement</li>
                            <li>Writ of possession etc.</li>
                        </ol>
                    </li>
                    <li>Other allocation/permit by
                        <ol class="roman">
                            <li>Federal Government Allocation</li>
                            <li>Housing Corporation, Railway Property lease agreement etc.</li>
                        </ol>
                    </li>
                </ol>
            </li>
            <li>Complete Architectural working drawings of the proposal signed and sealed by a registered Architect or Architectural firm should be presented.</li>
            <li>All engineering drawings duly signed and sealed by a registered Engineer or Engineering Firm should be presented <i>(i.e. Structural drawings, Electrical and Mechanical drawings)</i>.</li>
            <li>Site Analysis Report (SAR) that has been conducted in the presence of Registered Town Planner. Signed and stamped by him.</li>
            <li>Confirmation of the presented document by applicants at Ministry of Land and Physical Planning.</li>
            <li>Landscape design of the proposal.</li>
            <li>Soil Test (<i>where applicable</i>).</li>
            <li>Environmental Impact Assessment (E.I.A). (<i>where applicable</i>).</li>
            <li>Indemnity</li>
        </ol>
    </div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
