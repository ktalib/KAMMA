<?php

namespace App\Support\Portal;

use Illuminate\Support\Collection;

/**
 * The requests an applicant sees on the KAMMA portal, in one shape for every screen.
 *
 * UI first (user 2026-11-23): KAMMA has no request tables yet and applicants are not linked to anything, so
 * - the demo applicant sees sample requests (numbers start DEMO-, never a real file), one at each kind of step;
 * - an account made on Register sees none until the backend links its requests.
 * Staff names are never shown to applicants: a step names the desk, not the officer.
 */
class PortalApplications
{
    /** PLACEHOLDER: the processing fee is not set yet (config kamma.bills.processing_fee); demo bills use this. */
    public const DEMO_PROCESSING_FEE = 20000;

    /** @return Collection<int, array<string, mixed>> */
    public function forApplicant(?array $applicant): Collection
    {
        if (! $applicant || empty($applicant['is_demo'])) {
            return collect();
        }

        return collect($this->demo())->map(fn (array $a) => $this->finish($a))->values();
    }

    /** @return array<string, mixed>|null */
    public function find(string $reference, ?array $applicant): ?array
    {
        $reference = trim($reference);

        return $this->forApplicant($applicant)->first(fn ($a) => strcasecmp($a['ref'], $reference) === 0);
    }

    /** Every bill on every request, newest first. @return Collection<int, array<string, mixed>> */
    public function bills(?array $applicant): Collection
    {
        return $this->forApplicant($applicant)
            ->flatMap(fn ($a) => collect($a['bills'])->map(fn ($b) => $b + ['ref' => $a['ref'], 'type' => $a['type']]))
            ->sortByDesc(fn ($b) => $b['raised_on']->getTimestamp())
            ->values();
    }

    /** Updates on every request, newest first. @return Collection<int, array<string, mixed>> */
    public function notifications(?array $applicant): Collection
    {
        return $this->forApplicant($applicant)
            ->flatMap(fn ($a) => collect($a['events'])->map(fn ($e) => $e + ['ref' => $a['ref'], 'type' => $a['type']]))
            ->sortByDesc(fn ($e) => $e['at']->getTimestamp())
            ->values();
    }

    /** The processing fee per request: KAMMA's setting, else the demo placeholder. */
    public static function processingFee(): int
    {
        $fee = config('kamma.bills.processing_fee');

        return $fee !== null ? (int) $fee : self::DEMO_PROCESSING_FEE;
    }

    /** Adds what every screen derives: the timeline, the location line and the outstanding bills. */
    private function finish(array $a): array
    {
        $a['timeline'] = PortalTimeline::build($a);
        $a['location_line'] = collect([$a['plot_no'] ? 'Plot '.$a['plot_no'] : null, $a['district'], $a['lga']])->filter()->implode(', ');
        $a['outstanding'] = collect($a['bills'])->filter(fn ($b) => $b['status'] !== 'Paid')->values()->all();
        $a['status_label'] = match (true) {
            $a['timeline']['complete'] => 'Returned to the Ministry',
            $a['outstanding'] !== [] => 'Awaiting payment',
            $a['timeline']['returned'] !== null => 'Returned for correction',
            default => 'In progress',
        };

        return $a;
    }

    /**
     * Sample requests for the demo applicant. Dates are relative to today so the screens always look current.
     *
     * @return list<array<string, mixed>>
     */
    private function demo(): array
    {
        $d = fn (int $days, string $time = '10:00') => now()->startOfDay()->subDays($days)->setTimeFromTimeString($time);
        $fee = self::processingFee();
        $base = [
            'is_demo' => true,
            'applicant_name' => 'DEMO APPLICANT',
            'location' => null,
            'site_visit' => null,
            'ksi' => null,
            'merge_files' => [],
            'details' => [],
            'office_documents' => [],
        ];
        $docs = fn (int $days) => [
            ['name' => 'certificate-of-occupancy.pdf', 'kind' => 'Certificate of Occupancy / Letter of Grant', 'size' => 1_812_331, 'at' => $d($days)],
            ['name' => 'survey-plan.pdf', 'kind' => 'Title Deed Plan / Survey Plan', 'size' => 904_771, 'at' => $d($days)],
        ];
        $processing = fn (string $no, int $days, ?string $receipt = null, ?int $paidDays = null) => [
            'kind' => 'processing', 'bill_no' => $no, 'bill_type' => 'Application Processing Fee', 'amount' => (float) $fee,
            'status' => $receipt ? 'Paid' : 'Unpaid', 'receipt_no' => $receipt, 'paid_on' => $paidDays !== null ? $d($paidDays) : null,
            'raised_on' => $d($days), 'standard' => null, 'multiplier' => 1, 'penalty_reason' => null,
        ];
        $betterment = fn (string $no, int $days, float $standard, int $multiplier, ?string $receipt = null, ?int $paidDays = null) => [
            'kind' => 'betterment', 'bill_no' => $no, 'bill_type' => 'Betterment', 'amount' => $standard * $multiplier,
            'status' => $receipt ? 'Paid' : 'Unpaid', 'receipt_no' => $receipt, 'paid_on' => $paidDays !== null ? $d($paidDays) : null,
            'raised_on' => $d($days), 'standard' => $standard, 'multiplier' => $multiplier,
            'penalty_reason' => $multiplier > 1 ? 'The KSI found a building on the land without a Building Plan Approval, so the betterment fee is the standard fee × '.$multiplier.'.' : null,
        ];
        $received = fn (string $no, int $days) => ['title' => 'Request received', 'body' => "Request {$no} received.", 'at' => $d($days, '09:12'), 'action' => 'submitted'];
        $shadow = fn (int $days, string $billNo) => ['title' => 'Shadow File commissioned', 'body' => "KAMMA opened a Shadow File for your land file. Processing fee bill {$billNo} raised: ₦".number_format($fee, 2).'.', 'at' => $d($days, '11:05'), 'action' => 'shadow'];
        $paid = fn (string $billNo, string $receipt, int $days) => ['title' => 'Payment confirmed', 'body' => "Bill {$billNo} paid, receipt {$receipt}.", 'at' => $d($days, '13:40'), 'action' => 'paid'];
        $visited = fn (int $days, int $photos) => ['title' => 'Site visited', 'body' => "An officer visited the site and took {$photos} geocoded photos.", 'at' => $d($days, '10:30'), 'action' => 'site_visit'];
        $ksi = fn (int $days, int $area) => ['title' => 'KSI completed', 'body' => 'The Zonal Office inspected the site. Recommended area '.number_format($area).' m².', 'at' => $d($days, '15:00'), 'action' => 'ksi'];
        $bettermentRaised = fn (string $billNo, int $days, float $amount) => ['title' => 'Betterment bill raised', 'body' => "Bill {$billNo}: ₦".number_format($amount, 2).'.', 'at' => $d($days, '16:00'), 'action' => 'betterment'];

        return [
            [
                'ref' => 'DEMO-KPR/2026/00005', 'category' => PortalForm::CATEGORY_POST_COFO, 'type' => 'Sub-Division',
                'file_number' => 'RES-DEMO-0005', 'plot_no' => '21', 'district' => 'NASSARAWA GRA', 'lga' => 'NASSARAWA',
                'file_land_use' => 'RESIDENTIAL', 'proposal' => 'Sub-divide into 3 plots', 'details' => ['Proposed plots' => '3'],
                'building_exists' => false, 'has_bpa' => null,
                'submitted_at' => $d(1, '09:12'), 'shadow_at' => $d(1, '11:05'), 'stage' => 'fee',
                'bills' => [$processing('DEMO-KPF/2026/00005', 1)],
                'documents' => $docs(1),
                'events' => [$received('DEMO-KPR/2026/00005', 1), $shadow(1, 'DEMO-KPF/2026/00005')],
            ] + $base,
            [
                'ref' => 'DEMO-KPR/2026/00004', 'category' => PortalForm::CATEGORY_LAND, 'type' => 'Conversion',
                'file_number' => 'CON-RES-DEMO-0004', 'plot_no' => '8', 'district' => 'TARAUNI', 'lga' => 'TARAUNI',
                'file_land_use' => 'RESIDENTIAL', 'proposal' => 'Planning Recommendation on a Conversion application', 'details' => [],
                'building_exists' => false, 'has_bpa' => null,
                'submitted_at' => $d(18, '09:12'), 'shadow_at' => $d(18, '11:05'), 'stage' => 'ksi',
                'bills' => [$processing('DEMO-KPF/2026/00004', 18, 'RCPT-DEMO-4', 16)],
                'site_visit' => ['date' => $d(9, '10:30'), 'photos' => 8, 'lat' => 12.0136, 'lng' => 8.5368],
                'documents' => $docs(18),
                'events' => [$received('DEMO-KPR/2026/00004', 18), $shadow(18, 'DEMO-KPF/2026/00004'), $paid('DEMO-KPF/2026/00004', 'RCPT-DEMO-4', 16), $visited(9, 8)],
            ] + $base,
            [
                'ref' => 'DEMO-KPR/2026/00003', 'category' => PortalForm::CATEGORY_POST_COFO, 'type' => 'Change of Purpose',
                'file_number' => 'RES-DEMO-0003', 'plot_no' => '45', 'district' => 'GWALE', 'lga' => 'GWALE',
                'file_land_use' => 'RESIDENTIAL', 'proposal' => 'Residential to Commercial', 'details' => ['Current use' => 'Residential', 'Proposed use' => 'Commercial'],
                'building_exists' => true, 'has_bpa' => false,
                'submitted_at' => $d(40, '09:12'), 'shadow_at' => $d(40, '11:05'), 'stage' => 'betterment',
                'bills' => [
                    $processing('DEMO-KPF/2026/00003', 40, 'RCPT-DEMO-3', 38),
                    $betterment('DEMO-KBB/2026/00003', 3, 250000.0, (int) config('kamma.bills.no_bpa_penalty_multiplier', 4)),
                ],
                'site_visit' => ['date' => $d(30, '10:30'), 'photos' => 6, 'lat' => 12.0051, 'lng' => 8.5121],
                'ksi' => ['date' => $d(12, '15:00'), 'recommended_area' => 900, 'building_exists' => true, 'has_bpa' => false],
                'documents' => $docs(40),
                'events' => [
                    $received('DEMO-KPR/2026/00003', 40), $shadow(40, 'DEMO-KPF/2026/00003'), $paid('DEMO-KPF/2026/00003', 'RCPT-DEMO-3', 38),
                    $visited(30, 6), $ksi(12, 900),
                    $bettermentRaised('DEMO-KBB/2026/00003', 3, 250000.0 * (int) config('kamma.bills.no_bpa_penalty_multiplier', 4)),
                ],
            ] + $base,
            [
                'ref' => 'DEMO-KPR/2026/00002', 'category' => PortalForm::CATEGORY_POST_COFO, 'type' => 'Merger',
                'file_number' => 'COM-DEMO-0002', 'plot_no' => '3', 'district' => 'FAGGE', 'lga' => 'FAGGE',
                'file_land_use' => 'COMMERCIAL', 'proposal' => 'Merge with COM-DEMO-0012', 'details' => [],
                'merge_files' => ['COM-DEMO-0012'],
                'building_exists' => false, 'has_bpa' => null,
                'submitted_at' => $d(70, '09:12'), 'shadow_at' => $d(70, '11:05'), 'stage' => 'md',
                'bills' => [
                    $processing('DEMO-KPF/2026/00002', 70, 'RCPT-DEMO-2', 68),
                    $betterment('DEMO-KBB/2026/00002', 35, 180000.0, 1, 'RCPT-DEMO-2B', 33),
                ],
                'site_visit' => ['date' => $d(60, '10:30'), 'photos' => 5, 'lat' => 12.0201, 'lng' => 8.5302],
                'ksi' => ['date' => $d(40, '15:00'), 'recommended_area' => 1250, 'building_exists' => false, 'has_bpa' => null],
                'documents' => $docs(70),
                'events' => [
                    $received('DEMO-KPR/2026/00002', 70), $shadow(70, 'DEMO-KPF/2026/00002'), $paid('DEMO-KPF/2026/00002', 'RCPT-DEMO-2', 68),
                    $visited(60, 5), $ksi(40, 1250), $bettermentRaised('DEMO-KBB/2026/00002', 35, 180000.0), $paid('DEMO-KBB/2026/00002', 'RCPT-DEMO-2B', 33),
                    ['title' => 'Planning Recommendation prepared', 'body' => 'The Planning Recommendation / Memo went to the MD KAMMA.', 'at' => $d(20, '12:00'), 'action' => 'recommendation'],
                ],
            ] + $base,
            [
                'ref' => 'DEMO-KPR/2026/00001', 'category' => PortalForm::CATEGORY_POST_COFO, 'type' => 'Extension',
                'file_number' => 'RES-DEMO-0001', 'plot_no' => '12', 'district' => 'KANO MUNICIPAL', 'lga' => 'KANO MUNICIPAL',
                'file_land_use' => 'RESIDENTIAL', 'proposal' => 'Extend the plot by 300 m²', 'details' => ['Extension requested' => '300 m²'],
                'building_exists' => true, 'has_bpa' => true,
                'submitted_at' => $d(120, '09:12'), 'shadow_at' => $d(120, '11:05'), 'stage' => PortalTimeline::COMPLETE,
                'bills' => [
                    $processing('DEMO-KPF/2026/00001', 120, 'RCPT-DEMO-1', 118),
                    $betterment('DEMO-KBB/2026/00001', 80, 150000.0, 1, 'RCPT-DEMO-1B', 77),
                ],
                'site_visit' => ['date' => $d(110, '10:30'), 'photos' => 7, 'lat' => 11.9964, 'lng' => 8.5167],
                'ksi' => ['date' => $d(90, '15:00'), 'recommended_area' => 1100, 'building_exists' => true, 'has_bpa' => true],
                'documents' => $docs(120),
                'office_documents' => [['name' => 'Planning Recommendation', 'at' => $d(50)]],
                'events' => [
                    $received('DEMO-KPR/2026/00001', 120), $shadow(120, 'DEMO-KPF/2026/00001'), $paid('DEMO-KPF/2026/00001', 'RCPT-DEMO-1', 118),
                    $visited(110, 7), $ksi(90, 1100), $bettermentRaised('DEMO-KBB/2026/00001', 80, 150000.0), $paid('DEMO-KBB/2026/00001', 'RCPT-DEMO-1B', 77),
                    ['title' => 'Not recommended by the MD KAMMA', 'body' => 'Show the access road on the extension sketch.', 'at' => $d(65, '12:30'), 'action' => 'not_recommended'],
                    ['title' => 'Planning Recommendation prepared', 'body' => 'The corrected Planning Recommendation went back to the MD KAMMA.', 'at' => $d(58, '10:00'), 'action' => 'recommendation'],
                    ['title' => 'Approved by the MD KAMMA', 'body' => 'The Planning Recommendation was approved.', 'at' => $d(52, '15:20'), 'action' => 'approved'],
                    ['title' => 'File returned to the Ministry', 'body' => 'Your land file went back to the Ministry of Land for the next stage.', 'at' => $d(50, '14:00'), 'action' => 'returned'],
                ],
            ] + $base,
        ];
    }
}
