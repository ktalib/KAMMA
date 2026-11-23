{{-- Log a File / Create File Tracker, copied from KLAES (resources/views/create_file_tracker_page/index.blade.php):
     Request Mode, Reset Form / Preview, the Create File Tracker tab (File Details, Office Details, File Log Session,
     Current Date & Time, Quick Actions) and the File Tracker Log tab. Data: KAMMA's files, units and KLAES users. --}}
<x-app-layout title="Create File Tracker" :bare="true">
    @php
        $card = 'bg-white rounded-xl border border-gray-200 shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $head = 'px-6 py-4 border-b border-gray-100 dark:border-slate-800';
        $h3 = 'text-lg font-semibold text-gray-900 flex items-center gap-2 dark:text-slate-100';
        $sub = 'text-sm text-gray-500 dark:text-slate-400';
        $label = 'block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300';
        $input = 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $ro = 'w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300';
        $help = 'mt-1 text-xs text-gray-500 dark:text-slate-400';
        $req = '<span class="text-red-500">*</span>';
    @endphp

    <div class="flex-1"
         x-data="logAFile(@js([
             'fileUrl' => route('tracking.log.file'),
             'officersUrl' => route('tracking.log.officers'),
             'listUrl' => route('tracking.log.list'),
             'purposes' => $purposes,
             'tab' => request('tab') === 'log' ? 'log' : 'create',
             'old' => [
                 'file_number' => old('file_number', request('file')),
                 'priority' => old('priority', 'High'),
                 'status_update' => old('status_update', 'log-out'),
                 'purpose' => old('purpose', ''),
                 'purpose_other' => old('purpose_other', ''),
                 'timeline_days' => old('timeline_days', ''),
                 'num_pages' => old('num_pages', ''),
                 'notes' => old('notes', ''),
                 'origin_unit_id' => old('origin_unit_id', ''),
                 'to_unit_id' => old('to_unit_id', ''),
                 'holder_user_id' => old('holder_user_id', ''),
                 'holder_name' => old('holder_name', ''),
                 'override' => (bool) old('override_in_transit'),
             ],
         ]))">
        <div class="px-6 py-3 flex items-center gap-3 shadow-sm bg-gradient-to-r from-red-900 via-red-800 to-red-950">
            <i data-lucide="file-text" class="h-5 w-5 text-white shrink-0"></i>
            <div class="flex items-center gap-2">
                <span class="text-white font-bold text-sm uppercase tracking-widest">KAMMA</span>
                <span class="text-red-100 text-sm">·</span>
                <span class="text-white text-sm font-semibold">File Tracking</span>
                <span class="text-red-100 text-sm">·</span>
                <span class="text-white text-sm">Log a File</span>
            </div>
        </div>

        <div class="px-6 py-6 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                    @foreach ($errors->all() as $m)<p>{{ $m }}</p>@endforeach
                </div>
            @endif

            <div class="{{ $card }} px-5 py-3 flex items-center gap-3">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Request Mode</span>
                <span class="h-4 w-px bg-gray-200 dark:bg-slate-700"></span>
                <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-slate-800">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 shadow-sm dark:bg-slate-700 dark:text-slate-100"><i data-lucide="file-text" class="h-3.5 w-3.5"></i> Manual Request</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-400" title="Digital requests come with the DMS"><i data-lucide="monitor" class="h-3.5 w-3.5"></i> Digital Request</span>
                </div>
            </div>

            <div class="{{ $card }} px-6 py-4 flex gap-2">
                <button type="button" @click="resetForm()" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"><i data-lucide="rotate-cw" class="h-4 w-4"></i> Reset Form</button>
                <button type="button" @click="preview = true" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"><i data-lucide="eye" class="h-4 w-4"></i> Preview</button>
            </div>

            <div class="{{ $card }}">
                <div class="flex gap-6 border-b border-gray-200 px-6 dark:border-slate-800">
                    <button type="button" @click="tab = 'create'" :class="tab === 'create' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-slate-400'" class="-mb-px inline-flex items-center gap-2 border-b-2 py-4 text-sm font-medium"><i data-lucide="file-plus" class="h-4 w-4"></i> Create File Tracker</button>
                    <button type="button" @click="tab = 'log'; loadList()" :class="tab === 'log' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-slate-400'" class="-mb-px inline-flex items-center gap-2 border-b-2 py-4 text-sm font-medium"><i data-lucide="database" class="h-4 w-4"></i> File Tracker Log <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700" x-text="rows.length"></span></button>
                </div>

                {{-- Create File Tracker --}}
                <form x-show="tab === 'create'" method="POST" action="{{ route('tracking.store') }}" class="p-6" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="log_id" :value="logId">
                    <input type="hidden" name="holder_name" :value="form.holder_name">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <div class="space-y-6 xl:col-span-2">
                            <section class="{{ $card }}">
                                <div class="{{ $head }}"><h3 class="{{ $h3 }}"><i data-lucide="file-text" class="h-5 w-5"></i> File Details</h3><p class="{{ $sub }}">Enter the basic file information</p></div>
                                <div class="p-6 space-y-5">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <label class="{{ $label }}">File No {!! $req !!}</label>
                                            <div class="flex">
                                                <input name="file_number" x-model="form.file_number" readonly placeholder="Loaded from Quick Search" class="{{ $ro }} rounded-r-none">
                                                <a href="{{ route('tracking.search') }}" class="inline-flex items-center rounded-r-md border border-l-0 border-gray-200 bg-gray-50 px-3 text-gray-400 hover:text-gray-700 dark:border-slate-700 dark:bg-slate-800" title="Quick Search"><i data-lucide="search" class="h-4 w-4"></i></a>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">File Tracking ID</label>
                                            <input :value="file.tracking_id || ''" readonly class="{{ $ro }}">
                                            <p class="{{ $help }}">Auto-fetched after selecting a file number</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Current Unit</label>
                                            <input :value="file.current_location || (file.found ? 'Not tracked yet' : '–')" readonly class="{{ $ro }}">
                                            <p class="{{ $help }}">Where the file is now (Origin)</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                                            <i data-lucide="info" class="inline h-3.5 w-3.5"></i> Files are logged through <a href="{{ route('tracking.search') }}" class="font-semibold underline">Quick Search</a> — the smart selector is disabled here. Search a file in Quick Search and click <b>Log File</b> to load it.
                                        </div>
                                        <label class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                            <input type="checkbox" name="override_in_transit" value="1" x-model="form.override" class="mt-0.5 h-3.5 w-3.5 rounded border-amber-300">
                                            <span>Override for in-transit file — log it even though it is still on its way.</span>
                                        </label>
                                        <div x-show="file.status === 'IN_TRANSIT'" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                                            This file is in transit to <b x-text="file.receiving_department"></b>.
                                        </div>
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">File Title {!! $req !!}</label>
                                        <input :value="file.file_title || ''" readonly placeholder="e.g. Alhaji Ibrahim Dantata" class="{{ $ro }}">
                                        <p class="{{ $help }}">Auto-filled from the file; it cannot be typed here.</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="{{ $label }}">File Priority</label>
                                            <select name="priority" x-model="form.priority" class="{{ $input }}">
                                                @foreach ($priorities as $p)<option value="{{ $p }}">{{ $p }} Priority</option>@endforeach
                                            </select>
                                            <p class="{{ $help }}">Set the priority level for this file</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Status Update</label>
                                            <select name="status_update" x-model="form.status_update" class="{{ $input }}">
                                                <option value="log-out">Log-out</option>
                                                <option value="log-in">Log-in</option>
                                            </select>
                                            <p class="{{ $help }}">Update the file tracker status</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Request Purpose {!! $req !!}</label>
                                            <select name="purpose" x-model="form.purpose" @change="applyPurpose()" class="{{ $input }}">
                                                <option value="">Select the reason this file is being requested</option>
                                                @foreach ($purposes as $name => $days)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                                                <option value="OTHER">OTHER (SPECIFY)</option>
                                            </select>
                                            <input x-show="form.purpose === 'OTHER'" name="purpose_other" x-model="form.purpose_other" :disabled="form.purpose !== 'OTHER'" placeholder="Specify the purpose *" class="mt-2 {{ $input }} uppercase">
                                            <p class="{{ $help }}">Drives the file's expected turnaround (timeline) and its Green/Amber/Red status.</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Timeline (Days)</label>
                                            <input name="timeline_days" type="number" min="1" max="365" x-model="form.timeline_days" placeholder="e.g. 5" class="{{ $input }}">
                                            <p class="{{ $help }}">How many days the file should be with the requesting office before it's due back. Sets the Expected Return Date below.</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Expected Return Date {!! $req !!}</label>
                                            <input name="expected_return_on" :value="expectedReturn" readonly class="{{ $ro }}">
                                            <p class="{{ $help }}">Auto-calculated from the Request Purpose or Timeline (Days) above — not manually editable.</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Number of Pages {!! $req !!}</label>
                                            <input name="num_pages" type="number" min="1" x-model="form.num_pages" placeholder="Enter total pages…" class="{{ $input }}">
                                            <p class="{{ $help }}">Count the total pages in the physical file before logging out.</p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">Notes/Remarks</label>
                                        <textarea name="notes" rows="3" x-model="form.notes" placeholder="Reason why the file is in this office…" class="{{ $input }}"></textarea>
                                        <p class="{{ $help }}">Enter the reason for the file being in this office</p>
                                    </div>
                                </div>
                            </section>

                            <section class="{{ $card }}">
                                <div class="{{ $head }}"><h3 class="{{ $h3 }}"><i data-lucide="building" class="h-5 w-5"></i> Office Details</h3><p class="{{ $sub }}">Select the current office location</p></div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="{{ $label }}">Unit (Origin)</label>
                                            <select name="origin_unit_id" x-model="form.origin_unit_id" :disabled="!! file.tracking_id" class="{{ $input }}">
                                                <option value="">Select unit (origin)</option>
                                                @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                            <p class="{{ $help }}" x-text="file.tracking_id ? 'Taken from where the file is now' : 'Where the file starts its tracking'"></p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Requester Unit {!! $req !!}</label>
                                            <select name="to_unit_id" x-model="form.to_unit_id" class="{{ $input }}">
                                                <option value="">Select destination</option>
                                                @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                            </select>
                                            <p class="{{ $help }}">Select the destination unit/office</p>
                                        </div>
                                        <div class="relative">
                                            <label class="{{ $label }}">File Requester {!! $req !!}</label>
                                            <input type="hidden" name="holder_user_id" :value="form.holder_user_id">
                                            <input x-model="officerQ" @focus="searchOfficers()" @input.debounce.300ms="searchOfficers()" @keydown.escape="officers = []" placeholder="Select receiving officer…" autocomplete="off" class="{{ $input }}">
                                            <ul x-show="officers.length" x-cloak @click.outside="officers = []" class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                                                <template x-for="o in officers" :key="o.id">
                                                    <li><button type="button" @click="pickOfficer(o)" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50 dark:hover:bg-slate-800"><span class="font-medium text-gray-900 dark:text-slate-100" x-text="o.name"></span> <span class="text-xs text-gray-500" x-text="o.rank || ''"></span></button></li>
                                                </template>
                                            </ul>
                                            <p class="{{ $help }}">Select the colleague receiving this file.</p>
                                        </div>
                                        <div>
                                            <label class="{{ $label }}">Sending Officer</label>
                                            <input value="{{ Auth::user()->name }}" readonly class="{{ $ro }}">
                                            <p class="{{ $help }}">The officer sending this file (Current User)</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="{{ $card }}">
                                <div class="{{ $head }}"><h3 class="{{ $h3 }}"><i data-lucide="hash" class="h-5 w-5"></i> File Log Session</h3><p class="{{ $sub }}">Initial log entry details (auto-populated)</p></div>
                                <div class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div><label class="{{ $label }}">Log ID</label><input :value="logId" readonly class="{{ $ro }} font-mono"><p class="{{ $help }}">Auto-generated log identifier</p></div>
                                    <div><label class="{{ $label }}">Log In Time</label><input :value="form.status_update === 'log-in' ? clock.time : ''" readonly class="{{ $ro }}"><p class="{{ $help }}">Current time (auto-updated)</p></div>
                                    <div><label class="{{ $label }}">Log In Date</label><input :value="form.status_update === 'log-in' ? clock.date : ''" readonly class="{{ $ro }}"><p class="{{ $help }}">Current date (auto-updated)</p></div>
                                    <div><label class="{{ $label }}">Log Out Time</label><input :value="form.status_update === 'log-out' ? clock.time : ''" readonly class="{{ $ro }}"><p class="{{ $help }}">Will be set when file is logged out</p></div>
                                    <div class="md:col-span-2"><label class="{{ $label }}">Log Out Date</label><input :value="form.status_update === 'log-out' ? clock.date : ''" readonly class="{{ $ro }}"><p class="{{ $help }}">Will be set when file is logged out</p></div>
                                </div>
                            </section>

                            <div class="flex justify-end gap-2">
                                <button type="button" @click="resetForm()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">Reset</button>
                                <button type="submit" :disabled="submitting || ! file.found" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"><i data-lucide="save" class="h-4 w-4"></i> Save File Log</button>
                            </div>
                        </div>

                        <aside class="space-y-6">
                            <section class="{{ $card }}">
                                <div class="{{ $head }}"><h3 class="{{ $h3 }}"><i data-lucide="clock" class="h-5 w-5"></i> Current Date &amp; Time</h3><p class="{{ $sub }}">Live timestamp for log entries</p></div>
                                <div class="p-6 text-center">
                                    <div class="font-mono text-2xl font-bold text-gray-900 dark:text-slate-100" x-text="clock.time"></div>
                                    <div class="mt-1 text-gray-600 dark:text-slate-400" x-text="clock.date"></div>
                                    <div class="mt-4 border-t border-gray-100 pt-4 text-left text-sm dark:border-slate-800">
                                        <div class="text-gray-500 dark:text-slate-400">File No:</div>
                                        <span class="mt-1 inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700" x-text="form.file_number || '-'"></span>
                                        <div class="mt-3 text-gray-500 dark:text-slate-400">Log ID:</div>
                                        <span class="mt-1 inline-flex rounded-full bg-purple-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-purple-700" x-text="logId"></span>
                                    </div>
                                </div>
                            </section>
                            <section class="{{ $card }}">
                                <div class="{{ $head }}"><h3 class="{{ $h3 }}"><i data-lucide="file-search" class="h-5 w-5"></i> Quick Actions</h3><p class="{{ $sub }}">Perform common file tracking operations</p></div>
                                <div class="grid grid-cols-3 gap-4 p-6 text-center">
                                    @foreach ([['Search Files', 'Find trackers', 'file-search', 'bg-blue-100 text-blue-600', route('tracking.search')], ['Office List', 'View offices', 'building-2', 'bg-green-100 text-green-600', route('tracking.units')], ['Track Status', 'Check status', 'activity', 'bg-orange-100 text-orange-600', route('tracking.find')], ['Statistics', 'View stats', 'bar-chart-3', 'bg-purple-100 text-purple-600', route('tracking.dashboard')], ['Assignment Center', 'Assign & review', 'user-plus', 'bg-blue-100 text-blue-600', route('tracking.movement')]] as [$t, $s, $i, $c, $u])
                                        <a href="{{ $u }}" class="group flex flex-col items-center">
                                            <span class="flex h-12 w-12 items-center justify-center rounded-lg {{ $c }} transition group-hover:scale-105"><i data-lucide="{{ $i }}" class="h-6 w-6"></i></span>
                                            <span class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">{{ $t }}</span>
                                            <span class="text-xs text-gray-500 dark:text-slate-400">{{ $s }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        </aside>
                    </div>
                </form>

                @include('tracking.partials.log-tab')
            </div>
        </div>

        {{-- Preview --}}
        <div x-show="preview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" @keydown.escape.window="preview = false">
            <div @click.outside="preview = false" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h3 class="{{ $h3 }} mb-4"><i data-lucide="eye" class="h-5 w-5"></i> File Log Preview</h3>
                <dl class="space-y-2 text-sm">
                    <template x-for="[k, v] in previewRows()" :key="k">
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-1.5 dark:border-slate-800"><dt class="text-gray-500 dark:text-slate-400" x-text="k"></dt><dd class="text-right font-medium text-gray-900 dark:text-slate-100" x-text="v || '—'"></dd></div>
                    </template>
                </dl>
                <div class="mt-5 text-right"><button type="button" @click="preview = false" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Close</button></div>
            </div>
        </div>
    </div>

    @include('tracking.partials.log-a-file-js')
</x-app-layout>
