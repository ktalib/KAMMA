{{-- A tick box on a paper template: empty, or ticked when $on. --}}
@props(['on' => false])
<span {{ $attributes->merge(['class' => 'box']) }}>@if ($on)&#10003;@endif</span>
