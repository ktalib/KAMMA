{{-- Marks data read live (read-only) from the KLAES database, or shows why it could not be read. --}}
@props(['tables' => null, 'error' => null])

@if ($error)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300']) }}>
        <i data-lucide="database-zap" class="mt-0.5 h-4 w-4 shrink-0"></i>
        <p><span class="font-semibold">KLAES data unavailable.</span> {{ $error }}</p>
    </div>
@else
    <p {{ $attributes->merge(['class' => 'flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400']) }}>
        <i data-lucide="database" class="h-3.5 w-3.5"></i>
        <span>Live from KLAES, read-only{{ $tables ? ' ('.$tables.')' : '' }}. KAMMA never changes these records.</span>
    </p>
@endif
