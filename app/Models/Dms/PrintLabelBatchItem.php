<?php

namespace App\Models\Dms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One label on a Print Files Label batch (KLAES PrintLabelBatchItem): a KAMMA shadow file. */
class PrintLabelBatchItem extends Model
{
    protected $table = 'print_label_batch_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['qr_code_data' => 'array', 'is_printed' => 'boolean', 'printed_at' => 'datetime', 'label_position' => 'integer'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PrintLabelBatch::class, 'batch_id');
    }

    public function fileIndexing(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }
}
