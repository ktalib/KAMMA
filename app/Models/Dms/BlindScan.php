<?php

namespace App\Models\Dms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A page in EDMS/BLIND_SCAN/KAMMA_Raw (kamma_blind_scans), waiting for Scan Upload to take it into its file. */
class BlindScan extends Model
{
    public const PENDING = 'pending';
    public const MOVED = 'moved';

    protected $table = 'blind_scans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['moved_at' => 'datetime', 'file_size' => 'integer'];
    }

    public function indexing(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    /** Where the page sits on the edms disk. */
    public function path(): string
    {
        return $this->folder.'/'.$this->relative_path;
    }
}
