<?php

namespace App\Models\Tracking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A file search request (KLAES Quick Search concept): awaiting, then Found or Not Found (Pending / NWK). */
class FileRequest extends Model
{
    protected $table = 'file_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['needed_by' => 'date', 'responded_at' => 'datetime', 'is_blind' => 'boolean'];
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(FileTracker::class, 'file_tracker_id');
    }
}
