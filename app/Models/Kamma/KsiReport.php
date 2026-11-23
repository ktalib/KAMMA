<?php

namespace App\Models\Kamma;

use App\Models\Dms\FileIndexing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A KSI (KAMMA Site Inspection) report: KLAES joint_site_inspection_reports, per kind (conversion, st, sltr, master). */
class KsiReport extends Model
{
    protected $table = 'ksi_reports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['inspection_date' => 'date', 'md_decided_at' => 'datetime', 'building_exists' => 'boolean', 'bpa_approved' => 'boolean', 'existing_structure' => 'boolean', 'has_extension' => 'boolean'];
    }

    /** The KAMMA shadow file. */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    public function bills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bill::class, 'ksi_report_id');
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KsiPhoto::class, 'ksi_report_id');
    }

    /** The site measurement the betterment rate is charged on: the RECOMMENDED area (KLAES rule), else the existing one. */
    public function chargeableArea(): ?float
    {
        $a = $this->recommended_site_area ?: $this->existing_site_area;

        return $a === null ? null : (float) $a;
    }
}
