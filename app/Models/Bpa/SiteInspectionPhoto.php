<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A geocoded site photograph (GPS + time taken on the device). kadca_site_inspection_photos. */
class SiteInspectionPhoto extends Model
{
    protected $table = 'bpa_site_inspection_photos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['taken_at' => 'datetime', 'latitude' => 'float', 'longitude' => 'float'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SiteInspection::class, 'site_inspection_id');
    }
}
