<script>
    // Log a File (KLAES create_file_tracker_page): live clock, Log ID, file details loaded from Quick Search,
    // purpose → timeline → expected return date, officer picker, and the File Tracker Log tab.
    window.logAFile = function (cfg) {
        const pad = (n) => String(n).padStart(2, '0');
        const newLogId = () => { const d = new Date(); return `LOG-${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}-${String(Math.floor(Math.random() * 1000)).padStart(3, '0')}`; };
        const blank = { file_number: '', priority: 'High', status_update: 'log-out', purpose: '', purpose_other: '', timeline_days: '', num_pages: '', notes: '', origin_unit_id: '', to_unit_id: '', holder_user_id: '', holder_name: '', override: false };
        return {
            tab: cfg.tab,
            form: Object.assign({}, blank, cfg.old),
            file: {},
            logId: newLogId(),
            clock: { time: '', date: '' },
            officers: [], officerQ: cfg.old.holder_name || '',
            preview: false, submitting: false,
            rows: [], counts: { mine: 0, awaiting: 0, others: 0, completed: 0 }, loading: false,
            group: '', priority: '', search: '', from: '', to: '', listTab: 'active', transitOnly: false,

            init() {
                const tick = () => { const d = new Date(); this.clock = { time: `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`, date: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` }; };
                tick(); setInterval(tick, 1000);
                if (this.form.file_number) this.loadFile();
                this.loadList();
                this.$watch('tab', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
            },
            async loadFile() {
                try {
                    const r = await fetch(`${cfg.fileUrl}?file_number=${encodeURIComponent(this.form.file_number)}`, { headers: { Accept: 'application/json' } });
                    this.file = r.ok ? await r.json() : {};
                } catch (e) { this.file = {}; }
            },
            applyPurpose() {
                if (cfg.purposes[this.form.purpose]) this.form.timeline_days = cfg.purposes[this.form.purpose];
            },
            get expectedReturn() {
                const days = parseInt(this.form.timeline_days, 10);
                if (! days) return '';
                const d = new Date(); d.setDate(d.getDate() + days);
                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            },
            async searchOfficers() {
                try {
                    const r = await fetch(`${cfg.officersUrl}?q=${encodeURIComponent(this.officerQ.trim())}`, { headers: { Accept: 'application/json' } });
                    this.officers = r.ok ? (await r.json()).officers : [];
                } catch (e) { this.officers = []; }
            },
            pickOfficer(o) { this.form.holder_user_id = o.id; this.form.holder_name = o.name; this.officerQ = o.name; this.officers = []; },
            resetForm() {
                const keep = this.form.file_number;
                this.form = Object.assign({}, blank, { file_number: keep });
                this.officerQ = ''; this.logId = newLogId();
            },
            previewRows() {
                const unit = (id) => { const el = this.$root.querySelector(`select[name="to_unit_id"] option[value="${id}"]`); return el ? el.textContent : ''; };
                return [['File No', this.form.file_number], ['File Title', this.file.file_title], ['Tracking ID', this.file.tracking_id || 'New on save'],
                    ['Priority', this.form.priority], ['Status Update', this.form.status_update === 'log-out' ? 'Log-out' : 'Log-in'],
                    ['Request Purpose', this.form.purpose === 'OTHER' ? this.form.purpose_other : this.form.purpose], ['Expected Return Date', this.expectedReturn],
                    ['Number of Pages', this.form.num_pages], ['Requester Unit', unit(this.form.to_unit_id)], ['File Requester', this.form.holder_name], ['Log ID', this.logId]];
            },
            async loadList() {
                this.loading = true;
                try {
                    const q = new URLSearchParams({ from: this.from, to: this.to, search: this.search });
                    const r = await fetch(`${cfg.listUrl}?${q}`, { headers: { Accept: 'application/json' } });
                    const j = await r.json();
                    this.rows = j.rows; this.counts = j.counts;
                } catch (e) { this.rows = []; }
                this.loading = false;
            },
            visible() {
                return this.rows.filter(r => (this.listTab === 'completed' ? r.status === 'Completed' : r.status !== 'Completed')
                    && (! this.group || r.group === this.group) && (! this.priority || r.priority === this.priority)
                    && (! this.transitOnly || r.status === 'In-transit'));
            },
            exportCsv() {
                const head = ['S/N', 'File No.', 'File title', 'Tracking ID', 'Priority', 'Handler', 'Location', 'Purpose', 'Log ID', 'Logged out', 'Expected return', 'Pages', 'Status'];
                const cell = v => `"${String(v ?? '').replace(/"/g, '""')}"`;
                const lines = [head.map(cell).join(',')].concat(this.visible().map((r, i) => [i + 1, r.file_number, r.file_title, r.tracking_id, r.priority, r.handler, r.location, r.purpose, r.log_id, r.logged_out, r.expected_return, r.pages, r.status].map(cell).join(',')));
                const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob(['﻿' + lines.join('\n')], { type: 'text/csv' })), download: 'kamma-file-tracker-log.csv' });
                a.click();
            },
        };
    };
</script>
