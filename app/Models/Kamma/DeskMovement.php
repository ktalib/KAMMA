<?php

namespace App\Models\Kamma;

use Illuminate\Database\Eloquent\Model;

/** One step of a land file through the KAMMA desks (AppSupportKammaDesk). */
class DeskMovement extends Model
{
    protected $table = 'desk_movements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }
}
