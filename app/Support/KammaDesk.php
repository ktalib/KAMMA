<?php

namespace App\Support;

/**
 * KAMMA's desks, in the order a land file moves through them (KAMMA Workflow v1.0 + briefing 2026-11-22):
 * the PS receives the land file from the Cadastral Department (KLAES) and forwards it to the MD KAMMA, who routes
 * it to the department in charge; Director → Deputy Director → Zonal Officer, who assigns a Field Officer for the
 * KSI (KAMMA Site Inspection); then the Planning Recommendation, and the file goes back to the PS.
 * A tracking unit is linked to a desk by `stage`, so desk decisions can move the physical file too.
 */
class KammaDesk
{
    public const FLOW = [
        'ps' => 'PS (Permanent Secretary)',
        'md' => 'MD KAMMA',
        'department' => 'Department',
        'director' => 'Director',
        'deputy_director' => 'Deputy Director',
        'zonal_officer' => 'Zonal Officer',
        'field_officer' => 'Field Officer',
        'ksi' => 'KSI (KAMMA Site Inspection)',
        'planning_recommendation' => 'Planning Recommendation',
        'returned_to_ps' => 'Back to PS',
    ];

    /** Units that are not desks in the flow but still hold files. */
    public const OTHER = [
        'registry' => 'Registry',
        'accounts' => 'Accounts',
        'archive' => 'Archive',
    ];

    public static function label(?string $stage): string
    {
        if ($stage === null || $stage === '') {
            return '—';
        }

        return self::FLOW[$stage] ?? self::OTHER[$stage] ?? ucwords(str_replace('_', ' ', $stage));
    }

    /** @return list<string> */
    public static function stages(): array
    {
        return array_keys(self::FLOW);
    }
}
