<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A KAMMA bill, kept in kadca_bills until the user approves writing KAMMA bills into KLAES `billing` with source = KAMMA.
 *
 * source: initial (the BPA application fee, on an application) | betterment | contravention | legacy (on a KLAES land
 * file; KLAES REV-M). The particulars below read the bill's own columns first and fall back to its application.
 */
class Bill extends Model
{
    protected $table = 'bpa_bills';

    protected $guarded = ['id'];

    public const SOURCES = [
        'initial' => 'BPA Initial Bill',
        'betterment' => 'Betterment Bill',
        'contravention' => 'Contravention Bill',
        'legacy' => 'Bill Legacy',
    ];

    protected function casts(): array
    {
        return ['rate' => 'decimal:2', 'amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'paid_on' => 'date',
            'payment_recorded_at' => 'datetime', 'details' => 'array', 'due_on' => 'date', 'receipted_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'bpa_application_id');
    }

    /** The BPA application fee bills (Architecture › Payment, KAMMA Bills & Payments). */
    public function scopeInitial(Builder $query): Builder
    {
        return $query->where('source', 'initial');
    }

    public function payer(): ?string
    {
        return $this->payer_name ?: $this->application?->developer_name;
    }

    public function fileNo(): ?string
    {
        return $this->file_number ?: ($this->application?->bp_file_no ?: $this->application?->file_number);
    }

    /** Plot No., District, LGA, Location (KLAES order): the bill's own value, else its application's. */
    public function place(string $part): ?string
    {
        return $this->{$part} ?: $this->application?->{$part};
    }

    public function balance(): float
    {
        return max(0, round((float) $this->amount - (float) $this->amount_paid, 2));
    }
}
