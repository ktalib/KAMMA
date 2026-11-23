{{-- DMS > PT Quality Control: typed pages checked against the page image; Pass or Fail (with a reason) per page. --}}
<x-app-layout title="PT Quality Control">
    @php
        $n = fn ($v) => number_format((int) $v);
        $tab = fn ($on) => $on ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200';
    @endphp

    <div class="space-y-6" x-data="{ fail: null }">
        <x-ui.page-head icon="shield-check" title="PT Quality Control" subtitle="Check typed pages against the scanned image" tone="emerald" section="DMS › Document Page Types" />
        @if ($error)<x-ui.klaes-note :error="$error" />@endif
        <x-ui.preview-note what="Recording a QC decision" />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat title="Awaiting QC" :value="$n($counts['pending'] ?? 0)" hint="Typed pages not yet checked" icon="hourglass" tone="amber" :href="route('dms.qc', ['status' => 'pending'])" />
            <x-ui.stat title="Passed" :value="$n($counts['passed'] ?? 0)" hint="Page type confirmed" icon="check-circle-2" tone="emerald" :href="route('dms.qc', ['status' => 'passed'])" />
            <x-ui.stat title="Failed" :value="$n($counts['failed'] ?? 0)" hint="Sent back for re-typing" icon="x-circle" tone="rose" :href="route('dms.qc', ['status' => 'failed'])" />
        </div>

        <x-ui.panel :padded="false">
            <nav class="flex gap-6 border-b border-slate-100 px-6 dark:border-slate-800">
                @foreach (['pending' => 'Awaiting QC', 'passed' => 'Passed', 'failed' => 'Failed'] as $k => $l)
                    <a href="{{ route('dms.qc', ['status' => $k]) }}" class="-mb-px border-b-2 py-3 text-sm font-semibold {{ $tab($status === $k) }}">{{ $l }}</a>
                @endforeach
            </nav>
            @if (! $pages || $pages->isEmpty())
                <x-ui.empty icon="shield-check" title="No pages here">Pages typed in PageTyping arrive here for checking.</x-ui.empty>
            @else
                <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($pages as $p)
                        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                            <div class="aspect-[3/4] bg-slate-100 dark:bg-slate-800">
                                @if ($p->scanning)
                                    <img src="{{ route('dms.scans.image', $p->scanning) }}" alt="Page {{ $p->page_number }} of {{ $p->indexing?->file_number }}" loading="lazy" class="h-full w-full object-contain">
                                @endif
                            </div>
                            <div class="space-y-1 p-3 text-sm">
                                <p class="font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">{{ $p->indexing?->file_number }} · p.{{ $p->page_number }}</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->typeLabel() }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $p->subtypeLabel() ?: '—' }} · {{ $p->page_code ?: 'no code' }} · {{ $p->typed_by_name }}</p>
                                @if ($status === 'pending')
                                    <div class="flex gap-2 pt-2">
                                        <form method="POST" action="{{ route('ui.preview', 'pt-qc-pass') }}" class="flex-1">@csrf<input type="hidden" name="page_typing_id" value="{{ $p->id }}"><button class="w-full rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">Pass</button></form>
                                        <button type="button" @click="fail = {{ $p->id }}" class="flex-1 rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10">Fail</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">{{ $pages->links() }}</div>
            @endif
        </x-ui.panel>

        <template x-teleport="body">
            <div x-show="fail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="fail = null">
                <form method="POST" action="{{ route('ui.preview', 'pt-qc-fail') }}" @click.outside="fail = null" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                    @csrf
                    <input type="hidden" name="page_typing_id" :value="fail">
                    <h3 class="font-bold text-slate-900 dark:text-white">Fail this page</h3>
                    <x-ui.select-other name="reason" label="Reason" :options="['Wrong page type', 'Wrong sub-type', 'Page code wrong', 'Image unreadable', 'Page out of order']" required />
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="fail = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">Cancel</button>
                        <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">Fail page</button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</x-app-layout>
