<?php

namespace App\Services\Klaes;

use App\Support\LandUse;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Land files as KLAES indexed them (dbo.file_indexings), read through the unprefixed, read-only
 * 'klaes' connection. KAMMA never writes these rows.
 *
 * KLAES rules kept here: deleted and decommissioned files are left out; a file can carry its number in
 * several columns, so the search matches all of them; (T) files are indexed under temp_file_no;
 * reads use NOLOCK because klas has no snapshot isolation and busy tables would otherwise block.
 */
class IndexedFiles
{
    private const NUMBER_COLUMNS = ['file_number', 'mls_file_no', 'kangis_file_no', 'new_kangis_file_no', 'temp_file_no'];

    private const DETAIL_COLUMNS = [
        'id', 'file_number', 'file_title', 'land_use_type', 'plot_number', 'district', 'lga', 'location',
        'plot_size', 'street_name', 'phone', 'residence_address', 'title_status', 'title_status_type',
    ];

    /**
     * @param  bool  $numbersOnly  match file numbers only, not the holder's name (the applicant portal: a member of the
     *                             public must not be able to look up other people's files by name)
     * @return list<array<string, mixed>>
     */
    public function search(string $term, int $limit = 15, bool $numbersOnly = false): array
    {
        $term = trim($term);
        if (mb_strlen($term) < ($numbersOnly ? 3 : 2)) {
            return [];
        }

        $like = '%'.str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $term).'%';
        if ($this->klaes()->getDriverName() !== 'sqlsrv') {
            $like = '%'.$term.'%';
        }

        $rows = $this->live()
            ->select(self::DETAIL_COLUMNS)
            ->where(function (Builder $q) use ($like, $numbersOnly) {
                foreach (self::NUMBER_COLUMNS as $col) {
                    $q->orWhere($col, 'like', $like);
                }
                if (! $numbersOnly) {
                    $q->orWhere('file_title', 'like', $like);
                }
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => $this->present($r))->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $row = $this->live()->select(self::DETAIL_COLUMNS)->where('id', $id)->first();

        return $row ? $this->present($row) : null;
    }

    private function live(): Builder
    {
        $table = $this->klaes()->getDriverName() === 'sqlsrv' ? 'file_indexings with (nolock)' : 'file_indexings';

        return $this->klaes()->table(DB::raw($table))
            ->where(fn (Builder $q) => $q->whereNull('is_deleted')->orWhere('is_deleted', 0))
            ->where(fn (Builder $q) => $q->whereNull('is_decommissioned')->orWhere('is_decommissioned', 0));
    }

    private function klaes()
    {
        return DB::connection('klaes');
    }

    /** @return array<string, mixed> */
    private function present(object $r): array
    {
        $code = LandUse::code($r->land_use_type, $r->file_number);

        return [
            'id' => (int) $r->id,
            'file_number' => $r->file_number,
            'file_title' => $this->clean($r->file_title),
            'plot_no' => $this->clean($r->plot_number),
            'district' => $this->clean($r->district),
            'lga' => $this->clean($r->lga),
            'location' => $this->clean($r->location),
            'plot_size' => $this->clean($r->plot_size),
            'street_name' => $this->clean($r->street_name),
            'phone' => $this->clean($r->phone),
            'address' => $this->clean($r->residence_address),
            'land_use' => $this->clean($r->land_use_type) ?? ($code ? LandUse::LABELS[$code] : null),
            'land_use_code' => $code,
            'title_status' => $this->titleStatus($r),
        ];
    }

    /** KLAES: title_status 1 = a status is recorded on the title (revoked, withdrawn, litigation, …). */
    private function titleStatus(object $r): string
    {
        if ((string) $r->title_status === '1') {
            $type = $this->clean($r->title_status_type);

            return $type ? 'Recorded: '.ucwords(str_replace('_', ' ', $type)) : 'Recorded (type not given)';
        }

        return 'None recorded';
    }

    private function clean(mixed $value): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $v === '' ? null : trim($v, '"');
    }
}
