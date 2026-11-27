<?php

namespace App\Services\Kamma;

use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Models\User;
use App\Services\Serials;
use App\Support\KammaKinds;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The MD KAMMA's approvals and the KAMMA Memo (KLAES Physical Planning: the Director of Physical Planning approves the
 * Planning Recommendation, then the memo is raised and approved; in KAMMA the Director is the MD KAMMA, briefing 2026-11-22).
 *
 *  - decideReport(): Planning Recommendation Approval / Master KSI Approval on a submitted KSI report. Decline needs a reason.
 *    A decided report is final (no second decision), like KLAES's approval pages.
 *  - raiseMemo(): the planner's memo on an APPROVED Planning Recommendation (Conversion, ST, SLTR; not Master KSI, which
 *    has no memo in the menu). One memo per report. Number KMA-PM/YYYY/00001 (App\Services\Serials, restarts each 1 January).
 *  - decideMemo(): the MD KAMMA's recommendation on the memo; approve or decline (reason required).
 *
 * The KLAES land file is never changed here; the desk route stays with App\Services\Kamma\DeskFlow.
 */
class MdApprovals
{
    public const DECISIONS = ['approved', 'declined'];

    public function __construct(private Serials $serials)
    {
    }

    public function decideReport(KsiReport $report, string $decision, ?string $remarks, User $md): KsiReport
    {
        $this->checkDecision($decision, $remarks, 'remarks');

        return DB::transaction(function () use ($report, $decision, $remarks, $md) {
            $report = KsiReport::query()->lockForUpdate()->findOrFail($report->getKey());
            if ($report->md_decision !== null) {
                throw ValidationException::withMessages(['decision' => "The KSI report on {$report->file_number} was already {$report->md_decision} by the MD KAMMA."]);
            }
            if ($report->status !== 'submitted') {
                throw ValidationException::withMessages(['decision' => 'Only a submitted KSI report goes to the MD KAMMA.']);
            }

            $report->update([
                'md_decision' => $decision,
                'md_remarks' => filled($remarks) ? trim($remarks) : null,
                'md_decided_by' => $md->getKey(),
                'md_decided_by_name' => $md->name,
                'md_decided_at' => now(),
                'status' => $decision,
            ]);

            return $report;
        });
    }

    public function raiseMemo(KsiReport $report, string $plannerRecommendation, string $date, User $planner): Memo
    {
        if (! in_array($report->kind, KammaKinds::prKinds(), true)) {
            throw ValidationException::withMessages(['ksi_report_id' => 'Master KSI reports have no KAMMA Memo.']);
        }
        if ($report->md_decision !== 'approved') {
            throw ValidationException::withMessages(['ksi_report_id' => 'The memo is raised only after the MD KAMMA approves the Planning Recommendation.']);
        }

        return DB::transaction(function () use ($report, $plannerRecommendation, $date, $planner) {
            if (Memo::query()->where('ksi_report_id', $report->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['ksi_report_id' => "{$report->file_number} already has a KAMMA Memo."]);
            }

            return Memo::create([
                'kind' => $report->kind,
                'memo_no' => sprintf('KMA-PM/%s/%05d', now()->format('Y'), $this->serials->next('kamma_memo')),
                'ksi_report_id' => $report->id,
                'file_number' => $report->file_number,
                'applicant_name' => $report->applicant_name,
                // Property order: Plot No., District, LGA, Location.
                'property_location' => self::property($report),
                'planner_recommendation' => trim($plannerRecommendation),
                'planner_recommendation_date' => $date,
                'status' => 'submitted',
                'created_by' => $planner->getKey(),
                'created_by_name' => $planner->name,
            ]);
        });
    }

    public function decideMemo(Memo $memo, string $decision, ?string $recommendation, User $md): Memo
    {
        $this->checkDecision($decision, $recommendation, 'md_recommendation');

        return DB::transaction(function () use ($memo, $decision, $recommendation, $md) {
            $memo = Memo::query()->lockForUpdate()->findOrFail($memo->getKey());
            if ($memo->status !== 'submitted') {
                throw ValidationException::withMessages(['decision' => "Memo {$memo->memo_no} was already {$memo->status}."]);
            }

            $memo->update([
                'md_recommendation' => filled($recommendation) ? trim($recommendation) : null,
                'md_recommendation_date' => now()->toDateString(),
                'status' => $decision,
            ]);

            return $memo;
        });
    }

    public static function property(KsiReport $r): string
    {
        return collect([$r->plot_number ? 'Plot '.$r->plot_number : null, $r->district, $r->lga, $r->location])
            ->filter(fn ($v) => filled($v))->implode(', ');
    }

    private function checkDecision(string $decision, ?string $reason, string $field): void
    {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw ValidationException::withMessages(['decision' => 'Choose Approve or Decline.']);
        }
        if ($decision === 'declined' && blank($reason)) {
            throw ValidationException::withMessages([$field => 'Give the reason for declining.']);
        }
    }
}
