<?php

namespace App\Models\Tracking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One move of a tracked file or mail: logged, sent (in transit) or received. */
class FileMovement extends Model
{
    protected $table = 'file_movements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime', 'expected_return_on' => 'date'];
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(FileTracker::class, 'file_tracker_id');
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
