<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An officer's Site Inspection Report on a BP file (site visit, Site Analysis Report, measurements). kadca_site_inspections. */
class SiteInspection extends Model
{
    protected $table = 'bpa_site_inspections';

    protected $guarded = ['id'];

    public const RECOMMEND_APPROVAL = 'Recommended for approval (conveyance)';
    public const NOT_RECOMMENDED = 'Not recommended';
    public const CONTRAVENTION = 'Contravention found (issue notice)';

    public const RECOMMENDATIONS = [self::RECOMMEND_APPROVAL, self::NOT_RECOMMENDED, self::CONTRAVENTION];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'filed_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ZonalAssignment::class, 'zonal_assignment_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SiteInspectionPhoto::class, 'site_inspection_id');
    }
}
