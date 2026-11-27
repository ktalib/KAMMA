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
        return ['is_active' => 'boolean', 'is_external' => 'boolean'];
    }

    /** KAMMA's own units (External destinations are for File Movement only). */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_external', false)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * File Movement's list (user, 2026-11-26): KAMMA's desks, departments and units, then External: KADCA (KNUPDA),
     * Ministry of Land, the other MDAs. `department` is the group each shows under.
     */
    public function scopeForMovement(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('is_external')->orderBy('sort_order')->orderBy('name');
    }
}
