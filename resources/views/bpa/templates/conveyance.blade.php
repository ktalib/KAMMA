{{-- Paper pages 7-8: Conveyance of Building Plan Approval (letter, two sheets). Data: ref. no., date, the use banner,
     File No., R of O No., developer, plot no., address, location, the two bold fines in condition xix, and the
     signatory's name. The conditions are the paper's wording; the page break falls after xiii as on the paper. --}}
@extends('bpa.templates.layout')

@push('styles')
<style>
    .cv { font-size: 10.2pt; line-height: 1.32; }
    .lh { display: flex; align-items: center; gap: 4mm; }
    .lh img { width: 30mm; height: 30mm; object-fit: contain; flex: none; }
    .lh .t { flex: 1; text-align: center; }
    .lh .n { font-weight: 900; font-size: 16pt; line-height: 1.1; text-transform: uppercase; }
    .lh .a { font-size: 8.6pt; font-weight: 600; margin-top: 1mm; }
    .band { display: grid; grid-template-columns: 1fr 1fr 0.9fr; gap: 3mm; background: #e5e7eb; border-top: 2.5mm solid #9ca3af; padding: 2mm 3mm; margin: 3mm 0 4mm; font-size: 7.8pt; line-height: 1.25; text-transform: uppercase; }
    .band .dots { font-size: 9pt; min-width: 30mm; text-transform: none; }
    .use { text-align: center; margin: 0 0 4mm; }
    .use span { display: inline-block; background: #111; color: #fff; font-weight: 900; font-size: 16pt; letter-spacing: .3em; padding: 1mm 4mm 1mm 5mm; border: 1.5mm double #fff; outline: .5mm solid #111; }
    .facts { display: grid; grid-template-columns: 37mm 1fr 25mm 40mm; gap: 1mm 3mm; font-size: 9.6pt; margin: 0 2mm 4mm; }
    .facts b { font-weight: 800; text-transform: uppercase; }
    .facts .dots { min-width: 0; font-weight: 800; }
    .cv h1 { font-size: 11pt; margin: 3mm 0 3mm; }
    .cond { display: grid; grid-template-columns: 11mm 1fr; gap: .4mm 0; text-align: justify; }
    .sign { display: grid; grid-template-columns: 11mm 1fr; }
    .signs { display: grid; grid-template-columns: 11mm 8mm 1fr; }
    .foot { position: absolute; left: 17mm; right: 17mm; bottom: 9mm; height: 3mm; background: linear-gradient(90deg, #9ca3af, #d1d5db, #9ca3af); }
</style>
@endpush

@php
    $lh = config('kamma.bpa.letterhead');
    $fines = [$d['fine_materials'] ?? 'N50,000', $d['fine_waste'] ?? 'N100,000'];
    $conditions1 = [
        'That I am pleased to enclose here with sets of <b>Approved Drawings</b> in connection of the above mentioned application and further inform you that the construction of the building should be based on these approved drawing.',
        'No alteration of the approved drawings can be made without Authority\'s permission. Any unilateral alteration renders this approval null and void.',
        'That you establish any missing beacon before Permission/Approval would be given to you to commence with the development. One set of the approved drawings should be kept on site during and after construction for the purpose of inspection by Authority\'s official.',
        'The validity of this approval is subject to development within three (3) years from the above date.',
        'This approval letter is not valid without accompanied of Architectural, Structural, Mechanical and Electrical Engineering Drawings.',
        'You are to provide security lights, planting and drainage (where not provided) and also contact fire-service office for fire safety recommendations on the drawings and site before commencement of the construction works.',
        'That you should notify the Authority and obtain setting out approval before you commence construction on site.',
        'Fence is a temporary structure, its approval can be withdrawn by the authority and demolished within seven (7) days-notice without any compensation.',
        'That you are to maintain fencing height of not more than 3m in all sides of the elevation including the front facing the road. In case of front view, 0.5meter height grill or sand cement blocks wall for gardening/landscaping.',
        'That you are to maintain only one entrance to the premises.',
        'That the Authority shall not be liable for any fault arising from design, construction and usage failure or any deviation whatsoever from the conditions of this approval.',
        'That the building(s) if constructed shall only be used for the purpose for which the plan approval is granted and no change of usage is allowed without the formal consent of the Authority.',
        'That you should facilitate access to site by designated Authority staff at all times.',
    ];
    $conditions2 = [
        'That the Authority is not liable for any impact which your proposed building may suffer in the course of provision of infrastructure in the area.',
        'That during physical construction, concrete mixing, block molding and any such acts should be strictly contained within your plots. Public right of way, storm water drainages and similar public utilities should not be tampered with by the work on your site.',
        'That on completion you must make fire-fighting provision on property/building and solid waste deposit/collection within the premises.',
        'That you must report to the Authority on completion of the project/building so that an official will certify that the building(s) is/are suitable for human habitation together with your built drawings.',
        'The Authority reserve the right to withdraw this approval at any stage of implementation if deemed necessary.',
        'The verge between your building and the road, public right of way storm water drains as well as similar public utilities should not be obstructed or tampered with. Note that construction materials and waste found on any of the areas mentioned above will lead to closure of your site pending payments of approved fines of <b>'.e($fines[0]).'</b> and <b>'.e($fines[1]).'</b> respectively each time.',
        'That this approval is considered void if it is discovered that any information leading to its grant is false or forged.',
        'That the building is subject to required redecoration every four (4) years.',
        'That registered COREN Civil Engineer must supervise the building to DPC level which should be above required minimum of 75years of flood level.',
        'That you should liaise with Engineering Department of the Authority to obtain appropriate information on design road level and other infrastructure to determine the DPC and foundation level in the area.',
        'Failure to comply as stated above is contravention of the law, the Authority will take appropriate action against you.',
    ];
    $romans = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x', 'xi', 'xii', 'xiii', 'xiv', 'xv', 'xvi', 'xvii', 'xviii', 'xix', 'xx', 'xxi', 'xxii', 'xxiii', 'xxiv'];
@endphp

@section('pages')
{{-- Sheet 1 (paper page 7) --}}
<div class="page cv">
    <div class="lh">
        <img src="{{ asset($lh['seal']) }}" alt="{{ $lh['short'] }} seal">
        <div class="t">
            <div class="n">{{ $lh['name'] }} ({{ $lh['short'] }})</div>
            <div class="a">{{ $lh['address'] }}</div>
            <div class="a">Tel: {{ $lh['phones'] }}</div>
            @if ($lh['email'])<div class="a">E-mail: {{ $lh['email'] }}</div>@endif
        </div>
        {{-- QR and security code in place of the KLAES-METRO logo (user 2026-11-24, as on KLAES's Land RofO). --}}
        @include('bpa.templates.partials.qr-code', ['qr' => $d['file_no'] ?? null, 'code' => $d['security_code'] ?? null])
    </div>

    <div class="band">
        <div>All correspondence to be addressed to<br>the Managing Director</div>
        <div>In case of reply please quote<br>Ref. No.: <x-tpl.dots :v="$d['ref_no'] ?? null" /></div>
        <div>Date <x-tpl.dots :v="$d['date'] ?? null" /></div>
    </div>

    <div class="use"><span>{{ $d['use'] ?? 'RESIDENTIAL' }}</span></div>

    <div class="facts">
        <span>File No.:</span><x-tpl.dots :v="$d['file_no'] ?? null" />
        <span>R OF O NO:</span><x-tpl.dots :v="$d['rofo_no'] ?? null" />
        <span>Name of Developer:</span><x-tpl.dots :v="$d['developer'] ?? null" />
        <span>Plot No:</span><x-tpl.dots :v="$d['plot_no'] ?? null" />
        <span>Address:</span><x-tpl.dots :v="$d['address'] ?? null" />
        <span>Location:</span><x-tpl.dots :v="$d['location'] ?? null" />
    </div>

    <h1>Conveyance of Building Plan Approval</h1>
    <p style="margin:0 0 1.5mm;text-align:justify">With reference to your application for approval of your Proposed Building Plan, you are hereby granted subject to the following must abide conditions which you are expected to read and abide by:</p>
    <div class="cond">
        @foreach ($conditions1 as $i => $text)
            <span>{{ $romans[$i] }}.</span><span>{!! $text !!}</span>
        @endforeach
    </div>
    <div class="foot"></div>
</div>

{{-- Sheet 2 (paper page 8) --}}
<div class="page cv">
    <div style="text-align:right;font-weight:600;margin:4mm 0 3mm">{{ $d['file_no'] ?? '' }}@if (empty($d['file_no']))<x-tpl.dots style="min-width:40mm" />@endif</div>
    <div class="cond">
        @foreach ($conditions2 as $i => $text)
            <span>{{ $romans[$i + count($conditions1)] }}.</span><span>{!! $text !!}</span>
        @endforeach
    </div>
    <div class="sign" style="margin-top:2mm">
        <span>2.</span><span>There should be signboard contained details of the plots i.e. <b><i>(excluding organic settlements)</i></b></span>
    </div>
    @foreach (['Location', 'Plot Number', 'C of O No. SLTR (etc).', 'Date of Building Plan/Setting-out Approval', 'Project Title', 'Name of the Architect/Consultants'] as $i => $item)
        <div class="signs"><span></span><span>({{ chr(97 + $i) }})</span><span>{{ $item }}</span></div>
    @endforeach
    <div class="sign" style="margin-top:2mm"><span>3.</span><span>Thank you for active participation in the development of Kano.</span></div>

    <p style="margin:5mm 0 14mm">Yours Faithfully,</p>
    <div style="font-weight:800;font-style:italic;line-height:1.4">
        <div style="min-width:70mm;display:inline-block;border-bottom:{{ empty($d['signatory']) ? '1.3px dotted #000' : '0' }};min-height:5mm">{{ $d['signatory'] ?? '' }}</div>
        <div>DIRECTOR ARCHITECTURE</div>
        <div>For: Managing Director</div>
    </div>

    <div class="spacer"></div>
    <div style="text-align:right;font-weight:700;margin-bottom:0">2 | <span style="letter-spacing:.08em">Page</span></div>
    @include('bpa.templates.partials.footer')
</div>
@endsection
