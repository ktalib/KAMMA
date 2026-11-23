<?php

namespace App\Models\Dms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The page type of one scanned page (KLAES pagetypings). */
class PageTyping extends Model
{
    protected $table = 'page_typings';

    protected $guarded = ['id'];

    public function scanning(): BelongsTo
    {
        return $this->belongsTo(Scanning::class, 'scanning_id');
    }

    public function indexing(): BelongsTo
    {
        return $this->belongsTo(FileIndexing::class, 'file_indexing_id');
    }

    /** The page type as shown: the "Other" text when Other was picked. */
    public function typeLabel(): string
    {
        return $this->page_type === 'Other' && $this->page_type_others ? $this->page_type_others : $this->page_type;
    }

    public function subtypeLabel(): ?string
    {
        return $this->page_subtype === 'Other' && $this->page_subtype_others ? $this->page_subtype_others : $this->page_subtype;
    }
}
