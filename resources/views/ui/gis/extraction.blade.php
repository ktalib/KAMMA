{{-- GIS > Survey Plan Extraction, copied in layout and wording from KLAES survey_plan_extraction/index: figure cards, the upload
     box (max 5 files), the AI analysis pipeline stages, the extracted-data editor (File No., Applicant Name, Approved Plan No.,
     Starting Beacon No., Beacon Coordinates, Courses) and the COGO data format. KAMMA has no OCR service yet: the extracted
     data is entered by hand and the save posts to the preview stub. --}}
<x-app-layout title="Survey Plan Extraction">
    @php
        $label = 'mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400';
        $field = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
        $stages = ['Init', 'OCR', 'Layout', 'Extract', 'Assemble', 'Done'];
    @endphp

    <div class="space-y-6" x-data="extractionPage()">
        <x-ui.page-head icon="sparkles" title="Survey Plan Extraction" subtitle="Read beacon coordinates and courses from scanned survey plans" tone="teal" section="GIS" />
        <x-ui.preview-note what="AI extraction">Enter or correct the plan data by hand; the save stores nothing yet.</x-ui.preview-note>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm dark:border-blue-500/20 dark:bg-slate-900">
                <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">Plans Selected</p>
                <p class="mt-1 text-2xl font-black tabular-nums text-slate-900 dark:text-white" x-text="files.length">0</p>
            </div>
            <x-ui.stat title="Processed / Extracted" value="0" icon="scan-text" tone="emerald" />
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">AI Analysis Status</p>
                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white" x-text="files.length ? 'Ready' : 'Idle'"></p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.panel title="Upload Survey Plans" subtitle="Upload scanned survey plan documents (PDF, JPG, PNG, TIFF, WebP). Max 5 files.">
                <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 px-6 py-10 text-center hover:border-teal-400 dark:border-slate-700 dark:hover:border-teal-500/60"
                       @dragover.prevent @drop.prevent="pick($event.dataTransfer.files)">
                    <i data-lucide="upload-cloud" class="h-8 w-8 text-slate-400"></i>
                    <span class="mt-2 font-semibold text-slate-700 dark:text-slate-200">Drag and drop survey plans here</span>
                    <span class="text-sm text-slate-500 dark:text-slate-400">or click to browse files</span>
                    <span class="mt-2 text-xs text-slate-400">Max 5 files. Supported: PDF, JPG, PNG, TIFF, WebP.</span>
                    <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.webp" class="sr-only" @change="pick($event.target.files)">
                </label>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300"><span x-text="files.length"></span> survey plan(s) selected</p>
                <ul class="mt-2 space-y-1 text-sm">
                    <template x-for="(f, i) in files" :key="i"><li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5 dark:bg-slate-800/60"><span class="truncate" x-text="f.name"></span><button type="button" @click="files.splice(i, 1)" class="text-xs text-rose-600 hover:underline dark:text-rose-400">Remove</button></li></template>
                </ul>
            </x-ui.panel>

            <x-ui.panel title="Survey Plan AI Analysis Pipeline">
                <ol class="grid grid-cols-6 gap-2 text-center text-xs">
                    @foreach ($stages as $s)
                        <li><span class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $loop->iteration }}</span><span class="mt-1 block text-slate-600 dark:text-slate-300">{{ $s }}</span></li>
                    @endforeach
                </ol>
                <div class="mt-4 h-2 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-2 w-0 rounded-full bg-teal-500"></div></div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">0% Complete · Current Stage: Initializing</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">The OCR service is not connected to KLAES-METRO yet; enter the plan data below.</p>
            </x-ui.panel>
        </div>

        <form method="POST" action="{{ route('ui.preview', 'survey-plan-extraction') }}">
            @csrf
            <x-ui.panel title="Extracted Survey Plan Data" subtitle="Review and edit the data extracted from the uploaded survey plans.">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2"><x-ui.file-picker name="file_indexing_id" label="File No." /></div>
                    <div><label for="ex-app" class="{{ $label }}">Applicant Name</label><input id="ex-app" name="applicant_name" class="{{ $field }}"></div>
                    <div><label for="ex-plan" class="{{ $label }}">Approved Plan No.</label><input id="ex-plan" name="approved_plan_no" class="{{ $field }}"></div>
                    <div><label for="ex-start" class="{{ $label }}">Starting Beacon No.</label><input id="ex-start" name="starting_beacon" x-model="start" class="{{ $field }}"></div>
                </div>

                <h3 class="mt-6 text-sm font-bold text-slate-800 dark:text-slate-100">Beacon Coordinates</h3>
                <table class="mt-2 w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th class="pb-2 pr-2">S/N</th><th class="pb-2 pr-2">Beacon No.</th><th class="pb-2 pr-2">X (Easting)</th><th class="pb-2 pr-2">Y (Northing)</th><th></th></tr></thead>
                    <tbody>
                        <template x-for="(b, i) in beacons" :key="b.key">
                            <tr>
                                <td class="py-1 pr-2 text-slate-500" x-text="i + 1"></td>
                                <td class="py-1 pr-2"><input :name="`beacons[${i}][no]`" x-model="b.no" class="{{ $field }}"></td>
                                <td class="py-1 pr-2"><input :name="`beacons[${i}][x]`" x-model="b.x" type="number" step="any" class="{{ $field }}"></td>
                                <td class="py-1 pr-2"><input :name="`beacons[${i}][y]`" x-model="b.y" type="number" step="any" class="{{ $field }}"></td>
                                <td class="py-1"><button type="button" @click="beacons.splice(i, 1)" class="rounded p-1 text-rose-500" aria-label="Remove beacon">&times;</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <button type="button" @click="beacons.push({ key: Math.random(), no: '', x: '', y: '' })" class="mt-2 text-sm font-semibold text-teal-700 hover:underline dark:text-teal-300">+ Add beacon</button>

                <h3 class="mt-6 text-sm font-bold text-slate-800 dark:text-slate-100">Courses</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Bearing and distance between consecutive beacons, worked out from the coordinates.</p>
                <table class="mt-2 w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th class="pb-2">S/N</th><th class="pb-2">From</th><th class="pb-2">To</th><th class="pb-2">Bearing</th><th class="pb-2">Distance (m)</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(c, i) in courses" :key="i"><tr><td class="py-1.5" x-text="i + 1"></td><td x-text="c.from"></td><td x-text="c.to"></td><td class="tabular-nums" x-text="c.bearing"></td><td class="tabular-nums" x-text="c.distance"></td></tr></template>
                    </tbody>
                </table>

                <h3 class="mt-6 text-sm font-bold text-slate-800 dark:text-slate-100">COGO Data Format</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Copy the formatted data below or download it as a .txt file.</p>
                <textarea readonly rows="6" class="{{ $field }} mt-2 font-mono text-xs" x-text="cogo"></textarea>
                <div class="mt-3 flex flex-wrap justify-end gap-2">
                    <button type="button" @click="navigator.clipboard && navigator.clipboard.writeText(cogo)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Copy</button>
                    <button type="button" @click="download()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Download .txt</button>
                    <button class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-500">Save extracted data</button>
                </div>
            </x-ui.panel>
        </form>
    </div>

    @push('scripts')
        <script>
            function extractionPage() {
                const row = () => ({ key: Math.random(), no: '', x: '', y: '' });
                const dms = (deg) => { const d = Math.floor(deg), mf = (deg - d) * 60, m = Math.floor(mf), s = Math.round((mf - m) * 60); return `${d}° ${String(m).padStart(2, '0')}' ${String(s).padStart(2, '0')}"`; };
                return {
                    files: [], start: '', beacons: [row(), row(), row(), row()],
                    pick(list) { this.files = [...this.files, ...list].slice(0, 5); },
                    get valid() { return this.beacons.filter((b) => b.x !== '' && b.y !== '' && ! isNaN(parseFloat(b.x)) && ! isNaN(parseFloat(b.y))); },
                    get courses() {
                        const v = this.valid;
                        if (v.length < 2) return [];
                        return v.map((b, i) => {
                            const n = v[(i + 1) % v.length];
                            const dx = parseFloat(n.x) - parseFloat(b.x), dy = parseFloat(n.y) - parseFloat(b.y);
                            let brg = Math.atan2(dx, dy) * 180 / Math.PI; if (brg < 0) brg += 360;
                            return { from: b.no || '?', to: n.no || '?', bearing: dms(brg), distance: Math.hypot(dx, dy).toFixed(2) };
                        });
                    },
                    get cogo() { return this.valid.map((b) => `${b.no || '?'},${b.x},${b.y}`).join('\n'); },
                    download() {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(new Blob([this.cogo], { type: 'text/plain' }));
                        a.download = 'cogo-' + (this.start || 'plan') + '.txt'; a.click();
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
