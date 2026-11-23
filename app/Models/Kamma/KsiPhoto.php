<?php

namespace App\Models\Kamma;

use App\Models\Dms\FileIndexing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A geocoded site photograph (GPS + time) on a KAMMA shadow file. */
class KsiPhoto extends Model
{
    protected $table = 'ksi_photos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['taken_at' => 'datetime'];
    }

    /** The KAMMA shadow file. */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }
}
