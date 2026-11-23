<?php

namespace App\Models\Kamma;

use App\Models\Dms\FileIndexing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A KAMMA bill: Application Processing Fee or Betterment (with the 4x no-BPA penalty). */
class Bill extends Model
{
    protected $table = 'bills';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'building_exists' => 'boolean', 'bpa_approved' => 'boolean'];
    }

    /** The KAMMA shadow file. */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(KsiReport::class, 'ksi_report_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
