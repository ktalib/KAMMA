<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The Head of Zonal Office's Instruction to Inspect (like KLAES I-to-S). kadca_zonal_assignments. */
class ZonalAssignment extends Model
{
    protected $table = 'bpa_zonal_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scope' => 'array', 'due_date' => 'date', 'issued_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(SiteInspection::class, 'zonal_assignment_id');
    }
}
