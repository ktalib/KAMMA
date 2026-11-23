{{-- A dropdown with exactly one "Other" option that reveals a required "specify" field (KLAES rule 6). --}}
@props(['name', 'label', 'options' => [], 'required' => false, 'value' => null])

@php
    $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
    $options = collect($options)->reject(fn ($o) => strcasecmp(trim($o), 'Other') === 0)->unique(fn ($o) => mb_strtolower(trim($o)))->values();
    $id = 'so-'.str_replace(['[', ']', '.'], '-', $name);
@endphp

<div x-data="{ v: @js(old($name, $value)) }" {{ $attributes }}>
    <label for="{{ $id }}" class="{{ $lbl }}">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" x-model="v" class="{{ $field }}" @required($required)>
        <option value="">Select…</option>
        @foreach ($options as $o)
            <option value="{{ $o }}">{{ $o }}</option>
        @endforeach
        <option value="Other">Other</option>
    </select>
    <div x-show="v === 'Other'" x-cloak class="mt-2">
        <label for="{{ $id }}-other" class="{{ $lbl }}">Specify {{ mb_strtolower($label) }} <span class="text-rose-500">*</span></label>
        <input id="{{ $id }}-other" type="text" name="{{ $name }}_other" value="{{ old($name.'_other') }}" maxlength="200" class="{{ $field }}"
               :required="v === 'Other'" :disabled="v !== 'Other'">
    </div>
</div>
