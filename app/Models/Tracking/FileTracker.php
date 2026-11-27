<?php

namespace App\Models\Tracking;

use App\Models\Dms\FileIndexing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A tracked KAMMA file (a shadow file, numbered like its KLAES land file) or mail: its tracking ID, where it is, and its movements. */
class FileTracker extends Model
{
    protected $table = 'file_trackers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_on' => 'date'];
    }

    public function getRouteKeyName(): string
    {
        return 'tracking_id';
    }

    /** The KAMMA shadow file this tracker follows (null for a mail). */
    public function shadowFile(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    /** The Building Plan application this tracker follows (BPA moved in from KADCA, 2026-11-25). */
    public function bpaApplication(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Bpa\Application::class, 'bpa_application_id');
    }

    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'current_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FileMovement::class, 'file_tracker_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_on !== null && $this->due_on->isPast() && ! $this->due_on->isToday();
    }
}
