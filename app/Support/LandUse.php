<?php

namespace App\Support;

/**
 * Land use of a KLAES land file (copied from KADCA, where it backs the Building Plan land-use check): a residential use must sit on a
 * residential (RES) land file; anything else is flagged, not blocked.
 *
 * The land file's use comes from the KLAES indexing record (`land_use_type`: RESIDENTIAL, COMMERCIAL, … with
 * a few misspellings) and, when that is blank or unclear, from the code in the file number (RES-2016-452,
 * CON-COM-2019-12, …).
 */
class LandUse
{
    /** Proposed building uses offered on the form => land-use code they need. "Other" is not checked. */
    public const PROPOSED = [
        'Residential' => 'RES',
        'Commercial' => 'COM',
        'Industrial' => 'IND',
        'Agricultural' => 'AGR',
        'Institutional / Government' => 'GOV',
    ];

    public const OTHER = 'Other';

    public const LABELS = [
        'RES' => 'Residential',
        'COM' => 'Commercial',
        'IND' => 'Industrial',
        'AGR' => 'Agricultural',
        'GOV' => 'Government / Institutional',
    ];

    public const MATCH = 'Match';
    public const MISMATCH = 'Mismatch';
    public const NOT_CHECKED = 'Not checked';

    /** The land file's land-use code (RES, COM, IND, AGR, GOV), or null when neither source says. */
    public static function code(?string $landUseType, ?string $fileNumber = null): ?string
    {
        $fromType = self::fromWord($landUseType);
        if ($fromType !== null) {
            return $fromType;
        }

        // File number segments, e.g. RES-2016-452, CON-RES-2008-169, CON-AG-1981-28.
        foreach (preg_split('/[^A-Z]+/', strtoupper((string) $fileNumber), -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $code = self::fromWord($part, true);
            if ($code !== null) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string} [Match|Mismatch|Not checked, note shown to staff]
     */
    public static function check(string $proposedUse, ?string $fileCode): array
    {
        $needed = self::PROPOSED[$proposedUse] ?? null;

        if ($needed === null) {
            return [self::NOT_CHECKED, 'The proposed use is "Other", so it was not compared with the land file.'];
        }
        if ($fileCode === null) {
            return [self::NOT_CHECKED, 'The land file has no recorded land use to compare with.'];
        }
        if ($needed === $fileCode) {
            return [self::MATCH, "{$proposedUse} building on a ".self::LABELS[$fileCode].' land file.'];
        }

        return [self::MISMATCH, "{$proposedUse} building proposed on a ".self::LABELS[$fileCode].' land file.'];
    }

    private static function fromWord(?string $word, bool $codesOnly = false): ?string
    {
        $w = strtoupper(trim((string) $word));
        if ($w === '') {
            return null;
        }

        $codes = ['RES' => 'RES', 'COM' => 'COM', 'IND' => 'IND', 'AG' => 'AGR', 'AGR' => 'AGR', 'GOV' => 'GOV'];
        if (isset($codes[$w])) {
            return $codes[$w];
        }
        if ($codesOnly) {
            return null;
        }

        return match (true) {
            str_starts_with($w, 'RES') => 'RES', // RESIDENTIAL, RESIDENCIAL, RESDENTIAL
            str_starts_with($w, 'COM') => 'COM',
            str_starts_with($w, 'IND') => 'IND',
            str_starts_with($w, 'AGR') => 'AGR',
            str_starts_with($w, 'GOV') || str_starts_with($w, 'INST') => 'GOV',
            default => null,
        };
    }
}
