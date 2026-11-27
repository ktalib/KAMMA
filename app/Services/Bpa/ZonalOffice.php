<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\Conveyance;
use App\Models\Bpa\SiteInspection;
use App\Models\Bpa\ZonalAssignment;
use App\Models\User;
use App\Services\Serials;
use App\Services\Tracking\FileTracking;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The KAMMA workflow after the Deputy Director (Arch.), briefing 2026-11-22:
 *
 *   zonal_office  -- Head of Zonal Office assigns an officer (Instruction to Inspect) -->  inspection
 *   inspection    -- the officer files the Site Inspection Report ------------------->  zonal_review
 *   zonal_review  -- Head approves (report must recommend approval) ----------------->  conveyance
 *                 -- Head returns it to the officer (reason) ------------------------>  inspection
 *                 -- Head refers it back to the Deputy Director (Arch.) (reason) ---->  dd_arch
 *   conveyance    -- Final Conveyance (Development Permit) raised ------------------->  approved (BPA approved)
 *
 * Every step is recorded in kadca_bpa_movements, like the Director and DD desks, and the physical file follows the desk
 * where one exists in File Tracking. Roles are on hold (user): any signed-in officer may act; who acted is recorded.
 */
class ZonalOffice
{
    public const ASSIGNED = 'assigned';
    public const REPORT_FILED = 'report_filed';
    public const REPORT_APPROVED = 'report_approved';
    public const REPORT_RETURNED = 'report_returned';
    public const REFERRED_BACK = 'referred_back';
    public const CONVEYED = 'conveyed';

    public function __construct(private Serials $serials, private MoveFile $moves, private FileTracking $tracking)
    {
    }

    /** @param  array{officer: User, scope: list<string>, due_date: string, instructions?: ?string}  $d */
    public function assign(Application $application, array $d, User $head): ZonalAssignment
    {
        return DB::transaction(function () use ($application, $d, $head) {
            $application = $this->lockAt($application, BpaStage::ZONAL_OFFICE, 'is not waiting for an officer');
            $officer = $d['officer'];

            $assignment = $application->assignments()->create([
                'instruction_no' => sprintf('BPA-ZI/%s/%05d', now()->format('Y'), $this->serials->next('zonal_instruction')),
                'officer_id' => $officer->getKey(),
                'officer_name' => $officer->name,
                'officer_rank' => $officer->rank,
                'scope' => array_values($d['scope']),
                'due_date' => $d['due_date'],
                'instructions' => $this->clean($d['instructions'] ?? null),
                'issued_by' => $head->getKey(),
                'issued_by_name' => $head->name,
                'issued_at' => now(),
                'status' => 'active',
            ]);

            $this->advance($application, BpaStage::ZONAL_OFFICE, BpaStage::INSPECTION, self::ASSIGNED,
                "{$assignment->instruction_no}: assigned to {$officer->name}", $head);

            return $assignment;
        });
    }

    /**
     * The officer's report. Photos are stored before the transaction and removed again if it fails.
     *
     * @param  array<string, mixed>  $d  validated fields
     * @param  list<array{file: UploadedFile, lat: ?float, lng: ?float, taken_at: ?string}>  $photos
     */
    public function fileReport(Application $application, array $d, array $photos, User $officer): SiteInspection
    {
        if ($photos === []) {
            throw ValidationException::withMessages(['photos' => 'Add at least one geocoded site photograph.']);
        }

        $disk = Storage::disk(config('kamma.bpa.uploads_disk'));
        $folder = 'kamma/bpa-site-inspections/'.$application->getKey();
        $stored = [];
        foreach ($photos as $p) {
            $ext = strtolower($p['file']->getClientOriginalExtension() ?: $p['file']->extension() ?: 'jpg');
            $stored[] = $p + ['path' => $p['file']->storeAs($folder, Str::uuid().'.'.$ext, ['disk' => config('kamma.bpa.uploads_disk')])];
        }

        try {
            return DB::transaction(function () use ($application, $d, $stored, $officer) {
                $application = $this->lockAt($application, BpaStage::INSPECTION, 'is not with an inspecting officer');
                $assignment = $application->assignments()->where('status', 'active')->latest('id')->first();

                $inspection = $application->inspections()->create(array_merge($d, [
                    'zonal_assignment_id' => $assignment?->getKey(),
                    'officer_id' => $officer->getKey(),
                    'officer_name' => $officer->name,
                    'officer_rank' => $this->clean($d['officer_rank'] ?? null) ?? $officer->rank,
                    'filed_at' => now(),
                ]));
                foreach ($stored as $p) {
                    $inspection->photos()->create([
                        'path' => $p['path'],
                        'original_name' => mb_substr($p['file']->getClientOriginalName(), 0, 300),
                        'latitude' => $p['lat'],
                        'longitude' => $p['lng'],
                        'taken_at' => $p['taken_at'],
                    ]);
                }
                $assignment?->update(['status' => 'reported']);

                $this->advance($application, BpaStage::INSPECTION, BpaStage::ZONAL_REVIEW, self::REPORT_FILED,
                    $inspection->recommendation.($inspection->remarks ? ': '.$inspection->remarks : ''), $officer);

                return $inspection;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $p) {
                $disk->delete($p['path']);
            }
            throw $e;
        }
    }

    /** Head of Zonal Office on a filed report: approve | return (to the officer) | refer (back to the DD Arch.). */
    public function review(Application $application, string $decision, ?string $remarks, User $head): Application
    {
        $remarks = $this->clean($remarks);
        if (in_array($decision, ['return', 'refer'], true) && $remarks === null) {
            throw ValidationException::withMessages(['remarks' => $decision === 'return'
                ? 'Give the reason the report goes back to the officer.' : 'Give the reason the file goes back to the Deputy Director (Arch.).']);
        }

        return DB::transaction(function () use ($application, $decision, $remarks, $head) {
            $application = $this->lockAt($application, BpaStage::ZONAL_REVIEW, 'has no inspection report waiting for review');
            $inspection = $application->inspections()->whereNull('review_decision')->latest('id')->firstOrFail();

            if ($decision === 'approve' && $inspection->recommendation !== SiteInspection::RECOMMEND_APPROVAL) {
                throw ValidationException::withMessages(['decision' => 'The officer did not recommend approval ("'.$inspection->recommendation
                    .'"). Return the report to the officer or refer the file back to the Deputy Director (Arch.).']);
            }

            [$to, $action, $review] = match ($decision) {
                'approve' => [BpaStage::CONVEYANCE, self::REPORT_APPROVED, 'approved'],
                'return' => [BpaStage::INSPECTION, self::REPORT_RETURNED, 'returned'],
                'refer' => [BpaStage::DEPUTY_DIRECTOR, self::REFERRED_BACK, 'referred'],
                default => throw ValidationException::withMessages(['decision' => 'Choose Approve, Return to officer or Refer back.']),
            };

            $inspection->update(['review_decision' => $review, 'review_remarks' => $remarks, 'reviewed_by' => $head->getKey(),
                'reviewed_by_name' => $head->name, 'reviewed_at' => now()]);
            $inspection->assignment?->update(['status' => $decision === 'return' ? 'active' : 'closed']);

            $this->advance($application, BpaStage::ZONAL_REVIEW, $to, $action, $remarks, $head);

            return $application;
        });
    }

    /** @param  array<string, mixed>  $d  validated conveyance fields */
    public function convey(Application $application, array $d, User $officer): Conveyance
    {
        return DB::transaction(function () use ($application, $d, $officer) {
            $application = $this->lockAt($application, BpaStage::CONVEYANCE, 'is not ready for Final Conveyance');

            $conveyance = $application->conveyance()->create(array_merge($d, [
                'permit_no' => sprintf('DP/%s/%05d', now()->format('Y'), $this->serials->next('development_permit')),
                'additional_conditions' => $this->clean($d['additional_conditions'] ?? null),
                'raised_by' => $officer->getKey(),
                'raised_by_name' => $officer->name,
            ]));

            $application->forceFill(['bpa_approved_at' => now()]);
            $this->advance($application, BpaStage::CONVEYANCE, BpaStage::APPROVED, self::CONVEYED,
                "Development Permit {$conveyance->permit_no}", $officer);

            return $conveyance;
        });
    }

    private function lockAt(Application $application, string $stage, string $notHere): Application
    {
        $application = Application::query()->lockForUpdate()->findOrFail($application->getKey());
        if ($application->stage !== $stage) {
            throw ValidationException::withMessages(['application' => ($application->bp_file_no ?: $application->application_no)
                ." {$notHere}. It is now with ".BpaStage::label($application->stage).'.']);
        }

        return $application;
    }

    private function advance(Application $application, string $from, string $to, string $action, ?string $remarks, User $officer): void
    {
        $application->fill(['stage' => $to, 'status' => BpaStage::status($to)])->save();
        $this->moves->record($application, $from, $to, $action, $remarks, $officer);

        // The physical file follows to a desk that has a File Tracking unit. Final Conveyance is raised by the Director (Arch.).
        $desk = $to === BpaStage::CONVEYANCE ? BpaStage::DIRECTOR : $to;
        $this->tracking->followBpaDesk($application, $desk, BpaStage::status($to), $officer);
    }

    private function clean(?string $text): ?string
    {
        $text = $text !== null ? trim($text) : null;

        return $text === '' ? null : $text;
    }
}
