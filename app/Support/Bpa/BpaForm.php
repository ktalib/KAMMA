<?php

namespace App\Support\Bpa;

/**
 * Options on the KADCA "Building Plan Processing and Approval - Application Form" (DOC, page 1),
 * worded as on the paper form. "Other" reveals one required "specify" box.
 */
class BpaForm
{
    public const OTHER = 'Other';

    /** The two application types (user 2026-11-25). Registration = a building put up without approval first. */
    public const FRESH = 'Fresh Application';

    public const REGISTRATION = 'Registration';

    public const APPLICATION_TYPES = [self::FRESH, self::REGISTRATION];

    public const SUBMISSION_TYPES = ['New Submission', 'Re-submission', 'Revalidation', self::OTHER];

    public const DOCUMENTS = [
        'Letter of Allocation',
        'Title Deed Plan',
        'Title Regularization/Acknowledgement',
        'Site Assessment Report',
        'Customary C-of-O',
        'Deed of Assignment',
        'Certificate of Occupancy',
        'Soil/Geotechnical Test',
        'Letter of Intent',
        'Architect/Town Planner',
        self::OTHER,
    ];

    public const DRAWINGS = [
        'Site Plan',
        'Calculation Sheets',
        'Architectural',
        'Contoured Site Plan',
        'Structural',
        'EIAR',
        'Electrical',
        'Fire Service',
        'Mechanical',
        'Police Report',
        'Soft Copies',
    ];

    /** How the building plan reached KADCA (briefing 2026-11-22). */
    public const PLAN_RECEIVED_AS = [
        'Scanned booklet from the applicant',
        'Soft copy (PDF) from the applicant',
        'Scanned at the counter',
        'CAD drawing (DXF/DWG)',
    ];

    public const PLAN_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'tif', 'tiff'];

    public const CAD_EXTENSIONS = ['dxf', 'dwg'];
}
