<?php

namespace App\Models\Kamma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A KAMMA Memo (KLAES memos), per kind. */
class Memo extends Model
{
    protected $table = 'memos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['planner_recommendation_date' => 'date', 'md_recommendation_date' => 'date'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(KsiReport::class, 'ksi_report_id');
    }
}
