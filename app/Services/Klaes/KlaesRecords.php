<?php

namespace App\Services\Klaes;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Information Products and Land Records: KLAES's own records, on the same KLAES data (user 2026-11-23: "KLAES data
 * tables on the same KLAES data ... each with a dashboard and search"). Read-only through the unprefixed 'klaes'
 * connection, with NOLOCK after the alias (`pra as p with (nolock)`) because klas has no snapshot isolation.
 *
 * Sources, as KLAES's own screens use them:
 *  - Letter of Grant/RofO, Occupancy Permit, Post CofO: dbo.pra (KLAES RofoStagingDashboard / OpsDashboard read pra
 *    by instrument_type); Land Records > Letter of Grant = the RofOs KLAES generated (pra.system_source *_ROFO).
 *  - Certificate of Occupancy: dbo.CofO_staging (KLAES property card CofO data).
 *  - Site Plan/Parcel Plan: plan pages typed in KLAES EDMS (dbo.pagetypings with a PageSubType named "…Plan").
 *  - File Records / File Status: dbo.file_indexings (deleted rows left out; File Status lists title statuses,
 *    decommissioned and duplicate files).
 * Columns keep the KLAES property order: Plot No., District, LGA, Location.
 */
class KlaesRecords
{
    public const GROUPS = [
        'products' => ['title' => 'Information Products', 'subtitle' => 'Letters of Grant, Occupancy Permits, Site Plans and Certificates of Occupancy as recorded in KLAES.', 'icon' => 'scroll-text', 'accent' => 'teal'],
        'land' => ['title' => 'Land Records', 'subtitle' => 'Land files, Letters of Grant, Post-CofO instruments and file status as recorded in KLAES.', 'icon' => 'book-open', 'accent' => 'indigo'],
    ];

    private const PRA_FILE = "COALESCE(NULLIF(p.mlsFNo, ''), NULLIF(p.kangisFileNo, ''), NULLIF(p.NewKANGISFileno, ''), p.fileno)";

    private const POST_COFO = ['Deed of Assignment', 'Deed of Mortgage', 'Deed of Surrender and Release', 'Subdivision', 'Deed of Purchase',
        'Tripartite Mortgage', 'Deed of Sub Lease', 'Power of Attorney', 'Deed of Devolution', 'Surrender and Release', 'Deed of Release',
        'Deed of Surrender', 'Deed of lease', 'Irrevocable Power of Attorney', 'Deed of Gift', 'Change Of Purpose', 'Merger', 'Extension'];

    private const GENERATED_ROFO = ['LAND_ROFO' => 'Lands', 'OSS_ROFO' => 'One Stop Shop', 'ST_ROFO' => 'Sectional Titling', 'SLTR_ROFO' => 'SLTR'];

    /** @return array<string, array<string, mixed>> */
    public function datasets(?string $group = null): array
    {
        $all = [
            'rofo' => [
                'group' => 'products', 'title' => 'Letter of Grant / RofO', 'icon' => 'scroll-text', 'accent' => 'emerald',
                'subtitle' => 'Rights of Occupancy (RofO) recorded in KLAES: legacy captures and the RofOs KLAES generates.',
                'from' => 'pra as p', 'date' => 'p.created_at', 'lga' => 'p.lgsaOrCity', 'district' => 'p.districtName', 'land_use' => 'p.land_use',
                'breakdown' => ['p.instrument_type', 'Instrument'],
                'where' => fn (Builder $q) => $this->live($q, 'p')->where(fn ($w) => $w->where('p.instrument_type', 'like', 'Right of Occupancy%')->orWhere('p.instrument_type', 'R-OFO')),
                'select' => [self::PRA_FILE.' as file_number', 'p.Grantee as holder', 'p.rofo_number as reference', 'p.plot_no as plot_no', 'p.districtName as district',
                    'p.lgsaOrCity as lga', 'p.location as location', 'p.land_use as land_use', 'p.instrument_type as instrument', 'p.serialNo as serial_no',
                    'p.pageNo as page_no', 'p.volumeNo as volume_no', 'p.transaction_date as dated', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'Grantee', 'reference' => 'RofO No.', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'instrument' => 'Instrument', 'registration' => 'Reg. (S/P/V)', 'dated' => 'Date'],
                'search' => ['p.mlsFNo', 'p.kangisFileNo', 'p.NewKANGISFileno', 'p.fileno', 'p.Grantee', 'p.rofo_number', 'p.plot_no', 'p.location'],
                'order' => 'p.id',
            ],
            'op' => [
                'group' => 'products', 'title' => 'Occupancy Permits (OP)', 'icon' => 'badge-check', 'accent' => 'sky',
                'subtitle' => 'Occupancy Permits and OP transfers of title recorded in KLAES (OPs Dashboard source).',
                'from' => 'pra as p', 'date' => 'p.created_at', 'lga' => 'p.lgsaOrCity', 'district' => 'p.districtName', 'land_use' => 'p.land_use',
                'breakdown' => ['p.instrument_type', 'OP kind'],
                'where' => fn (Builder $q) => $this->live($q, 'p')->whereIn('p.instrument_type', ['Occupancy Permit (OP)', 'Occupancy Permit', 'Transfer of Title (OP)']),
                'select' => [self::PRA_FILE.' as file_number', "COALESCE(NULLIF(p.Grantee, ''), NULLIF(p.Assignee, ''), p.party_1) as holder", 'p.op_serial_number as reference',
                    'p.plot_no as plot_no', 'p.districtName as district', 'p.lgsaOrCity as lga', 'p.location as location', 'p.land_use as land_use',
                    'p.instrument_type as instrument', 'p.op_type as op_type', 'p.transaction_date as dated', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'OP Holder', 'reference' => 'OP Serial', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'instrument' => 'Kind', 'op_type' => 'OP Type', 'dated' => 'Date'],
                'search' => ['p.mlsFNo', 'p.kangisFileNo', 'p.NewKANGISFileno', 'p.fileno', 'p.Grantee', 'p.Assignee', 'p.op_serial_number', 'p.plot_no', 'p.location'],
                'order' => 'p.id',
            ],
            'site-plans' => [
                'group' => 'products', 'title' => 'Site Plans / Parcel Plans', 'icon' => 'map', 'accent' => 'violet',
                'subtitle' => 'Plan pages (site, survey, parcel and location plans) typed on land files in KLAES EDMS.',
                'from' => 'pagetypings as p', 'date' => 'p.created_at', 'lga' => 'f.lga', 'district' => 'f.district', 'land_use' => 'f.land_use_type',
                'breakdown' => ['s.PageSubType', 'Plan type'],
                'joins' => [['file_indexings as f', 'f.id', '=', 'p.file_indexing_id'], ['PageSubType as s', 's.id', '=', 'p.page_subtype']],
                'where' => fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('p.is_deleted')->orWhere('p.is_deleted', 0))->whereIn('p.page_subtype', $this->planSubtypeIds()),
                'select' => ['f.file_number as file_number', 'f.file_title as holder', 's.PageSubType as reference', 'p.page_code as page_code', 'f.plot_number as plot_no',
                    'f.district as district', 'f.lga as lga', 'f.location as location', 'f.land_use_type as land_use', 'p.page_number as page_number', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'File Title', 'reference' => 'Plan Type', 'page_code' => 'Page Code', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'page_number' => 'Page', 'created_at' => 'Typed On'],
                'search' => ['f.file_number', 'f.file_title', 'f.plot_number', 'p.page_code'],
                'order' => 'p.id',
            ],
            'cofo' => [
                'group' => 'products', 'title' => 'Certificates of Occupancy', 'icon' => 'award', 'accent' => 'amber',
                'subtitle' => 'Certificates of Occupancy recorded in KLAES (property card CofO data).',
                'from' => 'CofO_staging as p', 'date' => 'p.created_at', 'lga' => 'p.lgsaOrCity', 'district' => 'p.districtName', 'land_use' => 'p.land_use',
                'breakdown' => ['p.cofo_type', 'CofO type'],
                'where' => fn (Builder $q) => $this->live($q, 'p'),
                'select' => ["COALESCE(NULLIF(p.np_fileno, ''), NULLIF(p.mlsFNo, ''), NULLIF(p.kangisFileNo, ''), NULLIF(p.NewKANGISFileno, ''), p.fileno) as file_number",
                    "COALESCE(NULLIF(p.Grantee, ''), p.Assignee) as holder", 'p.cofo_no as reference', 'p.plot_no as plot_no', 'p.districtName as district', 'p.lgsaOrCity as lga',
                    'p.location as location', 'p.land_use as land_use', 'p.serialNo as serial_no', 'p.pageNo as page_no', 'p.volumeNo as volume_no', 'p.cofo_date as dated', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'Holder', 'reference' => 'CofO No.', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'registration' => 'Reg. (S/P/V)', 'dated' => 'CofO Date'],
                'search' => ['p.np_fileno', 'p.mlsFNo', 'p.kangisFileNo', 'p.NewKANGISFileno', 'p.fileno', 'p.Grantee', 'p.cofo_no', 'p.plot_no', 'p.location'],
                'order' => 'p.id',
            ],
            'file-records' => [
                'group' => 'land', 'title' => 'File Records', 'icon' => 'folder-open', 'accent' => 'indigo',
                'subtitle' => 'Land files indexed in KLAES (File Indexing), deleted and decommissioned files left out.',
                'from' => 'file_indexings as f', 'date' => 'f.created_at', 'lga' => 'f.lga', 'district' => 'f.district', 'land_use' => 'f.land_use_type',
                'breakdown' => ['f.registry', 'Registry'],
                'where' => fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('f.is_deleted')->orWhere('f.is_deleted', 0))->where(fn ($w) => $w->whereNull('f.is_decommissioned')->orWhere('f.is_decommissioned', 0)),
                'select' => ['f.file_number as file_number', 'f.file_title as holder', 'f.registry as reference', 'f.plot_number as plot_no', 'f.district as district', 'f.lga as lga',
                    'f.location as location', 'f.land_use_type as land_use', 'f.batch_no as batch_no', 'f.kamma_matching as kamma_matching', 'f.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'File Title', 'reference' => 'Registry', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'batch_no' => 'Batch', 'kamma' => 'KAMMA', 'created_at' => 'Indexed On'],
                'search' => ['f.file_number', 'f.mls_file_no', 'f.kangis_file_no', 'f.new_kangis_file_no', 'f.temp_file_no', 'f.file_title', 'f.plot_number', 'f.location'],
                'order' => 'f.id',
            ],
            'letter-of-grant' => [
                'group' => 'land', 'title' => 'Letter of Grant', 'icon' => 'file-signature', 'accent' => 'emerald',
                'subtitle' => 'Letters of Grant (RofO) generated in KLAES by Lands, One Stop Shop, Sectional Titling and SLTR.',
                'from' => 'pra as p', 'date' => 'p.created_at', 'lga' => 'p.lgsaOrCity', 'district' => 'p.districtName', 'land_use' => 'p.land_use',
                'breakdown' => ['p.system_source', 'Module'],
                'where' => fn (Builder $q) => $this->live($q, 'p')->whereIn('p.system_source', array_keys(self::GENERATED_ROFO)),
                'select' => [self::PRA_FILE.' as file_number', 'p.Grantee as holder', 'p.rofo_number as reference', 'p.plot_no as plot_no', 'p.districtName as district',
                    'p.lgsaOrCity as lga', 'p.location as location', 'p.land_use as land_use', 'p.system_source as module', 'p.transaction_date as dated', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'Grantee', 'reference' => 'RofO No.', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'module' => 'Module', 'dated' => 'Date'],
                'search' => ['p.mlsFNo', 'p.kangisFileNo', 'p.NewKANGISFileno', 'p.fileno', 'p.Grantee', 'p.rofo_number', 'p.plot_no'],
                'order' => 'p.id',
            ],
            'post-cofo' => [
                'group' => 'land', 'title' => 'Post CofO', 'icon' => 'git-branch', 'accent' => 'violet',
                'subtitle' => 'Instruments registered after the title: assignments, mortgages, surrenders, subdivisions, change of purpose…',
                'from' => 'pra as p', 'date' => 'p.created_at', 'lga' => 'p.lgsaOrCity', 'district' => 'p.districtName', 'land_use' => 'p.land_use',
                'breakdown' => ['p.instrument_type', 'Instrument'],
                'where' => fn (Builder $q) => $this->live($q, 'p')->whereIn('p.instrument_type', self::POST_COFO),
                'select' => [self::PRA_FILE.' as file_number', 'p.instrument_type as reference',
                    "COALESCE(NULLIF(p.Assignor, ''), NULLIF(p.Mortgagor, ''), NULLIF(p.Surrenderor, ''), NULLIF(p.Lessor, ''), NULLIF(p.Grantor, ''), NULLIF(p.Donor, ''), NULLIF(p.Vendor, ''), p.party_1) as holder",
                    "COALESCE(NULLIF(p.Assignee, ''), NULLIF(p.Mortgagee, ''), NULLIF(p.Surrenderee, ''), NULLIF(p.Lessee, ''), NULLIF(p.Grantee, ''), NULLIF(p.Donee, ''), NULLIF(p.Purchaser, ''), p.party_2) as to_party",
                    'p.plot_no as plot_no', 'p.districtName as district', 'p.lgsaOrCity as lga', 'p.location as location', 'p.land_use as land_use', 'p.serialNo as serial_no',
                    'p.pageNo as page_no', 'p.volumeNo as volume_no', 'p.transaction_date as dated', 'p.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'reference' => 'Instrument', 'holder' => 'From (Party 1)', 'to_party' => 'To (Party 2)', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'registration' => 'Reg. (S/P/V)', 'dated' => 'Date'],
                'search' => ['p.mlsFNo', 'p.kangisFileNo', 'p.NewKANGISFileno', 'p.fileno', 'p.Assignor', 'p.Assignee', 'p.Mortgagor', 'p.Mortgagee', 'p.plot_no'],
                'order' => 'p.id',
            ],
            'file-status' => [
                'group' => 'land', 'title' => 'File Status', 'icon' => 'shield-alert', 'accent' => 'rose',
                'subtitle' => 'Files with a title status recorded (revoked, withdrawn, litigation, re-grant…), decommissioned files and duplicates.',
                'from' => 'file_indexings as f', 'date' => 'f.created_at', 'lga' => 'f.lga', 'district' => 'f.district', 'land_use' => 'f.land_use_type',
                'breakdown' => ['f.title_status_type', 'Title status'],
                'where' => fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('f.is_deleted')->orWhere('f.is_deleted', 0))
                    ->where(fn ($w) => $w->where('f.title_status', '1')->orWhere('f.is_decommissioned', 1)->orWhere('f.is_duplicate', 1)),
                'select' => ['f.file_number as file_number', 'f.file_title as holder', 'f.title_status as title_status', 'f.title_status_type as title_status_type',
                    'f.is_decommissioned as is_decommissioned', 'f.is_duplicate as is_duplicate', "COALESCE(NULLIF(f.title_status_remark, ''), f.decommissioning_reason) as remark",
                    'f.plot_number as plot_no', 'f.district as district', 'f.lga as lga', 'f.location as location', 'f.land_use_type as land_use', 'f.successor_file_no as successor', 'f.created_at as created_at'],
                'columns' => ['file_number' => 'File No.', 'holder' => 'File Title', 'status' => 'Status', 'remark' => 'Remark', 'plot_no' => 'Plot No.', 'district' => 'District', 'lga' => 'LGA', 'location' => 'Location', 'land_use' => 'Land Use', 'successor' => 'Successor File'],
                'search' => ['f.file_number', 'f.file_title', 'f.plot_number', 'f.title_status_type', 'f.successor_file_no'],
                'status_filter' => ['title' => 'Title status recorded', 'decommissioned' => 'Decommissioned', 'duplicate' => 'Duplicate'],
                'order' => 'f.id',
            ],
        ];

        return $group === null ? $all : array_filter($all, fn ($d) => $d['group'] === $group);
    }

    /** @return array<string, mixed> */
    public function dataset(string $group, string $key): array
    {
        $d = $this->datasets($group)[$key] ?? null;
        abort_if($d === null, 404);

        return $d + ['key' => $key];
    }

    /**
     * One page of a dataset, filtered.
     *
     * @param  array{q?:string,district?:string,lga?:string,land_use?:string,from?:string,to?:string,status?:string}  $f
     */
    public function page(array $d, array $f, int $perPage = 25): LengthAwarePaginator
    {
        $q = $this->filtered($d, $f)->select(array_map(fn ($s) => DB::raw($s), $d['select']))->orderByDesc($d['order']);
        $page = $q->paginate($perPage)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn ($r) => $this->present((array) $r)));

        return $page;
    }

    /** Rows for the CSV export (capped). @return list<array<string, mixed>> */
    public function export(array $d, array $f, int $limit = 5000): array
    {
        return $this->filtered($d, $f)->select(array_map(fn ($s) => DB::raw($s), $d['select']))->orderByDesc($d['order'])
            ->limit($limit)->get()->map(fn ($r) => $this->present((array) $r))->all();
    }

    /**
     * Figures for a dataset's dashboard strip and the group dashboard: total, this year, top LGAs, top land uses,
     * the dataset's own breakdown, and the last 12 months. Cached 10 minutes (KLAES tables are large).
     *
     * @return array<string, mixed>
     */
    public function figures(array $d): array
    {
        $empty = ['total' => 0, 'this_year' => 0, 'by_lga' => [], 'by_land_use' => [], 'by_breakdown' => [], 'monthly' => [], 'error' => null];

        try {
            return Cache::remember('kamma:records:'.$d['key'], 600, function () use ($d) {
                $base = fn () => $this->filtered($d, []);
                $year = now()->startOfYear()->toDateTimeString();
                $top = fn (string $col) => $base()->selectRaw("{$col} as label, count(*) as n")->whereNotNull(DB::raw($col))->where(DB::raw($col), '<>', '')
                    ->groupBy(DB::raw($col))->orderByDesc('n')->limit(8)->get()->map(fn ($r) => ['label' => $this->tidy($r->label), 'n' => (int) $r->n])->all();
                $month = $this->klaes()->getDriverName() === 'sqlsrv' ? "CONVERT(char(7), {$d['date']}, 120)" : "strftime('%Y-%m', {$d['date']})";

                return [
                    'total' => $base()->count(),
                    'this_year' => $base()->where(DB::raw($d['date']), '>=', $year)->count(),
                    'by_lga' => $top($d['lga']),
                    'by_land_use' => $top($d['land_use']),
                    'by_breakdown' => $top($d['breakdown'][0]),
                    'monthly' => $base()->selectRaw("{$month} as ym, count(*) as n")->where(DB::raw($d['date']), '>=', now()->subMonths(11)->startOfMonth()->toDateTimeString())
                        ->groupBy(DB::raw($month))->orderBy('ym')->get()->map(fn ($r) => ['label' => (string) $r->ym, 'n' => (int) $r->n])->all(),
                    'error' => null,
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return ['error' => 'KLAES did not answer for this list. Try again in a moment.'] + $empty;
        }
    }

    /** @return list<string> distinct values for a filter dropdown (LGA, district, land use), cached. */
    public function options(array $d, string $which): array
    {
        try {
            return Cache::remember("kamma:records:{$d['key']}:opt:{$which}", 3600, function () use ($d, $which) {
                $col = $d[$which];

                return $this->filtered($d, [])->selectRaw("{$col} as v")->whereNotNull(DB::raw($col))->where(DB::raw($col), '<>', '')
                    ->groupBy(DB::raw($col))->orderBy('v')->limit(300)->pluck('v')->map(fn ($v) => $this->tidy($v))->unique(fn ($v) => mb_strtolower($v))->values()->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    private function filtered(array $d, array $f): Builder
    {
        $q = $this->klaes()->table(DB::raw($this->nolock($d['from'])));
        foreach ($d['joins'] ?? [] as [$table, $a, $op, $b]) {
            $q->leftJoin(DB::raw($this->nolock($table)), $a, $op, $b);
        }
        ($d['where'])($q);

        $term = trim((string) ($f['q'] ?? ''));
        if ($term !== '') {
            $like = $this->like($term);
            $q->where(function (Builder $w) use ($d, $like) {
                foreach ($d['search'] as $col) {
                    $w->orWhere($col, 'like', $like);
                }
            });
        }
        foreach (['district', 'lga', 'land_use'] as $key) {
            if (filled($f[$key] ?? null)) {
                $q->where($d[$key], $f[$key]);
            }
        }
        if (filled($f['from'] ?? null)) {
            $q->where(DB::raw($d['date']), '>=', $f['from'].' 00:00:00');
        }
        if (filled($f['to'] ?? null)) {
            $q->where(DB::raw($d['date']), '<=', $f['to'].' 23:59:59');
        }
        if (isset($d['status_filter']) && filled($f['status'] ?? null)) {
            match ($f['status']) {
                'title' => $q->where('f.title_status', '1'),
                'decommissioned' => $q->where('f.is_decommissioned', 1),
                'duplicate' => $q->where('f.is_duplicate', 1),
                default => null,
            };
        }

        return $q;
    }

    /** @param array<string, mixed> $r */
    private function present(array $r): array
    {
        foreach ($r as $k => $v) {
            $r[$k] = is_string($v) ? $this->tidy($v) : $v;
        }
        if (array_key_exists('serial_no', $r)) {
            $parts = array_filter([$r['serial_no'] ?? null, $r['page_no'] ?? null, $r['volume_no'] ?? null], fn ($v) => filled($v));
            $r['registration'] = $parts ? implode(' / ', $parts) : null;
        }
        if (array_key_exists('module', $r)) {
            $r['module'] = self::GENERATED_ROFO[$r['module']] ?? $r['module'];
        }
        if (array_key_exists('kamma_matching', $r)) {
            $r['kamma'] = (string) $r['kamma_matching'] === '1' ? 'Shadow file' : null;
        }
        if (array_key_exists('title_status_type', $r)) {
            $r['status'] = collect([
                (string) ($r['title_status'] ?? '') === '1' ? ($r['title_status_type'] ? ucwords(str_replace('_', ' ', $r['title_status_type'])) : 'Title status recorded') : null,
                (int) ($r['is_decommissioned'] ?? 0) === 1 ? 'Decommissioned' : null,
                (int) ($r['is_duplicate'] ?? 0) === 1 ? 'Duplicate' : null,
            ])->filter()->implode(' · ');
        }
        foreach (['dated', 'created_at'] as $k) {
            if (filled($r[$k] ?? null)) {
                try {
                    $r[$k] = \Illuminate\Support\Carbon::parse($r[$k])->format('d M Y');
                } catch (Throwable) {
                    // KLAES keeps some dates as free text: show them as captured.
                }
            }
        }

        return $r;
    }

    /** @return list<string> KLAES PageSubType ids whose name is a plan (site, survey, parcel, location …). */
    private function planSubtypeIds(): array
    {
        try {
            return Cache::remember('kamma:records:plan-subtypes', 3600, fn () => $this->klaes()->table(DB::raw($this->nolock('PageSubType')))
                ->where('PageSubType', 'like', '%plan%')->pluck('id')->map(fn ($id) => (string) $id)->all());
        } catch (Throwable) {
            return ['0'];
        }
    }

    private function live(Builder $q, string $alias): Builder
    {
        return $q->where(fn ($w) => $w->whereNull("{$alias}.is_deleted")->orWhere("{$alias}.is_deleted", 0));
    }

    private function like(string $term): string
    {
        return $this->klaes()->getDriverName() === 'sqlsrv'
            ? '%'.str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $term).'%'
            : '%'.$term.'%';
    }

    /** "pra as p" → "pra as p with (nolock)" on SQL Server (the hint goes after the alias). */
    private function nolock(string $from): string
    {
        return $this->klaes()->getDriverName() === 'sqlsrv' ? "{$from} with (nolock)" : $from;
    }

    private function klaes(): Connection
    {
        return DB::connection('klaes');
    }

    private function tidy(mixed $v): string
    {
        return trim(trim(preg_replace('/\s+/u', ' ', (string) $v)), '"');
    }
}
