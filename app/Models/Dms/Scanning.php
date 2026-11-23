<?php

namespace App\Models\Dms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** One scanned page (an image) uploaded onto a KAMMA indexed file. */
class Scanning extends Model
{
    protected $table = 'scannings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_pdf_converted' => 'boolean', 'is_scan_more' => 'boolean', 'is_deleted' => 'boolean'];
    }

    public function indexing(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    public function pageTyping(): HasOne
    {
        return $this->hasOne(PageTyping::class, 'scanning_id');
    }
}
