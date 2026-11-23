<?php

namespace App\Models\Tracking;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A KAMMA unit files and mails move between (editable list; desks are linked by `stage`). */
class Unit extends Model
{
    protected $table = 'units';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
