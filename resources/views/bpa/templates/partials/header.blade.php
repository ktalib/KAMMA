{{-- Letterhead of the BPA paper forms, KNUPDA renamed KAMMA. $style: seal (page 1: large name), seal-caps (page 2),
     plain (page 6: centred text), title (page 4: the name line only), seal-only (page 3, which has no name line),
     memo (planning memo: seal-caps plus $department and "Memo on $memoOn").
     Logos side by side on every template (user 2026-11-24): the KAMMA seal on the left, the KLAES-METRO logo on the right. --}}
@php
    $lh = config('kamma.bpa.letterhead');
    $style = $style ?? 'seal';
    $left = '<img src="'.e(asset($lh['seal'])).'" alt="'.e($lh['short']).' seal">';
    // 'qr' => ['qr' => content, 'code' => security code] puts the QR and security code where the KLAES-METRO logo goes (the memo).
    $right = isset($qr) ? view('bpa.templates.partials.qr-code', $qr)->render() : '<img src="'.e(asset($lh['logo_right'])).'" alt="KLAES-METRO">';
@endphp
@if ($style === 'title')
    <div class="head-pair">
        {!! $left !!}
        <p style="font-weight:800;font-size:11.5pt;text-transform:uppercase;margin:0">{{ $lh['name'] }} ({{ $lh['short'] }})</p>
        {!! $right !!}
    </div>
@elseif ($style === 'seal-only')
    <div class="head-pair">{!! $left !!}<span></span>{!! $right !!}</div>
@elseif ($style === 'plain')
    <div class="head-plain">
        {!! $left !!}
        <div>
            <div class="name">{{ $lh['name'] }}</div>
            <div class="addr">{{ $lh['address'] }}</div>
            <div class="addr">Tel: {{ $lh['tel'] }}@if ($lh['email']) &nbsp; E-mail: {{ $lh['email'] }}@endif</div>
        </div>
        {!! $right !!}
    </div>
@else
    {{-- seal / seal-caps / memo. memo (the planning memo): caps name, then the department and "Memo on <file no.>" lines, no rule. --}}
    @php $caps = in_array($style, ['seal-caps', 'memo'], true); @endphp
    <div class="head {{ $style === 'memo' ? 'memo-head' : '' }}">
        {!! $left !!}
        <div class="txt">
            <div class="name {{ $caps ? 'caps' : '' }}">{{ $lh['name'] }}@if ($caps) ({{ $lh['short'] }})@endif</div>
            <div class="addr">{{ $lh['address'] }}</div>
            <div class="addr">Tel: {{ $lh['tel'] }}@if ($lh['email']) &nbsp; E-mail: {{ $lh['email'] }}@endif</div>
            @if ($style === 'memo')
                <div class="memo-ref">{{ $department }}</div>
                <div class="memo-ref">Memo on <x-tpl.dots :v="$memoOn" style="min-width:38mm" /></div>
            @endif
        </div>
        {!! $right !!}
    </div>
@endif
