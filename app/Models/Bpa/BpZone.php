<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** The Kano zones of a BP file (Zone I-IV, UC, Other). Lives in kadca_bp_zones. */
class BpZone extends Model
{
    protected $table = 'bp_zones';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_other' => 'boolean', 'is_active' => 'boolean'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
