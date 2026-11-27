<?php

namespace App\Support\Portal\Bpa;

use App\Support\Bpa\BpaStage;

/**
 * The applicant's view of where a Building Plan Approval application is: the nine steps of the KADCA column in the
 * briefing diagram, in applicant wording. Read from the same fields the staff side writes (bill status, BP file
 * number, BpaStage). Inspection and Conveyance have no staff screen yet, so only the demo applications reach them.
 */
class BpaTimeline
{
    // Stages after the Zonal Office. Not in BpaStage yet: the staff side stops at the Zonal Office (built last).
    public const INSPECTION = 'inspection';
    public const CONVEYANCE = 'conveyance';
    public const APPROVED = 'approved';

    /** step key => [title, what it means for the applicant while it is the current step] */
    public const STEPS = [
        'submitted' => ['Submitted', 'Your application has been received.'],
        'bill' => ['Application bill', 'The application bill is being raised.'],
        'paid' => ['Paid', 'Pay the application bill. Your application moves on once the payment is confirmed.'],
        'commissioned' => ['BP file commissioned', 'Your payment is confirmed. Your Building Plan (BP) file is being opened.'],
        'director' => ['Director (Arch.)', 'The Director, Architectural Department, is reviewing your building plan.'],
        'dd' => ['DD (Arch.)', 'The Deputy Director, Architectural Department, is reviewing your building plan.'],
        'zonal' => ['Zonal Office', 'Your file is with the Zonal Office, which assigns an officer to inspect the site. You can book the visit now.'],
        'inspection' => ['Inspection', 'The site inspection is being carried out and reported.'],
        'conveyance' => ['Conveyance', 'The Conveyance of Building Plan Approval is being prepared.'],
    ];

    /** How far along the desks each stage is (higher = further). */
    private const RANK = [
        BpaStage::ASSESSMENT => 0,
        BpaStage::ARCHITECTURE => 0,
        BpaStage::DIRECTOR => 4,
        BpaStage::DEPUTY_DIRECTOR => 5,
        BpaStage::ZONAL_OFFICE => 6,
        self::INSPECTION => 7,
        BpaStage::ZONAL_REVIEW => 7,
        self::CONVEYANCE => 8,
        self::APPROVED => 9,
    ];

    /**
     * @param  array<string, mixed>  $application  as BpaApplications presents it
     * @return array{steps: list<array{key: string, title: string, state: string, note: ?string}>, percent: int, current: ?string, current_hint: ?string, complete: bool, returned: ?string}
     */
    public static function build(array $application): array
    {
        $stage = (string) ($application['stage'] ?? BpaStage::ASSESSMENT);
        $rank = self::RANK[$stage] ?? 0;
        $bill = $application['bill'] ?? null;
        $commissioned = filled($application['bp_file_no'] ?? null);

        $done = [
            'submitted' => true,
            'bill' => $bill !== null,
            'paid' => $bill !== null && ($bill['status'] ?? '') === 'Paid',
            'commissioned' => $commissioned,
            'director' => $rank >= 5,
            'dd' => $rank >= 6,
            'zonal' => $rank >= 7,
            'inspection' => $rank >= 8,
            'conveyance' => $rank >= 9,
        ];

        // A desk that did not recommend sends the file back one desk with a reason (BpaStage::DESKS).
        $returned = null;
        $last = collect($application['events'] ?? [])->last();
        if ($last && ($last['action'] ?? null) === BpaStage::NOT_RECOMMENDED) {
            $returned = $last['body'] ?? 'Returned for correction.';
        }
        // Not recommended by the Director: the file is back in Architecture, still waiting for the Director.
        if ($stage === BpaStage::ARCHITECTURE && $commissioned) {
            $returned ??= 'Returned to Architecture for correction.';
        }

        $steps = [];
        $currentKey = null;
        foreach (self::STEPS as $key => [$title, $hint]) {
            $state = $done[$key] ? 'done' : 'todo';
            if (! $done[$key] && $currentKey === null) {
                $state = 'current';
                $currentKey = $key;
            }
            $steps[] = [
                'key' => $key,
                'title' => $title,
                'state' => $state,
                'note' => self::note($key, $state, $application, $returned),
            ];
        }

        $doneCount = count(array_filter($done));

        return [
            'steps' => $steps,
            'percent' => (int) round($doneCount / count(self::STEPS) * 100),
            'current' => $currentKey ? self::STEPS[$currentKey][0] : null,
            'current_hint' => $currentKey ? self::STEPS[$currentKey][1] : null,
            'complete' => $currentKey === null,
            'returned' => $currentKey !== null ? $returned : null,
        ];
    }

    /** The one-line detail under a step. */
    private static function note(string $key, string $state, array $a, ?string $returned): ?string
    {
        $bill = $a['bill'] ?? null;

        return match (true) {
            $key === 'submitted' => isset($a['submitted_at']) ? 'Received '.$a['submitted_at']->format('j M Y') : null,
            $key === 'bill' && $bill !== null => $bill['bill_no'].' · ₦'.number_format((float) $bill['amount'], 2),
            $key === 'paid' && $state === 'done' => trim('Receipt '.($bill['receipt_no'] ?? '').(isset($bill['paid_on']) ? ' · '.$bill['paid_on']->format('j M Y') : '')),
            $key === 'paid' && $state === 'current' => 'Awaiting your payment',
            $key === 'commissioned' && $state === 'done' => 'BP file '.$a['bp_file_no'],
            $state === 'current' && $returned !== null => 'Returned: '.$returned,
            $key === 'zonal' && $state === 'current' => 'Book your site inspection',
            $key === 'conveyance' && $state === 'done' => 'Approval conveyed',
            default => null,
        };
    }
}
