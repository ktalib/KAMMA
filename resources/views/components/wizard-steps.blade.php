{{-- Step bar for a formWizard form: numbered steps with titles; done steps get a tick and can be revisited.
     On small screens it collapses to "Step n of N: title". --}}
@props(['steps'])

<nav aria-label="Form steps" {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    <p class="text-sm font-medium text-slate-700 md:hidden dark:text-slate-300">
        Step <span x-text="step"></span> of {{ count($steps) }}:
        @foreach (array_values($steps) as $i => $title)
            <span x-show="step === {{ $i + 1 }}">{{ $title }}</span>
        @endforeach
    </p>
    <ol class="hidden items-center gap-2 md:flex">
        @foreach (array_values($steps) as $i => $title)
            @php $n = $i + 1; @endphp
            <li class="flex min-w-0 flex-1 items-center gap-2">
                <button type="button" @click="go({{ $n }})" :aria-current="step === {{ $n }} ? 'step' : null"
                        :disabled="{{ $n }} > furthest + 1"
                        class="group flex min-w-0 items-center gap-2 rounded-lg px-1 py-1 text-left disabled:cursor-not-allowed">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold ring-1"
                          :class="step === {{ $n }}
                              ? 'bg-indigo-600 text-white ring-indigo-600'
                              : ({{ $n }} < step || {{ $n }} <= furthest
                                  ? 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30'
                                  : 'bg-white text-slate-400 ring-slate-200 dark:bg-slate-900 dark:text-slate-500 dark:ring-slate-700')">
                        <svg x-show="{{ $n }} < step" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        <span x-show="{{ $n }} >= step">{{ $n }}</span>
                    </span>
                    <span class="truncate text-xs font-medium"
                          :class="step === {{ $n }} ? 'text-slate-900 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'">{{ $title }}</span>
                </button>
                @if (! $loop->last)
                    <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
