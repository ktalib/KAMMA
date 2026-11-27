<?php

namespace App\Support\Bpa;

/**
 * The Building Plan Approval paper forms (KNUPDA forms; KNUPDA is now KAMMA; photos sent 2026-11-24) as print templates.
 * Each template reads an optional $d array: an empty key prints the paper's blank (dotted line / empty box), a filled
 * key prints the value in its place. Template only for now (user: "don't link them yet"): nothing in the workflow,
 * menu or tables feeds them; sample() is made-up data for a layout check.
 */
class BpaTemplates
{
    /** slug => [title, paper page numbers (written on the photos)] */
    public const LIST = [
        'checklist' => ['Check List of Application for Building Plan Approval', '1'],
        'application-form' => ['Building Plan Application Form', '2'],
        'fee-computation' => ['Fee Computation - Analysis Sheet', '3'],
        'structural-record' => ['Structural Drawing Record', '4-5'],
        'recommendation' => ['Recommendation for Building Plan Approval/Registration', '6'],
        'conveyance' => ['Conveyance of Building Plan Approval', '7-8'],
        'memo' => ['Planning Memo - Application for Statutory Right of Occupancy', 'Memo'], // photo sent separately, 2026-11-24
        'kamma-recommendation' => ['KAMMA Recommendation to the Commissioner - Statutory Right of Occupancy', 'Letter'], // photo, 2026-11-24
        'kamma-recommendation-letterhead' => ['KAMMA Recommendation to the Commissioner - for printed letterhead', 'Letter'], // no header
    ];

    // Application form: the use named in the form title, and the "State Type of Application" boxes (paper order, row by row).
    public const FORM_USES = ['RESIDENTIAL', 'COMMERCIAL', 'INDUSTRY'];

    public const APPLICATION_TYPES = ['REGISTRATION', 'LEVIES', 'FENCING', 'NEW', 'RENOVATION', 'ADD FLOOR', 'EXTRA COPY', 'RE-APPLICATION'];

    /** A) Drawing submitted, (a) Architectural: key => label (sets per sheet). */
    public const ARCH_SHEETS = [
        'site_plan' => 'Site Plan',
        'ground_floor_plans' => 'Ground Floor Plans',
        'first_floors_plans' => 'First Floors Plans',
        'section' => 'Section',
        'elevations' => 'Elevations',
        'details' => 'Details',
        'any_others' => 'Any Others',
    ];

    public const OTHER_DRAWINGS = ['Structural Drawings', 'Electrical Drawings', 'Sewage Disposal Drawings', 'Mechanical Drawings'];

    public const OWNERSHIP_DOCUMENTS = ['Statutory Right of Occupancy', 'Customary Right of Occupancy', 'Resettlement', 'Others'];

    // Fee computation analysis sheet.
    public const APPLICATION_VARIABLES = [
        'Proposed Building(s) (Vacant Site)',
        'Existing Building(s) (Developed Site)',
        'Existing Building(s) (Approval Contravened)',
        'Proposed Renovation (Approved Building (s)',
        'Proposed Renovation (Non-Approved Building(s)',
        'Proposed Extension to (Approved Building(s)',
        'Extra-Copies (Without Change of Name)',
        'Extra-Copies (With Change of Name)',
        'Perimeter Fencing Permission',
    ];

    public const USE_CATEGORIES = ['Residential', 'Commercial', 'Industrial', 'Educational', 'Agricultural', 'Religious', 'Mixed Use', 'Institutional'];

    public const LOCATION_VARIABLES = ['Metropolitan Area', 'Urban Centres', 'Zone'];

    // Recommendation, item 5: the drawings listed under (b).
    public const RECOMMENDATION_DRAWINGS = [
        'site_plan' => 'Site plan',
        'floor_plan' => 'Floor plan',
        'floor_plans' => 'Floor plans',
        'sections' => 'Sections',
        'elevations' => 'Elevations',
        'details' => 'Details',
        'structure_sanitary' => 'Structure/Sanitary',
    ];

    // Conveyance: the use banner above the letter title.
    public const CONVEYANCE_USES = ['RESIDENTIAL', 'COMMERCIAL', 'INDUSTRIAL', 'EDUCATIONAL', 'AGRICULTURAL', 'RELIGIOUS', 'MIXED USE', 'INSTITUTIONAL'];

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::LIST);
    }

    public static function title(string $slug): string
    {
        return self::LIST[$slug][0];
    }

    /**
     * A 9-character security code (8 digits + 1 letter, KLAES's SecurityCodeService format) split for print as on the
     * Land RofO: the letter over the first two digits, then the other six. Null when there is no code (blank template).
     */
    public static function securityCode(?string $code): ?array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        if (! preg_match('/^(?=(?:\D*\d){8}\D*$)[0-9]*[A-Z][0-9]*$/', $code)) {
            return ['alphabet' => '', 'digits_start' => '', 'digits_end' => $code];
        }
        $digits = preg_replace('/\D/', '', $code);

        return ['alphabet' => preg_replace('/\d/', '', $code), 'digits_start' => substr($digits, 0, 2), 'digits_end' => substr($digits, 2)];
    }

    /** Made-up values for a layout check (no real person or file). */
    public static function sample(string $slug): array
    {
        $owner = 'AMINU SANI DANLADI';
        $plot = 'PLOT 12';
        $bp = 'BP-2026-1';

        return match ($slug) {
            'application-form' => [
                'form_use' => 'RESIDENTIAL',
                'application_types' => ['NEW'],
                'bp_no' => $bp,
                'owner_name' => $owner,
                'owner_address' => 'NO. 5 ZOO ROAD, KANO',
                'phone' => '08031234567',
                'plot_description' => $plot.', NASSARAWA GRA, NASSARAWA LGA',
                'intended_use' => 'RESIDENTIAL (DUPLEX)',
                'approved_use' => 'RESIDENTIAL',
                'arch_sets' => ['site_plan' => 2, 'ground_floor_plans' => 2, 'first_floors_plans' => 2, 'section' => 2, 'elevations' => 2, 'details' => 1],
                'other_drawings' => ['Structural Drawings', 'Electrical Drawings'],
                'ownership_documents' => ['Statutory Right of Occupancy'],
                'other_documents' => 'SITE ANALYSIS REPORT, SOIL TEST',
                'applicant' => $owner,
                'applicant_date' => '24/11/2026',
                'processing_fee' => '₦3,750.00',
                'approval_fee' => '₦7,500.00',
                'consultancy_fee' => '₦3,750.00',
                'total_fee' => '₦15,000.00',
                'director_date' => '25/11/2026',
            ],
            'fee-computation' => [
                'application_variables' => ['Proposed Building(s) (Vacant Site)'],
                'use_categories' => ['Residential'],
                'location' => ['Metropolitan Area'],
            ],
            'structural-record' => [
                'owner_name' => $owner,
                'plot_no' => $plot,
                'floors' => '2 (GROUND + 1)',
                'q4a' => 'YES', 'q4b' => 'NO', 'q5a' => 'YES', 'q5b' => 'YES', 'q5c' => 'YES',
                'q6a' => '180', 'q6b' => '226', 'q6c' => '300', 'q6d' => '339', 'q6e' => 'YES', 'q6f' => '144 mm', 'q6g' => '125 mm',
                'q7a' => '225', 'q7b' => '262', 'q7c' => '375', 'q7d' => '393', 'q7e' => '180', 'q7f' => '196', 'q7g' => '225', 'q7h' => '262',
                'q8a' => '900', 'q8b' => '1,206',
                'q11a' => 'YES', 'q11b' => '192 mm', 'q11c' => '150 mm',
                'q12a' => 'YES',
                'comments' => 'STRUCTURAL DRAWINGS SATISFACTORY FOR A TWO-STOREY BUILDING.',
                'date' => '26/11/2026',
                'officer' => 'ENGR. MUSA IDRIS',
            ],
            'recommendation' => [
                'bp_number' => $bp,
                'ownership_in_order' => 'YES',
                'change_of_ownership' => 'N/A',
                'purpose_conforms' => 'YES, RESIDENTIAL',
                'fees_paid' => 'YES (RECEIPT R-0001)',
                'levy_paid' => 'YES',
                'site_charted' => 'YES',
                'site_found_to_be' => 'VACANT',
                'previous_approval' => 'NONE',
                'title_type' => 'STATUTORY RIGHT OF OCCUPANCY',
                'cofo_no' => 'KN 1234',
                'drawings' => ['site_plan' => '2 SETS', 'floor_plan' => '2 SETS', 'floor_plans' => '2 SETS', 'sections' => '2 SETS', 'elevations' => '2 SETS', 'details' => '1 SET', 'structure_sanitary' => '2 SETS'],
                'no_of_blocks' => '1',
                'nature_of_building' => 'DUPLEX',
                'no_of_floors' => '2',
                'others_in_order' => 'YES',
                'structural_vetting' => 'YES',
                'eia_comment' => 'NOT APPLICABLE (RESIDENTIAL)',
                'recommend' => 'RECOMMENDED',
                'zonal_officer' => 'HAUWA BELLO',
                'zonal_date' => '27/11/2026',
                'director' => 'ARC. YUSUF ABDULLAHI',
                'director_date' => '28/11/2026',
            ],
            'conveyance' => [
                'ref_no' => $bp.'/1',
                'date' => '1st December, 2026',
                'use' => 'RESIDENTIAL',
                'file_no' => $bp,
                'rofo_no' => 'KN 1234',
                'developer' => $owner,
                'plot_no' => $plot,
                'address' => 'NO. 5 ZOO ROAD, KANO',
                'location' => 'NASSARAWA GRA, NASSARAWA LGA',
                'signatory' => 'ARC. YUSUF ABDULLAHI',
                'security_code' => '58T204913',
            ],
            'memo' => [
                'file_no' => 'CON-RES-2026-101',
                'security_code' => '41Q827305',
                'date' => '3rd December, 2026',
                'application_type' => 'Statutory Right of Occupancy',
                'applicant' => $owner,
                'proposed_use' => 'Residential',
                'location' => 'Nassarawa GRA',
                'lga' => 'Nassarawa',
                'side_1' => '20', 'side_2' => '30', 'side_3' => '20', 'side_4' => '30', 'area' => '600',
                'layout' => 'The site falls on the UTM sheet H-19',
                'road_to_centre' => '4.00', 'road_corridor' => '8.0',
                'other_remarks' => 'You may wish to note that the site is accessible, meets the minimum size requirement of 100m² for residential plot and conforms with the existing residential developments in the area.',
                'recommendation' => 'In view of the above, I wish to recommend the application subject to payment of betterment fee.',
                'signatory' => 'HAUWA BELLO',
            ],
            'kamma-recommendation', 'kamma-recommendation-letterhead' => [
                'file_no' => 'CON-RES-2026-101',
                'date' => '5th December, 2026',
                'security_code' => '7306M2418',
                'application_type' => 'Statutory Right of Occupancy',
                'applicant' => $owner,
                'use' => 'residential',
                'reasons' => 'the site is accessible, adequate in size and conforms with existing land use in the area.',
                'site_plan_page' => '16',
                'sides' => ['20.00', '30.00', '20.00', '30.00'],
                'recommended_by' => 'TPL. HAUWA BELLO',
                'recommended_title' => 'Director Physical Planning',
                'endorsed_by' => 'TPL. YUSUF ABDULLAHI',
                'endorsed_title' => 'Managing Director',
            ],
            default => [],
        };
    }
}
