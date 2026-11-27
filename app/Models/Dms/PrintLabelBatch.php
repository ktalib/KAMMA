<?php

namespace App\Models\Dms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One Print Files Label run (KLAES PrintLabelBatch), for KAMMA shadow files. */
class PrintLabelBatch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'print_label_batches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['batch_size' => 'integer', 'generated_count' => 'integer', 'printed_at' => 'datetime'];
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(PrintLabelBatchItem::class, 'batch_id')->orderBy('label_position');
    }
}
