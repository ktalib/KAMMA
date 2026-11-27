{{-- "YES/NO" on a paper template: both words when blank; once answered, the answer bold and the other struck through. --}}
@props(['v' => null])
@php $v = strtoupper((string) $v); @endphp
<span {{ $attributes->merge(['class' => 'yesno']) }}>@if ($v === 'YES' || $v === 'NO')<b class="{{ $v === 'YES' ? 'pick' : 'off' }}">YES</b>/<b class="{{ $v === 'NO' ? 'pick' : 'off' }}">NO</b>@else YES/NO @endif</span>
