{{-- QR code and security code on the right of the letterhead, as on KLAES's Land RofO print (user 2026-11-24): the
     security code above, letter over the first two digits, then the remaining digits; the QR below it. $qr is the QR
     content and $code the 9-character security code (8 digits + 1 letter). A blank template prints empty placeholders. --}}
@php $sc = \App\Support\Bpa\BpaTemplates::securityCode($code ?? null); @endphp
<div class="qr-block">
    @if ($sc)
        <div class="sec-code" title="Security code">
            <span class="frac"><span class="top">{{ $sc['alphabet'] }}</span><span>{{ $sc['digits_start'] }}</span></span>
            <span class="end">{{ $sc['digits_end'] }}</span>
        </div>
    @else
        <div class="sec-code sec-blank">Security code</div>
    @endif
    @if (filled($qr ?? null))
        <div class="qr">{!! \App\Support\Qr::svg($qr, 150) !!}</div>
    @else
        <div class="qr qr-blank">QR</div>
    @endif
</div>
