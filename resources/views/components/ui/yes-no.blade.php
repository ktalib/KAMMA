{{-- A required Yes / No question (KLAES JSI radio pair). x-model optional through the "model" prop. --}}
@props(['name', 'label', 'model' => null, 'value' => null])

<fieldset {{ $attributes->merge(['class' => '']) }}>
    <legend class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $label }} <span class="text-rose-500">*</span></legend>
    <div class="flex gap-4">
        @foreach (['1' => 'Yes', '0' => 'No'] as $v => $text)
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="radio" name="{{ $name }}" value="{{ $v }}" required @checked((string) old($name, $value) === (string) $v)
                       @if ($model) x-model="{{ $model }}" @endif
                       class="border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800">
                {{ $text }}
            </label>
        @endforeach
    </div>
</fieldset>
