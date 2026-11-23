@extends('portal.layouts.portal')

@section('title', 'Notifications')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Every update on your requests, newest first. The same messages are sent by SMS from KLAES-METRO to your phone.</p>
</div>

@if ($events->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
        <i data-lucide="bell-off" class="mx-auto mb-4 h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
        <p class="text-base font-semibold text-slate-900 dark:text-white">No notifications yet</p>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Updates appear here as soon as your request moves.</p>
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($events as $e)
                @php $fresh = $e['at']->gte(now()->subDays(14)); @endphp
                <li class="flex gap-4 p-4 sm:p-5">
                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ ($e['action'] ?? '') === 'not_recommended' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' }}">
                        <i data-lucide="{{ ($e['action'] ?? '') === 'not_recommended' ? 'undo-2' : 'bell' }}" class="h-4 w-4" aria-hidden="true"></i>
                        @if ($fresh)
                            <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-emerald-600 ring-2 ring-white dark:bg-emerald-400 dark:ring-slate-900" title="New"></span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $e['title'] }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ $e['at']->format('j M Y, g:i a') }}</p>
                        </div>
                        @if ($e['body'])
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $e['body'] }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                            <a href="{{ route('portal.applications.show', $e['ref']) }}" class="font-mono font-semibold text-emerald-600 hover:underline dark:text-emerald-400">{{ $e['ref'] }}</a>
                            @if ($e['type'])
                                <span class="text-slate-300 dark:text-slate-600">&middot;</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $e['type'] }}</span>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
