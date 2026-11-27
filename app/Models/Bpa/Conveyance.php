<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The Final Conveyance of Building Plan Approval (Development Permit, KAMMA FORM.pdf template 1). kadca_conveyances. */
class Conveyance extends Model
{
    protected $table = 'bpa_conveyances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'date_of_approval' => 'date', 'commencement_date' => 'date', 'expiry_date' => 'date',
            'declarant_date' => 'date', 'officer_date' => 'date'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }
}
