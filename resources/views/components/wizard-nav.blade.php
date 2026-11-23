{{-- Back / Next / final submit for a formWizard form. The submit label is the slot. --}}
@props(['cancel' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
    <div class="flex items-center gap-3">
        <button type="button" x-show="step > 1" @click="back()"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            Back
        </button>
        @if ($cancel)
            <a href="{{ $cancel }}" class="px-2 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">Cancel</a>
        @endif
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs text-slate-500 dark:text-slate-400">Step <span x-text="step"></span> of <span x-text="steps"></span></span>
        <button type="button" x-show="step < steps" @click="next()"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            Next
        </button>
        <button type="submit" x-show="step === steps" x-cloak :disabled="saving"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">
            <span x-show="! saving">{{ $slot }}</span>
            <span x-show="saving" x-cloak>Saving…</span>
        </button>
    </div>
</div>
