<?php

namespace App\Support;

/**
 * The application kinds KAMMA gives a Planning Recommendation on, from the KAMMA menu: Conversion Applications,
 * ST One Stop Shop (Sectional Titling), SLTR Applications, and Master KSI (Parcel/Title Management, post-CofO:
 * Sub-Division, Merger, Extension, Change of Purpose). KLAES's screens use green for Conversion, blue for ST and
 * lime for SLTR; the same accents carry over.
 */
class KammaKinds
{
    public const KINDS = [
        'conversion' => ['label' => 'Conversion Applications', 'short' => 'Conversion', 'accent' => 'emerald', 'prefix' => 'CON-', 'icon' => 'repeat'],
        'st' => ['label' => 'ST One Stop Shop', 'short' => 'Sectional Titling', 'accent' => 'blue', 'prefix' => 'ST-', 'icon' => 'building-2'],
        'sltr' => ['label' => 'SLTR Applications', 'short' => 'SLTR', 'accent' => 'lime', 'prefix' => 'SLTR-', 'icon' => 'map'],
        'master' => ['label' => 'Master KSI (Post CofO)', 'short' => 'Master KSI', 'accent' => 'violet', 'prefix' => '', 'icon' => 'clipboard-list'],
    ];

    /** Post-CofO applications inside the 10 km buffer (KAMMA Workflow v1.0 scope). */
    public const POST_COFO = ['Sub-Division', 'Merger', 'Extension', 'Change of Purpose'];

    /** @return array<string, string> */
    public static function get(string $kind): array
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        return self::KINDS[$kind] + ['key' => $kind];
    }

    /** @return list<string> the kinds with a Planning Recommendation / Memo screen (not Master KSI). */
    public static function prKinds(): array
    {
        return ['conversion', 'st', 'sltr'];
    }
}
