<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A KAMMA internal memo on a BP file. kadca_memos. */
class Memo extends Model
{
    protected $table = 'bpa_memos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['memo_date' => 'date'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }
}
