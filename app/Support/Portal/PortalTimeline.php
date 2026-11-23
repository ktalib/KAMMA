<?php

namespace App\Support\Portal;

/**
 * The applicant's view of where a KAMMA Planning Recommendation request is (KAMMA Workflow v1.0 phases 1-4 and the
 * 2026-11-22 briefing, in applicant wording): Shadow File commissioning, processing fee, site visit, zonal KSI
 * (KAMMA Site Inspection, not JSI), betterment, Planning Recommendation / Memo, MD KAMMA, file returned to the Ministry.
 *
 * The staff side has no request table yet, so the stage is the key of the step the request is on ('complete' when the
 * file has gone back to the Ministry). Bills decide whether the two payment steps are done.
 */
class PortalTimeline
{
    public const COMPLETE = 'complete';

    /** step key => [title, what it means for the applicant while it is the current step] */
    public const STEPS = [
        'received' => ['Request received', 'Your request has been received. KAMMA checks it against your land file.'],
        'shadow' => ['Shadow File commissioned', 'KAMMA is opening a Shadow File: its own copy of your land file. Your land file itself is never changed.'],
        'fee' => ['Processing fee', 'Pay the application processing fee. Your request moves on once KAMMA confirms the payment.'],
        'site_visit' => ['Site visit', 'An officer visits the site and takes photos stamped with the location and time.'],
        'ksi' => ['Zonal KSI', 'The Zonal Office carries out the KAMMA Site Inspection (KSI) and measures the site.'],
        'betterment' => ['Betterment bill', 'Pay the betterment bill raised from the KSI. The Planning Recommendation is prepared once it is paid.'],
        'recommendation' => ['Planning Recommendation', 'KAMMA is preparing the Planning Recommendation / Memo on your request.'],
        'md' => ['MD KAMMA', 'The Managing Director of KAMMA is reviewing the Planning Recommendation.'],
        'returned' => ['Returned to the Ministry', 'Your land file is going back to the Ministry of Land for the next stage.'],
    ];

    /**
     * @param  array<string, mixed>  $application  as App\Support\Portal\PortalApplications presents it
     * @return array{steps: list<array{key: string, title: string, state: string, note: ?string}>, percent: int, current: ?string, current_hint: ?string, complete: bool, returned: ?string}
     */
    public static function build(array $application): array
    {
        $keys = array_keys(self::STEPS);
        $stage = (string) ($application['stage'] ?? 'received');
        $position = $stage === self::COMPLETE ? count($keys) : (int) array_search($stage, $keys, true);

        $bills = collect($application['bills'] ?? []);
        $fee = $bills->firstWhere('kind', 'processing');
        $betterment = $bills->firstWhere('kind', 'betterment');

        $done = [];
        foreach ($keys as $i => $key) {
            $done[$key] = $i < $position;
        }
        // A payment step is done only when its bill is paid.
        $done['fee'] = $done['fee'] && ($fee['status'] ?? null) === 'Paid';
        $done['betterment'] = $done['betterment'] && ($betterment['status'] ?? null) === 'Paid';

        // A desk that did not recommend sends the file back with a reason.
        $returned = null;
        $last = collect($application['events'] ?? [])->last();
        if ($last && ($last['action'] ?? null) === 'not_recommended') {
            $returned = $last['body'] ?? 'Returned for correction.';
        }

        $steps = [];
        $currentKey = null;
        foreach (self::STEPS as $key => [$title]) {
            $state = $done[$key] ? 'done' : 'todo';
            if (! $done[$key] && $currentKey === null) {
                $state = 'current';
                $currentKey = $key;
            }
            $steps[] = ['key' => $key, 'title' => $title, 'state' => $state, 'note' => self::note($key, $state, $application, $fee, $betterment, $returned)];
        }

        return [
            'steps' => $steps,
            'percent' => (int) round(count(array_filter($done)) / count(self::STEPS) * 100),
            'current' => $currentKey ? self::STEPS[$currentKey][0] : null,
            'current_hint' => $currentKey ? self::STEPS[$currentKey][1] : null,
            'complete' => $currentKey === null,
            'returned' => $currentKey !== null ? $returned : null,
        ];
    }

    /** The one-line detail under a step. */
    private static function note(string $key, string $state, array $a, ?array $fee, ?array $betterment, ?string $returned): ?string
    {
        $money = fn (array $b) => '₦'.number_format((float) $b['amount'], 2);

        return match (true) {
            $key === 'received' => isset($a['submitted_at']) ? 'Received '.$a['submitted_at']->format('j M Y') : null,
            $key === 'shadow' && $state === 'done' && isset($a['shadow_at']) => 'Opened '.$a['shadow_at']->format('j M Y'),
            $key === 'fee' && $fee !== null && $state === 'done' => trim($fee['bill_no'].' · paid'.($fee['receipt_no'] ? ', receipt '.$fee['receipt_no'] : '')),
            $key === 'fee' && $fee !== null && $state === 'current' => 'Awaiting your payment: '.$fee['bill_no'].' · '.$money($fee),
            $key === 'site_visit' && $state === 'done' && ! empty($a['site_visit']) => $a['site_visit']['photos'].' geocoded photos · '.$a['site_visit']['date']->format('j M Y'),
            $key === 'ksi' && $state === 'done' && ! empty($a['ksi']) => 'Recommended area '.number_format((float) $a['ksi']['recommended_area']).' m²',
            $key === 'betterment' && $betterment !== null && $state === 'current' => 'Awaiting your payment: '.$betterment['bill_no'].' · '.$money($betterment).(($betterment['multiplier'] ?? 1) > 1 ? ' (×'.$betterment['multiplier'].' penalty)' : ''),
            $key === 'betterment' && $betterment !== null && $state === 'done' => $betterment['bill_no'].' · paid',
            $state === 'current' && $returned !== null => 'Returned: '.$returned,
            $key === 'returned' && $state === 'done' => 'Planning Recommendation issued',
            default => null,
        };
    }
}
