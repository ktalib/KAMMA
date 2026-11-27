{{-- Stage timeline for one application (App\Support\Portal\PortalTimeline), drawn like the KLAES LAAS portal stage tracker.
     $timeline - the built timeline; $compact - a single progress bar instead of the full list. --}}
@if (! empty($compact))
    <div>
        <div class="mb-1.5 flex items-center justify-between gap-2 text-xs">
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $timeline['complete'] ? 'All stages completed' : 'Now: '.$timeline['current'] }}</span>
            <span class="text-slate-500 dark:text-slate-400">{{ $timeline['percent'] }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-full rounded-full {{ $timeline['returned'] ? 'bg-amber-500' : 'bg-emerald-600 dark:bg-emerald-500' }}" style="width: {{ $timeline['percent'] }}%"></div>
        </div>
    </div>
@else
    <ol>
        @foreach ($timeline['steps'] as $i => $step)
            @php
                $isDone = $step['state'] === 'done';
                $isCurrent = $step['state'] === 'current';
                $isReturned = $isCurrent && $timeline['returned'];
                $isLast = $loop->last;
            @endphp
            <li class="flex gap-3">
                <div class="flex flex-col items-center">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2
                        @if ($isDone) border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500
                        @elseif ($isReturned) border-amber-500 bg-white text-amber-600 dark:bg-slate-900 dark:text-amber-400
                        @elseif ($isCurrent) border-emerald-600 bg-white text-emerald-600 dark:border-emerald-400 dark:bg-slate-900 dark:text-emerald-400
                        @else border-slate-300 bg-white text-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-600 @endif">
                        <i data-lucide="{{ $isDone ? 'check' : ($isReturned ? 'undo-2' : ($isCurrent ? 'loader' : 'circle')) }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    </div>
                    @unless ($isLast)
                        <div class="min-h-[1rem] w-0.5 flex-1 {{ $isDone ? 'bg-emerald-600 dark:bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800' }}"></div>
                    @endunless
                </div>
                <div class="{{ $isLast ? '' : 'pb-4' }} min-w-0 pt-0.5">
                    <p class="text-sm
                        @if ($isReturned) font-semibold text-amber-700 dark:text-amber-300
                        @elseif ($isCurrent) font-semibold text-emerald-700 dark:text-emerald-300
                        @elseif ($isDone) font-semibold text-slate-900 dark:text-slate-100
                        @else text-slate-400 dark:text-slate-500 @endif">
                        {{ $step['title'] }}
                        @if ($isCurrent)
                            <span class="ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $isReturned ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' }}">Current</span>
                        @endif
                    </p>
                    @if ($step['note'])
                        <p class="mt-0.5 break-words text-xs {{ $isReturned ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $step['note'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
