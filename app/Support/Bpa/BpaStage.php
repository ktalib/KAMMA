<?php

namespace App\Support\Bpa;

/**
 * Where a Building Plan Approval file is (briefing 2026-11-22): application and bill, payment, BP file
 * commissioned, Director (Arch.) recommendation, Deputy Director (Arch.) recommendation, Zonal Office (the Head assigns
 * an officer), inspection (the officer files the report), the Head reviews the report, Final Conveyance, BPA approved.
 * Like KLAES Cadastral: a desk Recommends (the file moves on) or does Not recommend (a reason is
 * required and the file goes back one desk).
 */
class BpaStage
{
    public const ASSESSMENT = 'assessment';
    public const ARCHITECTURE = 'architecture';
    public const DIRECTOR = 'director_arch';
    public const DEPUTY_DIRECTOR = 'dd_arch';
    public const ZONAL_OFFICE = 'zonal_office';
    public const INSPECTION = 'inspection';
    public const ZONAL_REVIEW = 'zonal_review';
    public const CONVEYANCE = 'conveyance';
    public const APPROVED = 'approved';

    public const LABELS = [
        self::ASSESSMENT => 'Assessment Stage',
        self::ARCHITECTURE => 'Architecture',
        self::DIRECTOR => 'Director (Arch.)',
        self::DEPUTY_DIRECTOR => 'Deputy Director (Arch.)',
        self::ZONAL_OFFICE => 'Zonal Office',
        self::INSPECTION => 'Inspecting officer',
        self::ZONAL_REVIEW => 'Head of Zonal Office',
        self::CONVEYANCE => 'Final Conveyance',
        self::APPROVED => 'BPA approved',
    ];

    /**
     * The recommendation desks: URL slug => [stage, title, where Recommend sends the file, where Not recommended sends it].
     */
    public const DESKS = [
        'director' => [self::DIRECTOR, 'Director (Arch.)', self::DEPUTY_DIRECTOR, self::ARCHITECTURE],
        'deputy-director' => [self::DEPUTY_DIRECTOR, 'Deputy Director (Arch.)', self::ZONAL_OFFICE, self::DIRECTOR],
    ];

    public const RECOMMENDED = 'recommended';
    public const NOT_RECOMMENDED = 'not_recommended';

    /** The status line shown for a file at each stage. */
    public static function status(string $stage): string
    {
        return match ($stage) {
            self::DIRECTOR => 'With Director (Arch.)',
            self::DEPUTY_DIRECTOR => 'With Deputy Director (Arch.)',
            self::ZONAL_OFFICE => 'With Zonal Office',
            self::INSPECTION => 'With inspecting officer',
            self::ZONAL_REVIEW => 'Inspection report with Head of Zonal Office',
            self::CONVEYANCE => 'Awaiting Final Conveyance',
            self::APPROVED => 'BPA approved',
            self::ARCHITECTURE => 'Returned to Architecture',
            default => self::LABELS[$stage] ?? $stage,
        };
    }

    public static function label(?string $stage): string
    {
        return self::LABELS[$stage] ?? (string) $stage;
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} */
    public static function desk(string $slug): array
    {
        abort_unless(isset(self::DESKS[$slug]), 404);

        return self::DESKS[$slug];
    }
}
