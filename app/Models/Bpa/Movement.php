<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One move of a BPA file between desks: commissioned, recommended, not recommended (with reason) or re-sent. */
class Movement extends Model
{
    protected $table = 'bpa_movements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }
}
