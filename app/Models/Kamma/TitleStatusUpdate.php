<?php

namespace App\Models\Kamma;

use Illuminate\Database\Eloquent\Model;

/**
 * A KAMMA Title Status Update record (KLAES title_status_applications shape). KAMMA keeps its own record only: it never
 * flags or decommissions the KLAES land file (no write on dbo.file_indexings for that).
 *
 * TYPES / INITIATED_BY / INITIATED_BY_BY_TYPE are KLAES's App\Models\TitleStatusApplication lists, without the types that
 * point at a second file (Re-granted From/To, Resettled From/To, Closed To, Continued From): those belong to KLAES's
 * re-grant / resettlement / closure workflows. "Other" reveals a required "specify" field (KLAES rule 6).
 */
class TitleStatusUpdate extends Model
{
    protected $table = 'title_status_updates';

    protected $guarded = ['id'];

    public const TYPES = [
        'Withdrawal (Application)',
        'Withdrawal (Allocation)',
        'Cancellation (RofO)',
        'Revoke (CofO)',
        'Litigation',
        'Amendment/Reconsideration (Application/RofO/CofO)',
        'Surrender',
        'Re-grant',
        'Resettlement',
        'Subdivision',
        'Merger',
        'Change of Purpose',
        'Extension',
        'Change of Name',
        'Separation',
        'Closed',
    ];

    public const INITIATED_BY = ['Ministry', 'Allottee', 'Applicant', 'Court Order'];

    /** Per-type options (KLAES). Single-option types lock the value; a type not listed takes every option. */
    public const INITIATED_BY_BY_TYPE = [
        'Withdrawal (Application)' => ['Applicant'],
        'Withdrawal (Allocation)' => ['Ministry', 'Allottee'],
        'Cancellation (RofO)' => ['Ministry', 'Allottee'],
        'Revoke (CofO)' => ['Ministry', 'Allottee'],
        'Litigation' => ['Court Order'],
        'Amendment/Reconsideration (Application/RofO/CofO)' => ['Ministry', 'Allottee'],
        'Surrender' => ['Applicant'],
        'Re-grant' => ['Ministry'],
        'Resettlement' => ['Ministry', 'Allottee'],
        'Subdivision' => ['Applicant', 'Ministry'],
        'Merger' => ['Applicant', 'Ministry'],
        'Change of Purpose' => ['Applicant', 'Ministry'],
        'Extension' => ['Applicant', 'Ministry'],
    ];

    /** @return list<string> */
    public static function initiatedByFor(?string $type): array
    {
        return self::INITIATED_BY_BY_TYPE[$type] ?? self::INITIATED_BY;
    }

    public function typeLabel(): string
    {
        return $this->title_type === 'Other' ? (string) $this->title_type_other : (string) $this->title_type;
    }
}
