<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Building Plan file (kadca_bp_files): indexed (an existing BP file, numbered from the grouping) or
 * commissioned (a new one, given the next serial). The land-file half is the KLAES indexing record as it
 * was when the BP file was indexed; the BP half is the BP file's own indexing.
 */
class BpFile extends Model
{
    public const INDEXED = 'indexed';
    public const COMMISSIONED = 'commissioned';

    protected $table = 'bp_files';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['land_in_klaes' => 'boolean', 'is_deleted' => 'boolean', 'indexed_at' => 'datetime'];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_deleted', false);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(BpZone::class, 'bp_zone_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }

    /** The land file as the Application Form's step 1 shows it (same keys as IndexedFiles::present). */
    public function landFile(): array
    {
        return [
            'id' => $this->klaes_file_indexing_id, 'file_number' => $this->land_file_number, 'file_title' => $this->land_file_title,
            'plot_no' => $this->plot_no, 'district' => $this->district, 'lga' => $this->lga, 'location' => $this->location,
            'plot_size' => $this->plot_size, 'street_name' => $this->street_name, 'phone' => $this->phone, 'address' => $this->address,
            'land_use' => $this->land_use, 'land_use_code' => $this->land_use_code,
            'title_status' => $this->title_status ?? ($this->land_in_klaes ? 'None recorded' : 'Not in KLAES'),
            'in_klaes' => $this->land_in_klaes,
        ];
    }
}
