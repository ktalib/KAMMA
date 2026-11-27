<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A Building Plan Approval application (KAMMA Assessment Stage). Lives in kadca_bpa_applications.
 * The BP file is commissioned later, after the application bill is paid.
 */
class Application extends Model
{
    protected $table = 'bpa_applications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'documents_submitted' => 'array',
            'drawings_submitted' => 'array',
            'submitted_on' => 'date',
            'commissioned_at' => 'datetime',
        ];
    }

    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class, 'bpa_application_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class, 'bpa_application_id');
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class, 'bpa_application_id');
    }

    /** The physical BP file's tracking (File Tracking), once commissioned or logged. */
    public function tracker(): HasOne
    {
        return $this->hasOne(\App\Models\Tracking\FileTracker::class, 'bpa_application_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ZonalAssignment::class, 'bpa_application_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(SiteInspection::class, 'bpa_application_id');
    }

    /** The BP file the application was started from (BP indexing / commissioning, 2026-11-25). */
    public function bpFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BpFile::class, 'bp_file_id');
    }

    public function conveyance(): HasOne
    {
        return $this->hasOne(Conveyance::class, 'bpa_application_id');
    }

    public function memos(): HasMany
    {
        return $this->hasMany(Memo::class, 'bpa_application_id');
    }
}
