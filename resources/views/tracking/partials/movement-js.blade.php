<script>
    // File Movement (Department) (KLAES secretariat-file-log): scan to receive / send, batch, log manually, tables.
    window.deptMovement = (cfg) => {
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const post = (url, body) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body || {}) });
        const zero = { total: 0, files: 0, mails: 0 };
        return {
            office: cfg.office, officeName: cfg.officeName, units: cfg.units, purposes: cfg.purposes, unitUrl: cfg.unitUrl,
            counts: { pending: zero, held: zero, today: zero, transit: zero, outgoing: zero },
            rows: [], kind: 'files', dir: 'incoming', state: 'pending', search: '', from: '', to: '',
            mode: 'receive', batchMode: false, batch: [], scanQ: '', scanMsg: '', scanOk: false, found: null, sendTo: '', sendPurpose: '',
            changing: false, manual: false, auto: false, timer: null,
            m: { kind: 'mail', reference: '', title: '', mail_from: '', q: '', files: [], file_indexing_id: null, msg: '' },

            init() { this.load(); this.$nextTick(() => this.$refs.scan && this.$refs.scan.focus()); },
            async load() {
                if (! this.office) return;
                const q = new URLSearchParams({ office: this.office, kind: this.kind, dir: this.dir, state: this.state, search: this.search, from: this.from, to: this.to });
                try {
                    const j = await (await fetch(`${cfg.dataUrl}?${q}`, { headers: { Accept: 'application/json' } })).json();
                    this.rows = j.rows; this.counts = j.counts;
                } catch (e) { this.rows = []; }
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
            setMode(m) { this.mode = m; this.found = null; this.scanMsg = ''; this.$refs.scan.focus(); },
            say(msg, ok) { this.scanMsg = msg; this.scanOk = ok; },
            async find() {
                const q = this.scanQ.trim();
                if (! q) return;
                this.scanQ = ''; this.found = null;
                const j = await (await fetch(`${cfg.scanUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })).json();
                if (! j.found) return this.say(j.message, false);
                if (this.mode === 'send') {
                    if (j.status === 'in_transit') return this.say(`${j.file_number} is in transit to ${j.to_unit}; it must be received first.`, false);
                    if (j.unit_id !== this.office) return this.say(`${j.file_number} is in ${j.unit}, not in ${this.officeName}.`, false);
                    this.found = j; return this.say('', true);
                }
                if (j.status !== 'in_transit' || j.to_unit_id !== this.office) return this.say(`${j.file_number} is not on its way to ${this.officeName} (it is ${j.status === 'in_transit' ? 'going to ' + j.to_unit : 'in ' + j.unit}).`, false);
                if (this.batchMode) {
                    if (! this.batch.find(b => b.tracking_id === j.tracking_id)) this.batch.push(j);
                    return this.say(`${j.file_number} added to the batch.`, true);
                }
                await this.receive(j.receive_url, `${j.file_number} received in ${this.officeName}.`);
            },
            async receive(url, msg) {
                const r = await post(url);
                if (r.ok) { this.say(msg || 'Received.', true); } else { const j = await r.json().catch(() => ({})); this.say(Object.values(j.errors || {}).flat()[0] || 'Not received.', false); }
                this.load();
            },
            async receiveBatch() {
                for (const b of this.batch) await post(b.receive_url);
                this.say(`${this.batch.length} file(s) received in ${this.officeName}.`, true);
                this.batch = []; this.load();
            },
            async send() {
                if (! this.sendTo || ! this.sendPurpose) return this.say('Choose where to send it and the purpose.', false);
                const r = await post(this.found.send_url, { to_unit_id: this.sendTo, purpose: this.sendPurpose });
                if (r.ok) { this.say(`${this.found.file_number} sent; it shows as in transit until received.`, true); this.found = null; this.sendTo = ''; this.sendPurpose = ''; }
                else { const j = await r.json().catch(() => ({})); this.say(Object.values(j.errors || {}).flat()[0] || 'Not sent.', false); }
                this.load();
            },
            async findFiles() {
                if (this.m.q.trim().length < 2) { this.m.files = []; return; }
                this.m.files = (await (await fetch(`${cfg.filesUrl}?q=${encodeURIComponent(this.m.q.trim())}`, { headers: { Accept: 'application/json' } })).json()).files;
            },
            async logManual() {
                const body = { office: this.office, kind: this.m.kind, reference: this.m.reference, title: this.m.title, mail_from: this.m.mail_from, file_indexing_id: this.m.file_indexing_id };
                const r = await post(cfg.logUrl, body);
                const j = await r.json().catch(() => ({}));
                if (! r.ok) { this.m.msg = Object.values(j.errors || {}).flat()[0] || j.message || 'Not logged.'; return; }
                this.manual = false;
                this.m = { kind: 'mail', reference: '', title: '', mail_from: '', q: '', files: [], file_indexing_id: null, msg: '' };
                this.say(`Logged as ${j.tracking_id}. Print its label from the file page.`, true);
                this.load();
            },
            toggleAuto() { this.auto = ! this.auto; clearInterval(this.timer); if (this.auto) this.timer = setInterval(() => this.load(), 30000); },
            exportCsv() {
                const cell = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
                const lines = [['S/N', 'File Number', 'Tracking ID', 'File Title', 'From', 'To', 'Date', 'Purpose', 'Notes', 'Due'].map(cell).join(',')]
                    .concat(this.rows.map((r, i) => [i + 1, r.file_number, r.tracking_id, r.file_title, r.from, r.to, r.date, r.purpose, r.notes, r.due].map(cell).join(',')));
                Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob(['﻿' + lines.join('\n')], { type: 'text/csv' })), download: `kamma-file-movement-${this.dir}.csv` }).click();
            },
        };
    };
</script>
