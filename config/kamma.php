<?php

return [

    /*
    | Request purposes for Log a File and Quick Search, in KLAES's request_purposes shape (name => turnaround
    | days). The purpose sets the timeline and expected return date and the Green / Amber / Red status.
    | The KAMMA desks (PS → MD KAMMA → Department → Director → DD → Zonal Officer → Field Officer) drive the list.
    */
    'request_purposes' => [
        'FOR MD KAMMA' => 3,
        'FOR RECOMMENDATION' => 5,
        'FOR APPROVAL' => 5,
        'FOR KSI (SITE INSPECTION)' => 7,
        'FOR ASSESSMENT' => 5,
        'FOR BILLING' => 3,
        'FOR PLANNING RECOMMENDATION' => 5,
        'FOR MEMO' => 3,
        'FOR RETURN TO PS' => 2,
        'FOR REFERENCE' => 3,
        'FOR FILING' => 2,
    ],

    // KAMMA's own uploads (geocoded photos, KSI attachments). Local for now; the briefing puts the agencies'
    // files on a separate file server later, while land files stay on the main KLAES server.
    'uploads_disk' => env('KAMMA_UPLOADS_DISK', 'local'),
    'upload_max_kb' => (int) env('KAMMA_UPLOAD_MAX_KB', 102400),

    // DMS: scanned pages of indexed (shadow) files, in the EDMS stage folders (disk "edms", user 2026-11-25):
    // EDMS/BLIND_SCAN/KAMMA_Raw/<file no.>, EDMS/SCAN_UPLOAD/KAMMA/<file no.>/<A4|A3>, ... like KLAES's Lands_Registry.
    'dms_disk' => env('KAMMA_DMS_DISK', 'edms'),
    'dms_page_max_kb' => (int) env('KAMMA_DMS_PAGE_MAX_KB', 51200),
    'edms' => [
        'registry' => 'KAMMA',       // the registry folder under each stage folder
        'raw' => 'KAMMA_Raw',        // the registry folder under BLIND_SCAN
        'extensions' => ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'bmp', 'gif', 'webp', 'pdf'], // KLAES ALLOWED_EXTENSIONS
        'summary_days' => 30,        // Blind Scanning Summary: uploads from the last 30 days
    ],

    /*
    | Page types KAMMA adds to KLAES's own list (dbo.PageType / PageSubType) for Page Typing: the documents a KAMMA
    | shadow file collects (KAMMA Workflow v1.0, phases 2-4). Merged into KLAES's list by name, so 'Report' and
    | 'Image' gain these subtypes rather than appearing twice.
    */
    'dms_page_types' => [
        'Report' => ['KSI (KAMMA Site Inspection) Report', 'Site Assessment Report', 'Initial Site Visit Report'],
        'Planning' => ['Planning Recommendation', 'Planning Advice', 'KAMMA Memo', 'Physical Planning Memo'],
        'Bill / Receipt' => ['Application Processing Fee Bill', 'Betterment Bill', 'Payment Receipt', 'Evidence of Payment'],
        'Correspondence' => ['Minute from PS', 'Minute from MD KAMMA', 'Letter to Applicant', 'Return Letter to Ministry'],
        'Image' => ['Geocoded Site Photograph'],
    ],

    /*
    | Bills. PENDING (kamma-workflow open questions): the processing fee amount and which betterment rate table
    | KAMMA uses. Until KAMMA confirms, the screens show KLAES Physical Planning's betterment formula
    | (pp_betterment_rates: base fee + max(0, area − base area) × rate per extra m², on the KSI's RECOMMENDED area)
    | and the 4× no-BPA penalty from the KAMMA Workflow v1.0:
    |   IF Site_Assessment_Report = Building_Exists AND Land_File_Status != BPA_Approved THEN fee = standard × 4
    */
    'bills' => [
        'processing_fee' => env('KAMMA_PROCESSING_FEE') !== null ? (int) env('KAMMA_PROCESSING_FEE') : null, // null = not set yet (ask)
        'no_bpa_penalty_multiplier' => 4,
    ],

    /*
    | Applicant portal (/portal). Since 2026-11-25 applicant accounts and Building Plan applications are saved
    | (kamma_portal_applicants, kamma_bpa_applications). Planning Recommendation requests, site inspection
    | booking and construction progress still answer "Saved (preview)".
    */
    'portal' => [
        'demo_enabled' => (bool) env('KAMMA_PORTAL_DEMO', env('APP_ENV') !== 'production'),
        // Registration codes by SMS: on in production, off elsewhere (logged and shown on screen). User 2026-11-25.
        'sms_codes' => (bool) env('KAMMA_PORTAL_SMS_CODES', env('APP_ENV') === 'production'),
        'sms_sender' => env('KAMMA_PORTAL_SMS_SENDER', 'KLAES-METRO'),
        'demo' => [
            'username' => 'demo.applicant',
            'password' => env('KAMMA_PORTAL_DEMO_PASSWORD', 'kamma-demo'),
            'name' => 'Demo Applicant',
            'phone' => '08000000000', // placeholder: never texted (KLAES rule)
        ],

        // Building Plan Approval on the portal (2026-11-25, recording: the building plan portal "is coming under KAMMA,
        // not KADCA"). Copied from the KADCA app's config('kadca.*'); keep the two in step until BPA moves here.
        'bpa' => [
            'fee_per_plot' => (int) env('KAMMA_BPA_FEE_PER_PLOT', 15000),
            'registration_multiplier' => 4,      // Registration (built without approval) pays 4 × the fee
            'upload_max_kb' => 102400,
            // PLACEHOLDER until the agency sends the real list: the stages an applicant reports while building.
            'construction_stages' => ['Setting out', 'Foundation', 'Damp-proof course (DPC)', 'Lintel level', 'Roofing', 'Finishing', 'Completed'],
            'progress_max_photos' => 10,
            'progress_photo_max_kb' => 10240,
            // PLACEHOLDER until the agency confirms: when site inspections can be booked.
            'inspection' => [
                'slots' => ['09:00', '11:00', '14:00'],
                'weekdays' => [1, 2, 3, 4, 5], // ISO day numbers, Monday = 1
                'lead_days' => 2,
                'horizon_days' => 30,
            ],
        ],
    ],

    /*
    | Building Plan Approval staff screens (Development Permit), moved in from the KADCA app 2026-11-25 (user, option A:
    | "it's coming under KAMMA"). Copied from config('kadca.*').
    */
    'bpa' => [
        // The application form says "₦15,000 per plot, non-refundable".
        'application_fee_per_plot' => (int) env('KAMMA_BPA_FEE_PER_PLOT', 15000),
        // Registration (a building put up without approval) pays this many times the normal fee (briefing: 4×).
        'registration_multiplier' => 4,
        'uploads_disk' => env('KAMMA_BPA_UPLOADS_DISK', 'local'),
        'upload_max_kb' => (int) env('KAMMA_BPA_UPLOAD_MAX_KB', 102400),
        // Letterhead of the BPA print templates (KNUPDA = KAMMA's old name).
        'letterhead' => [
            'name' => 'Kano State Metropolitan Area Management Agency',
            'short' => 'KAMMA',
            'seal' => 'images/kamma-seal.png',
            'logo_right' => 'images/klaes-metro-logo.png',
            'address' => '2, Durbin Katsina Road, Bompai, P.M.B. 3046, Kano - Nigeria',
            'tel' => env('KAMMA_LETTERHEAD_TEL', '(064) 630338'),
            'phones' => env('KAMMA_LETTERHEAD_PHONES', '08035892929 | 08034073223 | 08095189684'),
            'email' => env('KAMMA_LETTERHEAD_EMAIL'),
        ],
    ],

];
