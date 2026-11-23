{{-- File Movement (Department), copied from KLAES (secretariat-file-log): the purple band, My Office with Change, the
     five counters, Incoming — scan to receive (Receive / Send mode, Batch mode, Log manually) and the Files / Mails
     tables (Incoming / Outgoing, Yet to Receive / Received). Data: KAMMA's units, files and mails. --}}
<x-app-layout title="File Movement (Department)" :bare="true">
    @php $card = 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900'; @endphp

    <div x-data="deptMovement(@js([
            'office' => $office?->id, 'officeName' => $office?->name,
            'units' => $units, 'purposes' => array_keys(config('kamma.request_purposes')),
            'dataUrl' => route('tracking.movement.data'), 'scanUrl' => route('tracking.movement.scan'),
            'logUrl' => route('tracking.movement.log'), 'filesUrl' => route('tracking.lookup'), 'unitUrl' => route('tracking.movement'),
         ]))">
        <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">File Movement (Department)</h1>
            <p class="text-gray-600 dark:text-slate-400">Receive and send files between offices — scan any KAMMA QR or log manually.</p>
        </div>
        <div class="flex items-center gap-3 bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-700 px-6 py-3 shadow-sm">
            <i data-lucide="book-open" class="h-5 w-5 text-white"></i>
            <span class="text-sm font-bold uppercase tracking-widest text-white">File Tracking</span><span class="text-indigo-100">·</span>
            <span class="text-sm font-semibold text-white">File Movement (Department)</span><span class="text-indigo-100">·</span>
            <span class="text-sm text-white" x-text="officeName"></span>
        </div>

        <div class="space-y-6 p-6">
            <div class="flex justify-end">
                <div class="{{ $card }} relative flex items-center gap-3 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600"><i data-lucide="building-2" class="h-5 w-5"></i></span>
                    <div><div class="text-xs uppercase tracking-wide text-gray-400">My Office</div><div class="font-semibold text-gray-900 dark:text-slate-100" x-text="officeName || 'No unit'"></div></div>
                    <button type="button" @click.stop="changing = ! changing" class="ml-3 text-sm font-semibold text-indigo-600 hover:text-indigo-800">Change</button>
                    <div x-show="changing" x-cloak @click.outside="changing = false" class="absolute right-0 top-full z-20 mt-1 w-64 rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                        <template x-for="u in units" :key="u.id"><a :href="unitUrl + '?unit=' + u.id" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 dark:text-slate-300 dark:hover:bg-slate-800" x-text="u.name"></a></template>
                        <a href="{{ route('tracking.units') }}" class="block border-t border-gray-100 px-4 py-2 text-xs font-semibold text-indigo-600 dark:border-slate-800">Manage units</a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([['pending', 'Pending Receipt', 'Sent to this office, not yet received', 'inbox', 'bg-yellow-100 text-yellow-600', 'text-yellow-600'], ['held', 'Held Here', 'Received and not yet sent on', 'folder-open', 'bg-green-100 text-green-600', 'text-green-600'], ['today', 'Received Today', 'Still held, received today', 'calendar-check', 'bg-blue-100 text-blue-600', 'text-blue-600'], ['transit', 'In Transit', 'Sent out, awaiting receipt', 'truck', 'bg-orange-100 text-orange-600', 'text-orange-600'], ['outgoing', 'Outgoing', 'Sent out in the last 7 days', 'send', 'bg-indigo-100 text-indigo-600', 'text-indigo-600']] as [$k, $l, $s, $i, $chip, $tone])
                    <div class="{{ $card }} p-5 {{ $k === 'pending' ? 'ring-2 ring-blue-200' : '' }}">
                        <div class="flex items-start justify-between"><div><div class="text-sm text-gray-600 dark:text-slate-400">{{ $l }}</div><div class="mt-1 text-2xl font-bold {{ $tone }}" x-text="counts.{{ $k }}.total">0</div></div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $chip }}"><i data-lucide="{{ $i }}" class="h-6 w-6"></i></span></div>
                        <div class="mt-3 text-xs text-gray-500 dark:text-slate-400">{{ $s }}</div>
                        <div class="text-xs text-gray-600 dark:text-slate-300">Files <span x-text="counts.{{ $k }}.files"></span> · Mails <span x-text="counts.{{ $k }}.mails"></span></div>
                    </div>
                @endforeach
            </div>

            <div class="{{ $card }} grid grid-cols-1 overflow-hidden border-t-4 border-t-green-500 lg:grid-cols-3">
                <div class="p-6 lg:col-span-2">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600"><i data-lucide="scan-line" class="h-6 w-6"></i></span>
                        <div><div class="font-bold text-gray-900 dark:text-slate-100" x-text="mode === 'receive' ? 'Incoming — scan to receive' : 'Outgoing — scan to send'"></div>
                            <div class="text-sm text-gray-500 dark:text-slate-400">Works with any KAMMA QR: the file label printed from File Tracking.</div></div>
                    </div>
                    <div class="mt-4 flex gap-2 rounded-lg border-2 border-indigo-200 p-1 dark:border-indigo-500/40">
                        <i data-lucide="qr-code" class="ml-2 mt-2.5 h-5 w-5 text-gray-400"></i>
                        <input x-ref="scan" x-model="scanQ" @keydown.enter.prevent="find()" placeholder="Scan the QR code, or type a file number" class="flex-1 border-0 bg-transparent text-sm focus:ring-0 dark:text-slate-100">
                        <button type="button" @click="find()" class="rounded-md bg-indigo-400 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Find</button>
                    </div>
                    <p class="mt-2 flex items-center gap-1.5 text-xs text-green-600"><span class="h-2 w-2 rounded-full bg-green-500"></span> Scanner ready — scans load automatically</p>
                    <p x-show="scanMsg" x-text="scanMsg" class="mt-3 text-sm" :class="scanOk ? 'text-green-700' : 'text-red-600'"></p>

                    <template x-if="found && mode === 'send'">
                        <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-500/30 dark:bg-orange-500/10">
                            <div class="text-sm font-semibold text-gray-900 dark:text-slate-100"><span x-text="found.file_number"></span> · <span class="font-mono text-xs" x-text="found.tracking_id"></span></div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <select x-model="sendTo" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Send to…</option><template x-for="u in units.filter(u => u.id !== office)" :key="u.id"><option :value="u.id" x-text="u.name"></option></template></select>
                                <select x-model="sendPurpose" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Purpose…</option><template x-for="p in purposes" :key="p"><option x-text="p"></option></template></select>
                            </div>
                            <button type="button" @click="send()" class="mt-3 inline-flex items-center gap-2 rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600"><i data-lucide="send" class="h-4 w-4"></i> Send</button>
                        </div>
                    </template>

                    <template x-if="batch.length">
                        <div class="mt-4 rounded-lg border border-violet-200 bg-violet-50 p-4 dark:border-violet-500/30 dark:bg-violet-500/10">
                            <div class="text-sm font-semibold text-violet-800 dark:text-violet-300">Batch: <span x-text="batch.length"></span> file(s) to receive</div>
                            <ul class="mt-2 space-y-1 text-sm"><template x-for="b in batch" :key="b.tracking_id"><li class="text-gray-700 dark:text-slate-300"><span x-text="b.file_number"></span> · <span class="font-mono text-xs" x-text="b.tracking_id"></span></li></template></ul>
                            <button type="button" @click="receiveBatch()" class="mt-3 rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Receive all</button>
                        </div>
                    </template>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 p-6 dark:border-slate-800 dark:bg-slate-800/40 lg:border-l lg:border-t-0">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Mode</div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="setMode('receive')" :class="mode === 'receive' ? 'bg-green-600 text-white' : 'bg-white text-green-700 border border-green-200 dark:bg-slate-900'" class="inline-flex items-center justify-center gap-2 rounded-md py-2 text-sm font-semibold"><i data-lucide="log-in" class="h-4 w-4"></i> Receive</button>
                        <button type="button" @click="setMode('send')" :class="mode === 'send' ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-700 border border-orange-200 dark:bg-slate-900'" class="inline-flex items-center justify-center gap-2 rounded-md py-2 text-sm font-semibold"><i data-lucide="log-out" class="h-4 w-4"></i> Send</button>
                    </div>
                    <button type="button" @click="batchMode = ! batchMode" :class="batchMode ? 'ring-2 ring-violet-400' : ''" class="mt-3 flex w-full items-center justify-center gap-2 rounded-md bg-violet-100 py-2.5 text-sm font-semibold text-violet-800 dark:bg-violet-500/20 dark:text-violet-200"><i data-lucide="layers" class="h-4 w-4"></i> Batch mode <span x-show="batchMode">(on)</span></button>
                    <p class="mt-1 text-center text-xs text-gray-500">Scan several files, then receive them together.</p>
                    <button type="button" @click.stop="manual = true" class="mt-3 flex w-full items-center justify-center gap-2 rounded-md bg-blue-100 py-2.5 text-sm font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-200"><i data-lucide="pencil" class="h-4 w-4"></i> Log manually</button>
                    <p class="mt-1 text-center text-xs text-gray-500">No tracking sheet or QR code? Log it by hand.</p>
                </div>
            </div>

            <div class="{{ $card }}">
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex gap-5">
                        <button type="button" @click="kind = 'files'; load()" :class="kind === 'files' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'" class="border-b-2 pb-1 text-sm font-medium">Files <span class="ml-1 rounded-full bg-green-100 px-1.5 text-xs text-green-700" x-text="kind === 'files' ? rows.length : '·'"></span></button>
                        <button type="button" @click="kind = 'mails'; load()" :class="kind === 'mails' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'" class="border-b-2 pb-1 text-sm font-medium">Mails <span class="ml-1 rounded-full bg-indigo-100 px-1.5 text-xs text-indigo-700" x-text="kind === 'mails' ? rows.length : '·'"></span></button>
                    </div>
                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <input x-model="search" @input.debounce.400ms="load()" placeholder="Filter…" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <input type="date" x-model="from" @change="load()" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <span class="text-xs text-gray-400">to</span>
                        <input type="date" x-model="to" @change="load()" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <button type="button" @click="exportCsv()" class="inline-flex items-center gap-1 rounded-md border border-green-200 bg-green-50 px-3 py-1.5 text-sm font-semibold text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300"><i data-lucide="download" class="h-4 w-4"></i> Export</button>
                        <button type="button" @click="load()" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800" aria-label="Refresh"><i data-lucide="refresh-cw" class="h-4 w-4"></i></button>
                        <button type="button" @click="toggleAuto()" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 dark:border-slate-700 dark:text-slate-300"><span class="h-2 w-2 rounded-full" :class="auto ? 'bg-green-500' : 'bg-gray-400'"></span> Auto: <span x-text="auto ? 'On' : 'Off'"></span></button>
                    </div>
                </div>
                <div class="flex gap-2 px-6 pt-3">
                    <button type="button" @click="dir = 'incoming'; load()" :class="dir === 'incoming' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 dark:text-slate-300'" class="rounded-full px-3 py-1 text-xs font-semibold">Incoming</button>
                    <button type="button" @click="dir = 'outgoing'; load()" :class="dir === 'outgoing' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 dark:text-slate-300'" class="rounded-full px-3 py-1 text-xs font-semibold">Outgoing</button>
                </div>
                <div class="flex gap-6 border-b border-gray-100 px-6 pt-3 dark:border-slate-800">
                    <button type="button" @click="state = 'pending'; load()" :class="state === 'pending' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'" class="border-b-2 pb-2 text-sm font-medium" x-text="dir === 'incoming' ? 'Yet to Receive' : 'Awaiting Receipt'"></button>
                    <button type="button" @click="state = 'done'; load()" :class="state === 'done' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'" class="border-b-2 pb-2 text-sm font-medium">Received</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-6 py-3">S/N</th><th class="px-6 py-3">File Number</th><th class="px-6 py-3">File Title</th><th class="px-6 py-3">From</th><th class="px-6 py-3">To</th><th class="px-6 py-3">Date</th><th class="px-6 py-3">Purpose / Notes</th><th class="px-6 py-3">Timeline</th><th class="px-6 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <template x-for="(r, i) in rows" :key="r.tracking_id + r.date">
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40">
                                    <td class="px-6 py-3 text-gray-500" x-text="i + 1"></td>
                                    <td class="px-6 py-3"><div class="font-semibold text-gray-900 dark:text-slate-100" x-text="r.file_number"></div><div class="font-mono text-xs text-indigo-700 dark:text-indigo-300" x-text="r.tracking_id"></div></td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.file_title"></td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.from"></td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-slate-300" x-text="r.to"></td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300" x-text="r.date"></td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-slate-300"><span x-text="r.purpose || '—'"></span><div class="text-xs text-gray-500" x-text="r.notes || ''"></div></td>
                                    <td class="px-6 py-3 whitespace-nowrap"><span x-show="r.timeline" class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" :class="{ 'bg-green-500': r.timeline === 'green', 'bg-amber-500': r.timeline === 'amber', 'bg-red-500': r.timeline === 'red' }"></span><span x-text="r.due"></span></span><span x-show="! r.timeline" class="text-gray-400">—</span></td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <button type="button" x-show="r.can_receive" @click="receive(r.receive_url)" class="rounded-md bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">Receive</button>
                                        <a :href="r.url" class="ml-1 rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200">View</a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="rows.length === 0" class="py-14 text-center text-gray-400"><i data-lucide="inbox" class="mx-auto h-8 w-8"></i><div class="mt-2 text-sm">Nothing here.</div></div>
                </div>
            </div>
        </div>

        {{-- Log manually --}}
        <div x-show="manual" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" @keydown.escape.window="manual = false">
            <div @click.outside="manual = false" class="w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-slate-100"><i data-lucide="pencil" class="h-5 w-5"></i> Log manually into <span x-text="officeName"></span></h3>
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2"><input type="radio" value="mail" x-model="m.kind"> Mail (letter or document)</label>
                    <label class="flex items-center gap-2"><input type="radio" value="file" x-model="m.kind"> KAMMA file</label>
                </div>
                <template x-if="m.kind === 'mail'">
                    <div class="space-y-3">
                        <input x-model="m.reference" placeholder="Mail reference *" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <input x-model="m.title" placeholder="Subject *" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <input x-model="m.mail_from" placeholder="From" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                </template>
                <template x-if="m.kind === 'file'">
                    <div>
                        <input x-model="m.q" @input.debounce.300ms="findFiles()" placeholder="Find the KAMMA file (land file number or file title)" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <div class="mt-1 max-h-48 overflow-y-auto">
                            <template x-for="f in m.files" :key="f.id">
                                <button type="button" @click="m.file_indexing_id = f.id; m.q = f.label; m.files = []" :disabled="!! f.tracked" class="block w-full px-2 py-1.5 text-left text-sm hover:bg-blue-50 disabled:opacity-50 dark:hover:bg-slate-800"><span class="font-semibold" x-text="f.label"></span> <span class="text-xs text-gray-500" x-text="f.tracked ? 'already tracked ' + f.tracked : f.sub"></span></button>
                            </template>
                        </div>
                    </div>
                </template>
                <p x-show="m.msg" x-text="m.msg" class="text-sm text-red-600"></p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="manual = false" class="rounded-md px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button type="button" @click="logManual()" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Log it</button>
                </div>
            </div>
        </div>
    </div>

    @include('tracking.partials.movement-js')
</x-app-layout>
