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

    // DMS: scanned pages of indexed (shadow) files. Same disk as the uploads until KAMMA's file server is set up.
    'dms_disk' => env('KAMMA_DMS_DISK', env('KAMMA_UPLOADS_DISK', 'local')),
    'dms_page_max_kb' => (int) env('KAMMA_DMS_PAGE_MAX_KB', 51200),

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
    | Applicant portal (/portal). UI first (user 2026-11-23: "first finish the UI and frontend ... next we can focus
    | on the backend"): applicant accounts are not stored yet, so the portal runs on a session-only sign-in and
    | every save answers "Saved (preview)".
    */
    'portal' => [
        'demo_enabled' => (bool) env('KAMMA_PORTAL_DEMO', env('APP_ENV') !== 'production'),
        'demo' => [
            'username' => 'demo.applicant',
            'password' => env('KAMMA_PORTAL_DEMO_PASSWORD', 'kamma-demo'),
            'name' => 'Demo Applicant',
            'phone' => '08000000000', // placeholder: never texted (KLAES rule)
        ],
    ],

];
