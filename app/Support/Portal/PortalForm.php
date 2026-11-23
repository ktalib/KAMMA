<?php

namespace App\Support\Portal;

use App\Support\KammaKinds;

/**
 * Options on the KAMMA applicant portal request form. The kinds are KAMMA's own (App\Support\KammaKinds): Planning
 * Recommendation on a land application (Conversion, Sectional Titling, SLTR) and the Post-CofO applications inside
 * the 10 km buffer (Sub-Division, Merger, Extension, Change of Purpose). "Other" reveals one required "specify" box.
 *
 * PLACEHOLDER until KAMMA sends its own checklist: the documents list.
 */
class PortalForm
{
    public const OTHER = 'Other';

    public const CATEGORY_LAND = 'land';

    public const CATEGORY_POST_COFO = 'post_cofo';

    public const CATEGORIES = [
        self::CATEGORY_LAND => 'Land application',
        self::CATEGORY_POST_COFO => 'Post-CofO application',
    ];

    public const DOCUMENTS = [
        'Certificate of Occupancy / Letter of Grant',
        'Title Deed Plan / Survey Plan',
        'Means of identification',
        'Deed of Assignment',
        'Building Plan Approval',
        'Proposed layout / sub-division plan',
        'Consent of the owners of the plots to merge',
        'Passport photograph',
        self::OTHER,
    ];

    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public const MAX_FILES = 10;

    /** @return array<string, string> request type => label, grouped by category */
    public static function types(): array
    {
        $land = [];
        foreach (KammaKinds::prKinds() as $kind) {
            $land[KammaKinds::KINDS[$kind]['short']] = KammaKinds::KINDS[$kind]['label'];
        }

        return [
            self::CATEGORY_LAND => $land,
            self::CATEGORY_POST_COFO => array_combine(KammaKinds::POST_COFO, KammaKinds::POST_COFO),
        ];
    }

    /** @return list<string> every request type */
    public static function allTypes(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::types())));
    }

    public static function categoryOf(string $type): ?string
    {
        foreach (self::types() as $category => $types) {
            if (array_key_exists($type, $types)) {
                return $category;
            }
        }

        return null;
    }
}
