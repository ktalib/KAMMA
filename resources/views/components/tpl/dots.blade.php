{{-- A blank on a paper template: the dotted line, with the value written on it when there is one. --}}
@props(['v' => null])
<span {{ $attributes->merge(['class' => 'dots']) }}>{{ $v }}</span>
