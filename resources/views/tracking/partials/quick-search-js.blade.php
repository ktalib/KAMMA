<script>
(() => {
    // Copied from KLAES quick_search.blade.php: same status codes, badges, result card, Next Action, request
    // form, File Search History and feedback table, over KAMMA's own files and file requests.
    const RESOLVE_URL  = @js(route('tracking.quick-search.resolve'));
    const FR_URL       = @js(route('tracking.quick-search.request'));
    const LOG_URL      = @js(route('tracking.quick-search.log'));
    const FEEDBACK_URL = @js(route('tracking.quick-search.feedback'));
    const REPORT_URL   = @js(route('tracking.quick-search.report'));
    const CSV_URL      = @js(route('tracking.quick-search.csv'));
    const LOOKUP_URL   = @js(route('tracking.lookup'));
    const LOG_FILE_URL = @js(route('tracking.create'));
    const UNITS        = @js($units);
    const PURPOSES     = @js($purposes);
    const CSRF         = document.querySelector('meta[name="csrf-token"]').content;

    const STATUS_META = {
        IN_TRANSIT:           { label: 'In Transit',              cls: 'bg-amber-100 text-amber-800 border-amber-300',   icon: 'truck' },
        IN_ARCHIVE:           { label: 'In Archive',              cls: 'bg-green-100 text-green-800 border-green-300',   icon: 'archive' },
        IN_ARCHIVE_FOUND:     { label: 'In Archive — Found',      cls: 'bg-green-100 text-green-800 border-green-300',   icon: 'check-circle' },
        IN_ARCHIVE_NOT_FOUND: { label: 'Not Found In Archive',    cls: 'bg-red-100 text-red-800 border-red-300',         icon: 'alert-triangle' },
        IN_UNIT:              { label: 'In Unit',                 cls: 'bg-blue-100 text-blue-800 border-blue-300',      icon: 'folder-search' },
        IN_UNIT_FOUND:        { label: 'In Unit — Found',         cls: 'bg-green-100 text-green-800 border-green-300',   icon: 'check-circle' },
        IN_UNIT_NOT_FOUND:    { label: 'In Unit — Not Found',     cls: 'bg-red-100 text-red-800 border-red-300',         icon: 'alert-triangle' },
        PENDING_FILE:         { label: 'Pending (Not Tracked)',   cls: 'bg-gray-100 text-gray-800 border-gray-300',      icon: 'help-circle' },
        FILE_NOT_FOUND:       { label: 'File Not Found',          cls: 'bg-red-100 text-red-800 border-red-300',         icon: 'alert-triangle' },
        MISSING_FILE:         { label: 'Not With KAMMA (NWK)',    cls: 'bg-red-100 text-red-800 border-red-300',         icon: 'alert-triangle' },
    };

    const input   = document.getElementById('qs-input');
    const btn     = document.getElementById('qs-btn');
    const pickBtn = document.getElementById('qs-pick');
    const result  = document.getElementById('qs-result');
    const icons   = () => window.lucide && window.lucide.createIcons();

    const esc = v => (v == null ? '' : String(v)).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const row = (l, v) => v ? `<div class="flex justify-between gap-4 py-2 border-b border-gray-100 last:border-0 dark:border-slate-800"><span class="text-sm text-gray-500 dark:text-slate-400">${esc(l)}</span><span class="text-sm font-medium text-gray-900 text-right dark:text-slate-100">${esc(v)}</span></div>` : '';
    const getJson = async (url) => { const r = await fetch(url, { headers: { Accept: 'application/json' } }); if (!r.ok) throw new Error(); return r.json(); };
    const field = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';

    // ── File number selector ───────────────────────────────────────────────
    const picker = document.getElementById('qs-picker');
    const pickQ = document.getElementById('qs-picker-q');
    const pickList = document.getElementById('qs-picker-list');
    const openPicker = () => { picker.classList.remove('hidden'); picker.classList.add('flex'); pickQ.value = ''; pickList.innerHTML = hint('Type at least 2 characters.'); setTimeout(() => pickQ.focus(), 30); };
    const closePicker = () => { picker.classList.add('hidden'); picker.classList.remove('flex'); };
    const hint = t => `<div class="py-6 text-center text-sm text-gray-500 dark:text-slate-400">${esc(t)}</div>`;
    let pickTimer;
    pickQ.addEventListener('input', () => {
        clearTimeout(pickTimer);
        pickTimer = setTimeout(async () => {
            const q = pickQ.value.trim();
            if (q.length < 2) { pickList.innerHTML = hint('Type at least 2 characters.'); return; }
            try {
                const { files } = await getJson(`${LOOKUP_URL}?q=${encodeURIComponent(q)}`);
                pickList.innerHTML = files.length ? files.map(f => `
                    <button type="button" data-pick="${esc(f.label)}" class="block w-full px-3 py-2.5 text-left hover:bg-indigo-50 dark:hover:bg-slate-800">
                        <span class="block text-sm font-bold text-gray-900 dark:text-slate-100">${esc(f.label)}</span>
                        <span class="block text-xs text-gray-500 dark:text-slate-400">${esc(f.sub)}${f.tracked ? ' · ' + esc(f.tracked) : ''}</span>
                    </button>`).join('') : hint('No KAMMA file matches that.');
            } catch (e) { pickList.innerHTML = hint('Could not load the files.'); }
        }, 300);
    });
    pickList.addEventListener('click', e => { const b = e.target.closest('[data-pick]'); if (!b) return; input.value = b.dataset.pick; closePicker(); search(); });
    pickBtn.addEventListener('click', openPicker);
    input.addEventListener('click', openPicker);
    document.getElementById('qs-picker-close').addEventListener('click', closePicker);
    picker.addEventListener('click', e => { if (e.target === picker) closePicker(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePicker(); });

    // A tracking ID scanned from a QR label lands here too (scanners type and press Enter).
    let scanned = '';
    document.addEventListener('keydown', e => {
        if (picker.classList.contains('flex') || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
        if (e.key === 'Enter' && /^KMA-TRK-/i.test(scanned)) { input.value = scanned.toUpperCase(); scanned = ''; search(); return; }
        if (e.key.length === 1) scanned = (scanned + e.key).slice(-20);
    });

    // ── Result card ────────────────────────────────────────────────────────
    function render(d) {
        const meta = STATUS_META[d.status] || { label: d.status, cls: 'bg-gray-100 text-gray-800 border-gray-300', icon: 'file' };
        const label = (d.status === 'IN_UNIT' && d.unit_name) ? `In ${d.unit_name}` : meta.label;
        const isFound = /_FOUND$/.test(d.status || '') && !/NOT_FOUND/.test(d.status || '');
        const isNotFound = /NOT_FOUND|MISSING_FILE/.test(d.status || '');

        const location = (() => {
            if (!d.found) return '';
            if (d.status === 'IN_TRANSIT') return row('Department', d.receiving_department) + row('Logged Out', d.logged_out_at);
            return row('Current Location (Expected)', d.current_location || 'Not tracked yet') + row('Shelf / Box', d.shelf);
        })();

        const answerBox = (f, c, title, icon) => f ? `
            <div class="mt-3 rounded-lg ${c.bg} border ${c.bd} px-4 py-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="${icon}" class="h-4 w-4 ${c.ic} shrink-0"></i>
                    <span class="text-sm font-bold ${c.tx}">${title}</span>
                    ${f.request_no ? `<span class="ml-auto text-[11px] font-semibold ${c.lbl}">${esc(f.request_no)}</span>` : ''}
                </div>
                <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-xs ${c.body}">
                    ${f.responded_at ? `<div><span class="font-semibold">Responded:</span> ${esc(f.responded_at)}</div>` : ''}
                    ${f.responder ? `<div><span class="font-semibold">Responded by (Registry Monitor):</span> ${esc(f.responder)}</div>` : ''}
                    ${f.requested_at ? `<div><span class="font-semibold">Request sent:</span> ${esc(f.requested_at)}</div>` : ''}
                    ${f.receiving_officer ? `<div><span class="font-semibold">Requester:</span> ${esc(f.receiving_officer)}</div>` : ''}
                    ${f.requester_office ? `<div><span class="font-semibold">Office:</span> ${esc(f.requester_office)}</div>` : ''}
                    ${f.note ? `<div class="sm:col-span-2"><span class="font-semibold">Note:</span> ${esc(f.note)}</div>` : ''}
                </dl>
            </div>` : '';
        const green = { bg: 'bg-green-50 dark:bg-green-500/10', bd: 'border-green-200 dark:border-green-500/30', ic: 'text-green-600', tx: 'text-green-800 dark:text-green-300', lbl: 'text-green-700', body: 'text-green-900 dark:text-green-200' };
        const nwk = d.fr_not_found && d.fr_not_found.not_found_type === 'MISSING';
        const red = nwk
            ? { bg: 'bg-red-50 dark:bg-red-500/10', bd: 'border-red-200 dark:border-red-500/30', ic: 'text-red-600', tx: 'text-red-800 dark:text-red-300', lbl: 'text-red-700', body: 'text-red-900 dark:text-red-200' }
            : { bg: 'bg-amber-50 dark:bg-amber-500/10', bd: 'border-amber-200 dark:border-amber-500/30', ic: 'text-amber-600', tx: 'text-amber-800 dark:text-amber-300', lbl: 'text-amber-700', body: 'text-amber-900 dark:text-amber-200' };

        const movement = (d.movements || []).length ? `
            <details class="mt-3 rounded-lg border border-gray-200 dark:border-slate-700">
                <summary class="cursor-pointer select-none px-4 py-2 text-xs font-semibold text-gray-600 flex items-center gap-2 dark:text-slate-300"><i data-lucide="route" class="h-3.5 w-3.5"></i> Movement Timeline (${d.movements.length})</summary>
                <ol class="px-4 pb-3 pt-1 space-y-2">
                    ${d.movements.map(m => `
                    <li class="text-xs">
                        <span class="font-semibold text-gray-800 dark:text-slate-100">${esc(m.action === 'logged' ? 'Logged into ' + m.to : m.action === 'sent' ? 'Sent from ' + m.from + ' to ' + m.to : 'Received in ' + m.to)}</span>
                        ${m.purpose ? `<span class="text-gray-500"> · ${esc(m.purpose)}</span>` : ''}
                        <div class="text-gray-500 dark:text-slate-400">${esc(m.by)} · ${esc(m.at)}</div>
                        ${m.notes ? `<div class="text-gray-600 dark:text-slate-300">${esc(m.notes)}</div>` : ''}
                    </li>`).join('')}
                </ol>
            </details>` : '';

        const buttons = d.found ? `
            <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-2 dark:border-slate-800">
                ${d.tracker_url ? `<a href="${esc(d.tracker_url)}" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900"><i data-lucide="send" class="h-4 w-4"></i> Send / Receive</a>` : `
                <a href="${esc(LOG_FILE_URL)}?file=${encodeURIComponent(d.file_number)}" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900"><i data-lucide="file-plus" class="h-4 w-4"></i> Log File</a>`}
                ${d.label_url ? `<a href="${esc(d.label_url)}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="qr-code" class="h-4 w-4"></i> Print QR Label</a>` : ''}
                ${d.application_url ? `<a href="${esc(d.application_url)}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"><i data-lucide="file-text" class="h-4 w-4"></i> Open Application</a>` : ''}
            </div>` : '';

        const form = d.can_send_fr ? `
            <div data-fr-form class="px-6 pt-4 pb-5 border-t border-gray-100 dark:border-slate-800">
                <div class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2 dark:text-slate-300">Requester</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1 dark:text-slate-400">Requester Unit <span class="text-red-500">*</span></label>
                        <select data-fr-unit class="${field}"><option value="">— Select Unit —</option>${UNITS.map(u => `<option value="${u.id}">${esc(u.name)}</option>`).join('')}</select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1 dark:text-slate-400">Requesting Officer <span class="text-red-500">*</span></label>
                        <input data-fr-name type="text" class="${field} uppercase" placeholder="Name of the officer who needs the file">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1 dark:text-slate-400">Purpose <span class="text-red-500">*</span></label>
                        <select data-fr-purpose class="${field}"><option value="">— Select Purpose —</option>${PURPOSES.map(p => `<option>${esc(p)}</option>`).join('')}<option value="__other">Other…</option></select>
                        <input data-fr-purpose-other type="text" placeholder="Specify purpose *" class="hidden mt-1 ${field}">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1 dark:text-slate-400">Needed By</label>
                        <input data-fr-needed type="date" class="${field}">
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" data-fr-send class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"><i data-lucide="send" class="h-4 w-4"></i> Send File Search Request</button>
                    <span data-fr-msg class="text-xs"></span>
                </div>
            </div>` : '';

        result.innerHTML = `
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-800">
                    <div>
                        <div class="text-lg font-bold text-gray-900 dark:text-slate-100">${esc(d.file_number)}</div>
                        <div class="text-sm text-gray-500 dark:text-slate-400">${esc(d.file_title || '—')}</div>
                        ${d.tracking_id ? `<div class="mt-1 font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-300">${esc(d.tracking_id)}</div>` : ''}
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold ${meta.cls}"><i data-lucide="${meta.icon}" class="h-3.5 w-3.5"></i> ${esc(label)}</span>
                </div>
                <div class="px-6 py-3">
                    ${d.found ? `
                    <details class="mb-3 rounded-lg border border-gray-200 dark:border-slate-700">
                        <summary class="cursor-pointer select-none px-4 py-2 text-xs font-semibold text-gray-600 flex items-center gap-2 dark:text-slate-300"><i data-lucide="users" class="h-3.5 w-3.5"></i> File &amp; Property Details</summary>
                        <div class="px-4 pb-3 pt-1">
                            ${row('Application', d.application_no)}${row('Land File', d.land_file)}${row('Plot No.', d.plot_no)}${row('District', d.district)}
                            ${row('LGA', d.lga)}${row('Location', d.location)}${row('Proposed Use', d.proposed_use)}${row('Desk', d.desk)}
                        </div>
                    </details>` : ''}
                    ${location}
                    ${row('Duration with holder', d.duration_with_holder)}
                    ${row('Request Sent', d.fr_sent_at ? (d.fr_sent_at + (d.fr_request_no ? ' · ' + d.fr_request_no : '')) : '')}
                    ${isFound ? answerBox(d.fr_found, green, 'File Found', 'check-circle') : ''}
                    ${isNotFound ? answerBox(d.fr_not_found, red, `File Not Found (${nwk ? 'NWK' : 'Pending'})`, nwk ? 'alert-triangle' : 'hourglass') : ''}
                    <div class="mt-3 rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 dark:bg-indigo-500/10 dark:border-indigo-500/30">
                        <div class="text-xs font-semibold text-indigo-700 uppercase tracking-wide dark:text-indigo-300">Next Action</div>
                        <div data-next-action class="text-sm text-indigo-900 mt-0.5 dark:text-indigo-200">${esc(d.next_action)}</div>
                    </div>
                    ${movement}
                </div>
                ${form}
                ${buttons}
            </div>`;
        result.classList.remove('hidden');
        icons();
        wireForm(d);
    }

    function renderError(msg) {
        result.innerHTML = `<div class="rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">${esc(msg)}</div>`;
        result.classList.remove('hidden');
    }

    async function search() {
        const q = input.value.trim();
        if (!q) { openPicker(); return; }
        btn.disabled = true;
        try { render(await getJson(`${RESOLVE_URL}?query=${encodeURIComponent(q)}`)); }
        catch (e) { renderError('The search did not respond. Try again.'); }
        finally { btn.disabled = false; }
    }
    btn.addEventListener('click', search);

    function wireForm(d) {
        const box = result.querySelector('[data-fr-form]');
        if (!box) return;
        const purpose = box.querySelector('[data-fr-purpose]');
        const other = box.querySelector('[data-fr-purpose-other]');
        purpose.addEventListener('change', () => other.classList.toggle('hidden', purpose.value !== '__other'));
        box.querySelector('[data-fr-send]').addEventListener('click', async (e) => {
            const msg = box.querySelector('[data-fr-msg]');
            const payload = {
                file_number: d.file_number,
                requester_unit_id: box.querySelector('[data-fr-unit]').value,
                requester_name: box.querySelector('[data-fr-name]').value.trim(),
                purpose: purpose.value === '__other' ? other.value.trim() : purpose.value,
                needed_by: box.querySelector('[data-fr-needed]').value || null,
            };
            const missing = [!payload.requester_unit_id && 'unit', !payload.requester_name && 'officer', !payload.purpose && 'purpose'].filter(Boolean);
            if (missing.length) { msg.className = 'text-xs text-red-600'; msg.textContent = 'Give the requester ' + missing.join(', ') + '.'; return; }
            e.currentTarget.disabled = true;
            try {
                const r = await fetch(FR_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(payload) });
                const j = await r.json();
                if (!r.ok) throw new Error(Object.values(j.errors || {}).flat()[0] || j.message);
                render(j.result);
                loadAll();
            } catch (err) { msg.className = 'text-xs text-red-600'; msg.textContent = err.message || 'The request was not sent.'; e.currentTarget.disabled = false; }
        });
    }

    // ── File Search Report ─────────────────────────────────────────────────
    const repFrom = document.getElementById('qs-rep-from');
    const repTo = document.getElementById('qs-rep-to');
    const repClear = document.getElementById('qs-rep-clear');
    const rangeQuery = () => new URLSearchParams(Object.fromEntries([['from', repFrom.value], ['to', repTo.value]].filter(([, v]) => v))).toString();
    let lastReport = null;
    async function loadReport() {
        repClear.classList.toggle('hidden', !(repFrom.value || repTo.value));
        document.getElementById('qs-export-csv').href = CSV_URL + (rangeQuery() ? '?' + rangeQuery() : '');
        try {
            const { report, date } = await getJson(`${REPORT_URL}?${rangeQuery()}`);
            lastReport = report;
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = Number(v || 0).toLocaleString(); };
            set('qs-rep-today', report.submitted_today); set('qs-rep-today-found', report.today_found);
            set('qs-rep-today-notfound', report.today_not_found); set('qs-rep-today-awaiting', report.today_awaiting);
            set('qs-rep-blind', report.blind); set('qs-rep-found', report.found); set('qs-rep-notfound', report.not_found);
            set('qs-rep-missing', report.missing); set('qs-rep-awaiting', report.awaiting);
            document.getElementById('qs-rep-date').textContent = date;
        } catch (e) { /* the cards keep their dashes */ }
    }
    [repFrom, repTo].forEach(el => el.addEventListener('change', loadReport));
    repClear.addEventListener('click', () => { repFrom.value = ''; repTo.value = ''; loadReport(); });
    document.getElementById('qs-export-pdf').addEventListener('click', () => {
        if (!window.jspdf || !lastReport) return;
        const doc = new window.jspdf.jsPDF();
        doc.setFontSize(14); doc.text('KLAES-METRO · File Search Report', 14, 16);
        doc.setFontSize(9); doc.text((repFrom.value || repTo.value) ? `Period: ${repFrom.value || '…'} to ${repTo.value || '…'}` : 'All time', 14, 22);
        doc.autoTable({ startY: 28, head: [['Figure', 'Count']], body: [
            ['Requests today', lastReport.submitted_today], ['  Found today', lastReport.today_found], ['  Not found today', lastReport.today_not_found],
            ['  Awaiting today', lastReport.today_awaiting], ['Total blind / open', lastReport.blind], ['Total found', lastReport.found],
            ['Total not found', lastReport.not_found], ['Total not with KAMMA (NWK)', lastReport.missing], ['Total awaiting', lastReport.awaiting],
        ] });
        doc.save('kamma-file-search-report.pdf');
    });

    // ── File Search History (with the Registry Monitor's answer buttons) ────
    const logBox = document.getElementById('qs-feedback');
    const logSearch = document.getElementById('qs-log-search');
    const logFilters = document.getElementById('qs-log-filters');
    let logStatus = '';
    const badge = r => r.status === 'awaiting' ? 'bg-slate-100 text-slate-700' : r.status === 'found' ? 'bg-green-100 text-green-800' : (r.not_found_type === 'nwk' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
    async function loadLog() {
        try {
            const j = await getJson(`${LOG_URL}?status=${logStatus}&search=${encodeURIComponent(logSearch.value.trim())}`);
            document.getElementById('qs-log-total').textContent = `(${j.total})`;
            const chips = [['', 'All', j.total], ['awaiting', 'Awaiting', j.counts.awaiting], ['found', 'Found', j.counts.found], ['not_found', 'Not Found', j.counts.not_found]];
            logFilters.innerHTML = chips.map(([v, l, n]) => `<button type="button" data-log-filter="${v}" class="rounded-full border px-2.5 py-1 text-[11px] font-semibold ${logStatus === v ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'}">${l} <span class="opacity-75">${n}</span></button>`).join('');
            logBox.innerHTML = j.rows.length ? j.rows.map(r => `
                <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-2">
                        <button type="button" data-open="${esc(r.file_number)}" class="text-left text-sm font-bold text-gray-900 hover:text-indigo-700 dark:text-slate-100">${esc(r.file_number)}</button>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${badge(r)}">${esc(r.status_label)}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">${esc(r.file_title || '')}</div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-slate-400">${esc(r.request_no)} · ${esc(r.requester)} (${esc(r.requester_unit)}) · ${esc(r.requested_at)}${r.is_blind ? ' · <span class="font-semibold text-sky-700">Blind</span>' : ''}</div>
                    <div class="text-[11px] text-gray-500 dark:text-slate-400">${esc(r.purpose)}${r.needed_by ? ' · needed by ' + esc(r.needed_by) : ''}</div>
                    ${r.status === 'awaiting' ? `
                    <div class="mt-2 flex flex-wrap gap-1.5" data-respond="${esc(r.respond_url)}">
                        <button type="button" data-outcome="found" class="rounded-md bg-green-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-green-700">Found</button>
                        <button type="button" data-outcome="pending" class="rounded-md bg-amber-500 px-2 py-1 text-[11px] font-semibold text-white hover:bg-amber-600">Not Found (Pending)</button>
                        <button type="button" data-outcome="nwk" class="rounded-md bg-red-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-red-700">NWK</button>
                    </div>` : (r.responder ? `<div class="mt-1 text-[11px] text-gray-500 dark:text-slate-400">Answered by ${esc(r.responder)} · ${esc(r.responded_at)}${r.note ? ' · ' + esc(r.note) : ''}</div>` : '')}
                </div>`).join('') : '<div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-slate-400">No file search requests yet.</div>';
        } catch (e) { logBox.innerHTML = '<div class="px-5 py-8 text-center text-sm text-red-600">Could not load the file request log.</div>'; }
    }
    logFilters.addEventListener('click', e => { const b = e.target.closest('[data-log-filter]'); if (!b) return; logStatus = b.dataset.logFilter; loadLog(); });
    let logTimer; logSearch.addEventListener('input', () => { clearTimeout(logTimer); logTimer = setTimeout(loadLog, 300); });
    document.getElementById('qs-fb-refresh').addEventListener('click', loadLog);
    logBox.addEventListener('click', async e => {
        const open = e.target.closest('[data-open]');
        if (open) { input.value = open.dataset.open; search(); window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
        const b = e.target.closest('[data-outcome]');
        if (!b) return;
        const note = b.dataset.outcome === 'found' ? '' : (prompt('Note for the requester (optional):') ?? null);
        if (note === null) return;
        b.parentElement.querySelectorAll('button').forEach(x => x.disabled = true);
        await fetch(b.parentElement.dataset.respond, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ outcome: b.dataset.outcome, note }) });
        loadAll();
        if (input.value.trim()) search();
    });

    // ── Registry Monitor Feedback table ─────────────────────────────────────
    const fbTable = document.getElementById('qs-feedback-table');
    const fbSearch = document.getElementById('qs-tbl-search');
    async function loadFeedback() {
        try {
            const { rows } = await getJson(`${FEEDBACK_URL}?search=${encodeURIComponent(fbSearch.value.trim())}`);
            const th = 'px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400';
            const td = 'px-4 py-2.5 text-sm text-gray-700 dark:text-slate-300';
            fbTable.innerHTML = rows.length ? `
                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-800/60"><tr><th class="${th}">S/N</th><th class="${th}">File No.</th><th class="${th}">Requester</th><th class="${th}">Outcome</th><th class="${th}">Answered</th><th class="${th}">Note</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">${rows.map((r, i) => `
                        <tr><td class="${td}">${i + 1}</td>
                            <td class="${td}"><div class="font-semibold text-gray-900 dark:text-slate-100">${esc(r.file_number)}</div><div class="text-xs text-gray-500">${esc(r.file_title || '')}</div></td>
                            <td class="${td}">${esc(r.requester)}<div class="text-xs text-gray-500">${esc(r.requester_unit)}</div></td>
                            <td class="${td}"><span class="rounded-full px-2 py-0.5 text-[11px] font-bold ${badge(r)}">${esc(r.status_label)}</span></td>
                            <td class="${td} whitespace-nowrap">${esc(r.responded_at)}<div class="text-xs text-gray-500">${esc(r.responder)}</div></td>
                            <td class="${td}">${esc(r.note || '—')}</td></tr>`).join('')}
                    </tbody>
                </table>` : '<div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">No feedback from the Registry Monitor yet.</div>';
        } catch (e) { fbTable.innerHTML = '<div class="px-6 py-8 text-center text-sm text-red-600">Could not load the Registry Monitor feedback.</div>'; }
    }
    let fbTimer; fbSearch.addEventListener('input', () => { clearTimeout(fbTimer); fbTimer = setTimeout(loadFeedback, 300); });
    document.getElementById('qs-tbl-refresh').addEventListener('click', loadFeedback);

    const loadAll = () => { loadReport(); loadLog(); loadFeedback(); };
    loadAll();

    // Opened from elsewhere with ?file=…: search it straight away.
    const pre = new URLSearchParams(location.search).get('file');
    if (pre) { input.value = pre; search(); }
})();
</script>
