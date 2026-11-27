<?php

namespace App\Support\Portal\Bpa;

use App\Models\Bpa\Application;
use App\Support\Bpa\BpaStage;
use App\Support\LandUse;
use App\Support\Portal\Bpa\BpaTimeline as PortalTimeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The applications an applicant sees on the portal, in one shape for every screen.
 *
 * - a real account (since 2026-11-25) sees the applications it sent: kamma_bpa_applications.portal_applicant_id, read
 *   from the rows the staff side moves along (bill, BP file, desk movements, conveyance);
 * - the demo applicant sees sample applications (numbers start DEMO-, never a real file), one at each step.
 * An applicant sees only their own.
 * Staff names are never shown to applicants: a step names the desk, not the officer.
 */
class BpaApplications
{
    /** @return Collection<int, array<string, mixed>> */
    public function forApplicant(?array $applicant): Collection
    {
        if (! $applicant) {
            return collect();
        }
        $rows = empty($applicant['is_demo']) ? $this->real((int) ($applicant['id'] ?? 0)) : $this->demo();

        return collect($rows)->map(fn (array $a) => $this->finish($a))->values();
    }

    /**
     * The account's own applications, newest first, in the demo's shape.
     *
     * @return list<array<string, mixed>>
     */
    private function real(int $applicantId): array
    {
        if ($applicantId < 1) {
            return [];
        }

        return Application::query()
            ->where('portal_applicant_id', $applicantId)
            ->with(['bill', 'files', 'conveyance', 'movements' => fn ($q) => $q->orderBy('acted_at')->orderBy('id')])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Application $a) => $this->present($a))
            ->all();
    }

    /** @return array<string, mixed> */
    private function present(Application $a): array
    {
        $bill = $a->bill;
        $billRow = $bill ? [
            'bill_no' => $bill->bill_no,
            'bill_type' => $bill->bill_type,
            'amount' => (float) $bill->amount,
            'status' => $bill->status === 'Paid' ? 'Paid' : 'Unpaid',
            'receipt_no' => $bill->official_receipt_no ?: $bill->receipt_no,
            'paid_on' => $bill->paid_on,
            'raised_on' => $bill->created_at,
        ] : null;

        $events = [[
            'title' => 'Application received',
            'body' => "Application {$a->application_no} received.".($billRow ? " Bill {$billRow['bill_no']} raised: ₦".number_format($billRow['amount'], 2).'.' : ''),
            'at' => $a->created_at, 'action' => 'submitted',
        ]];
        if ($billRow && $billRow['status'] === 'Paid') {
            $events[] = ['title' => 'Payment confirmed', 'body' => "Bill {$billRow['bill_no']} paid".($billRow['receipt_no'] ? ", receipt {$billRow['receipt_no']}" : '').'.',
                'at' => $bill->payment_recorded_at ?? $bill->paid_on ?? $bill->updated_at, 'action' => 'paid'];
        }
        // Desk names only, never the officer (staff names are not shown to applicants).
        foreach ($a->movements as $m) {
            $from = BpaStage::label($m->from_stage);
            $events[] = match ($m->action) {
                'commissioned', 'paid' => ['title' => 'BP file commissioned', 'body' => "Your Building Plan file number is {$a->bp_file_no}. The file is with the Director (Arch.).", 'at' => $m->acted_at, 'action' => 'commissioned'],
                BpaStage::RECOMMENDED => ['title' => "Recommended by the {$from}", 'body' => 'Your file has moved on to the next desk.', 'at' => $m->acted_at, 'action' => BpaStage::RECOMMENDED],
                BpaStage::NOT_RECOMMENDED => ['title' => "Not recommended by the {$from}", 'body' => $m->remarks ?: 'Returned for correction.', 'at' => $m->acted_at, 'action' => BpaStage::NOT_RECOMMENDED],
                default => ['title' => BpaStage::status((string) $m->to_stage), 'body' => 'Your file has moved on.', 'at' => $m->acted_at, 'action' => (string) $m->action],
            };
        }
        if ($a->bpa_approved_at) {
            $events[] = ['title' => 'Building plan approved', 'body' => 'The Conveyance of Building Plan Approval is ready for you.', 'at' => $a->bpa_approved_at, 'action' => 'approved'];
        }

        return [
            'is_demo' => false,
            'ref' => $a->application_no,
            'application_no' => $a->application_no,
            'bp_file_no' => $a->bp_file_no,
            'file_number' => $a->file_number,
            'file_title' => $a->file_title,
            'developer_name' => $a->developer_name,
            'plot_no' => $a->plot_no,
            'district' => $a->district,
            'lga' => $a->lga,
            'location' => $a->location,
            'plot_size' => $a->plot_size,
            'file_land_use' => $a->file_land_use,
            'proposed_use' => $a->proposed_use === LandUse::OTHER ? ($a->proposed_use_other ?: LandUse::OTHER) : $a->proposed_use,
            'land_use_check' => $a->land_use_check,
            'land_use_note' => $a->land_use_note,
            'submission_type' => $a->submission_type === 'Other' ? ($a->submission_type_other ?: 'Other') : $a->submission_type,
            'documents_submitted' => (array) ($a->documents_submitted ?? []),
            'drawings_submitted' => (array) ($a->drawings_submitted ?? []),
            'number_of_plots' => (int) $a->number_of_plots,
            'submitted_at' => $a->created_at,
            'stage' => (string) $a->stage,
            'bill' => $billRow,
            'documents' => $a->files->map(fn ($f) => [
                'name' => $f->original_name, 'kind' => $f->kind === 'cad' ? 'CAD drawing' : 'Building plan', 'size' => (int) $f->size_bytes, 'at' => $f->created_at,
            ])->all(),
            'office_documents' => $a->conveyance ? [['name' => 'Conveyance of Building Plan Approval', 'at' => $a->conveyance->created_at]] : [],
            'inspection' => null,
            'progress' => [],
            'events' => $events,
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(string $reference, ?array $applicant): ?array
    {
        $reference = trim($reference);
        $own = $this->forApplicant($applicant)->first(fn ($a) => strcasecmp($a['ref'], $reference) === 0
            || ($a['bp_file_no'] && strcasecmp($a['bp_file_no'], $reference) === 0));
        if ($own) {
            return $own;
        }

        return null;
    }

    /** Updates on every application, newest first. @return Collection<int, array<string, mixed>> */
    public function notifications(?array $applicant): Collection
    {
        return $this->forApplicant($applicant)
            ->flatMap(fn ($a) => collect($a['events'])->map(fn ($e) => $e + ['ref' => $a['ref'], 'bp_file_no' => $a['bp_file_no']]))
            ->sortByDesc(fn ($e) => $e['at']->getTimestamp())
            ->values();
    }

    /** Applications the applicant may report construction progress on: approved ones (building before approval is penalised). */
    public function forProgress(?array $applicant): Collection
    {
        return $this->forApplicant($applicant)->filter(fn ($a) => $a['can_report_progress'])->values();
    }

    /** Applications whose site inspection can be booked: at the Zonal Office or Inspection step. */
    public function forInspection(?array $applicant): Collection
    {
        return $this->forApplicant($applicant)->filter(fn ($a) => $a['can_book_inspection'])->values();
    }

    /** Adds what every screen derives: the timeline, the location line and what the applicant may do next. */
    private function finish(array $a): array
    {
        $a['timeline'] = PortalTimeline::build($a);
        $a['location_line'] = collect([$a['plot_no'] ? 'Plot '.$a['plot_no'] : null, $a['district'], $a['lga']])->filter()->implode(', ');
        $a['bill_outstanding'] = $a['bill'] && $a['bill']['status'] !== 'Paid';
        $a['can_book_inspection'] = in_array($a['stage'], [BpaStage::ZONAL_OFFICE, PortalTimeline::INSPECTION], true);
        $a['can_report_progress'] = $a['stage'] === PortalTimeline::APPROVED;
        $a['status_label'] = match (true) {
            $a['timeline']['complete'] => 'Approved',
            $a['bill_outstanding'] => 'Awaiting payment',
            $a['timeline']['returned'] !== null => 'Returned for correction',
            default => 'In progress',
        };

        return $a;
    }

    /**
     * Sample applications for the demo applicant: one awaiting payment, one with the Deputy Director, one at
     * inspection, one approved and being built. Dates are relative to today so the screens always look current.
     *
     * @return list<array<string, mixed>>
     */
    private function demo(): array
    {
        $d = fn (int $days, string $time = '10:00') => now()->startOfDay()->subDays($days)->setTimeFromTimeString($time);
        $base = [
            'is_demo' => true,
            'file_title' => 'DEMO APPLICANT',
            'developer_name' => 'DEMO APPLICANT',
            'location' => null,
            'plot_size' => null,
            'submission_type' => 'New Submission',
            'documents_submitted' => ['Certificate of Occupancy', 'Title Deed Plan', 'Architect/Town Planner'],
            'drawings_submitted' => ['Site Plan', 'Architectural', 'Structural', 'Electrical'],
            'number_of_plots' => 1,
            'office_documents' => [],
            'inspection' => null,
            'progress' => [],
        ];
        $plan = fn (int $days) => [
            ['name' => 'building-plan-booklet.pdf', 'kind' => 'Building plan', 'size' => 4_812_331, 'at' => $d($days)],
            ['name' => 'ground-floor.dwg', 'kind' => 'CAD drawing', 'size' => 1_204_771, 'at' => $d($days)],
        ];
        $bill = fn (string $no, int $days, ?string $receipt = null, ?int $paidDays = null) => [
            'bill_no' => $no, 'bill_type' => 'BPA Application Fee', 'amount' => 15000.0,
            'status' => $receipt ? 'Paid' : 'Unpaid', 'receipt_no' => $receipt,
            'paid_on' => $paidDays !== null ? $d($paidDays) : null, 'raised_on' => $d($days),
        ];
        $received = fn (string $no, string $billNo, int $days) => ['title' => 'Application received', 'body' => "Application {$no} received. Bill {$billNo} raised: ₦15,000.00.", 'at' => $d($days, '09:12'), 'action' => 'submitted'];
        $paid = fn (string $billNo, string $receipt, int $days) => ['title' => 'Payment confirmed', 'body' => "Bill {$billNo} paid, receipt {$receipt}.", 'at' => $d($days, '13:40'), 'action' => 'paid'];
        $commissioned = fn (string $bp, int $days) => ['title' => 'BP file commissioned', 'body' => "Your Building Plan file number is {$bp}. The file is with the Director (Arch.).", 'at' => $d($days, '11:05'), 'action' => 'commissioned'];
        $recommended = fn (string $desk, int $days) => ['title' => 'Recommended by the '.$desk, 'body' => 'Your file has moved on to the next desk.', 'at' => $d($days, '15:20'), 'action' => BpaStage::RECOMMENDED];

        return [
            [
                'ref' => 'DEMO-BPA/2026/00004', 'application_no' => 'DEMO-BPA/2026/00004', 'bp_file_no' => null,
                'file_number' => 'COM-DEMO-0004', 'plot_no' => '12', 'district' => 'NASSARAWA GRA', 'lga' => 'NASSARAWA',
                'file_land_use' => 'RESIDENTIAL', 'proposed_use' => 'Commercial', 'land_use_check' => LandUse::MISMATCH,
                'land_use_note' => 'Commercial building proposed on a Residential land file. Flagged for review.',
                'submitted_at' => $d(1, '09:12'), 'stage' => BpaStage::ASSESSMENT,
                'bill' => $bill('DEMO-BILL/2026/00004', 1),
                'documents' => $plan(1),
                'events' => [$received('DEMO-BPA/2026/00004', 'DEMO-BILL/2026/00004', 1)],
            ] + $base,
            [
                'ref' => 'DEMO-BPA/2026/00003', 'application_no' => 'DEMO-BPA/2026/00003', 'bp_file_no' => 'BP-2026-DEMO3',
                'file_number' => 'RES-DEMO-0003', 'plot_no' => '45', 'district' => 'TARAUNI', 'lga' => 'TARAUNI',
                'file_land_use' => 'RESIDENTIAL', 'proposed_use' => 'Residential', 'land_use_check' => LandUse::MATCH, 'land_use_note' => null,
                'submitted_at' => $d(16, '09:12'), 'stage' => BpaStage::DEPUTY_DIRECTOR,
                'bill' => $bill('DEMO-BILL/2026/00003', 16, 'RCPT-DEMO-3', 14),
                'documents' => $plan(16),
                'events' => [
                    $received('DEMO-BPA/2026/00003', 'DEMO-BILL/2026/00003', 16),
                    $paid('DEMO-BILL/2026/00003', 'RCPT-DEMO-3', 14),
                    $commissioned('BP-2026-DEMO3', 13),
                    $recommended('Director (Arch.)', 6),
                ],
            ] + $base,
            [
                'ref' => 'DEMO-BPA/2026/00002', 'application_no' => 'DEMO-BPA/2026/00002', 'bp_file_no' => 'BP-2026-DEMO2',
                'file_number' => 'RES-DEMO-0002', 'plot_no' => '7', 'district' => 'GWALE', 'lga' => 'GWALE',
                'file_land_use' => 'RESIDENTIAL', 'proposed_use' => 'Residential', 'land_use_check' => LandUse::MATCH, 'land_use_note' => null,
                'submitted_at' => $d(40, '09:12'), 'stage' => PortalTimeline::INSPECTION,
                'bill' => $bill('DEMO-BILL/2026/00002', 40, 'RCPT-DEMO-2', 38),
                'documents' => $plan(40),
                'inspection' => ['date' => now()->startOfDay()->addWeekdays(3), 'time' => '11:00', 'status' => 'Requested', 'contact_phone' => '08000000000', 'notes' => 'Gate is on the north side.'],
                'events' => [
                    $received('DEMO-BPA/2026/00002', 'DEMO-BILL/2026/00002', 40),
                    $paid('DEMO-BILL/2026/00002', 'RCPT-DEMO-2', 38),
                    $commissioned('BP-2026-DEMO2', 37),
                    ['title' => 'Not recommended by the Director (Arch.)', 'body' => 'Front setback is less than the 6 m required. Correct the site plan.', 'at' => $d(30, '12:30'), 'action' => BpaStage::NOT_RECOMMENDED],
                    ['title' => 'Sent back to the Director (Arch.)', 'body' => 'The correction was made and the file went back for review.', 'at' => $d(26, '10:00'), 'action' => 'resent'],
                    $recommended('Director (Arch.)', 22),
                    $recommended('Deputy Director (Arch.)', 15),
                    ['title' => 'Site inspection requested', 'body' => 'You asked for a visit on '.now()->startOfDay()->addWeekdays(3)->format('D j M Y').' at 11:00.', 'at' => $d(2, '17:45'), 'action' => 'inspection_requested'],
                ],
            ] + $base,
            [
                'ref' => 'DEMO-BPA/2026/00001', 'application_no' => 'DEMO-BPA/2026/00001', 'bp_file_no' => 'BP-2026-DEMO1',
                'file_number' => 'RES-DEMO-0001', 'plot_no' => '3', 'district' => 'FAGGE', 'lga' => 'FAGGE',
                'file_land_use' => 'RESIDENTIAL', 'proposed_use' => 'Residential', 'land_use_check' => LandUse::MATCH, 'land_use_note' => null,
                'submitted_at' => $d(90, '09:12'), 'stage' => PortalTimeline::APPROVED,
                'bill' => $bill('DEMO-BILL/2026/00001', 90, 'RCPT-DEMO-1', 88),
                'documents' => $plan(90),
                'office_documents' => [['name' => 'Conveyance of Building Plan Approval', 'at' => $d(45)]],
                'progress' => [
                    ['stage' => 'Setting out', 'photos' => 4, 'at' => $d(35, '08:30'), 'lat' => 12.0022, 'lng' => 8.5920],
                    ['stage' => 'Foundation', 'photos' => 6, 'at' => $d(20, '16:10'), 'lat' => 12.0023, 'lng' => 8.5921],
                ],
                'events' => [
                    $received('DEMO-BPA/2026/00001', 'DEMO-BILL/2026/00001', 90),
                    $paid('DEMO-BILL/2026/00001', 'RCPT-DEMO-1', 88),
                    $commissioned('BP-2026-DEMO1', 87),
                    $recommended('Director (Arch.)', 80),
                    $recommended('Deputy Director (Arch.)', 74),
                    ['title' => 'Site inspected', 'body' => 'The zonal officer inspected the site.', 'at' => $d(60, '11:30'), 'action' => 'inspected'],
                    ['title' => 'Building plan approved', 'body' => 'The Conveyance of Building Plan Approval is ready for you.', 'at' => $d(45, '14:00'), 'action' => 'approved'],
                    ['title' => 'Construction progress received', 'body' => '4 photos for Setting out.', 'at' => $d(35, '08:30'), 'action' => 'progress'],
                    ['title' => 'Construction progress received', 'body' => '6 photos for Foundation.', 'at' => $d(20, '16:10'), 'action' => 'progress'],
                ],
            ] + $base,
        ];
    }
}
