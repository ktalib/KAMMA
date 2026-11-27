<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An uploaded building plan: scanned booklet / soft copy ('plan') or CAD drawing, DXF or DWG ('cad'). */
class ApplicationFile extends Model
{
    protected $table = 'bpa_application_files';

    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }
}
