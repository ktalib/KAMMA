<?php

namespace App\Services\Kamma;

use App\Models\Dms\FileIndexing;
use App\Models\Kamma\DeskMovement;
use App\Models\User;
use App\Services\Tracking\FileTracking;
use App\Support\KammaDesk;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A land file's route through KAMMA (KAMMA Workflow v1.0 + briefing 2026-11-22), in the pattern of KADCA's
 * App\Services\Bpa\MoveFile and KLAES's Cadastral desk decisions:
 *
 *   PS → MD KAMMA → Department → Director → Deputy Director → Zonal Officer → Field Officer (KSI)
 *      → Planning Recommendation (MD KAMMA approval) → back to the PS (Ministry of Land).
 *
 * "Recommend / forward" moves the file to the next desk; "Not recommended" (reason required) sends it back one desk.
 * Every step is a desk_movements row, the shadow file's `stage` is the desk it sits on, and the physical file follows
 * (File Tracking). The KLAES land file itself is never changed (phase 4 detaches it by returning it to the PS).
 */
class DeskFlow
{
    /** The desks a file moves through in order (KammaDesk::FLOW without the PS, which only sends and receives). */
    public const ROUTE = ['md', 'department', 'director', 'deputy_director', 'zonal_officer', 'field_officer', 'planning_recommendation'];

    public function __construct(private FileTracking $tracking)
    {
    }

    /** Phase 1: the PS forwards the land file to the MD KAMMA (Shadow File Commissioning from the KAMMA menu). */
    public function receiveFromPs(FileIndexing $file, ?string $applicationType, User $officer): FileIndexing
    {
        return DB::transaction(function () use ($file, $applicationType, $officer) {
            $file->update([
                'stage' => 'md',
                'application_type' => $applicationType,
                'received_from_ps_at' => now(),
            ]);
            $this->log($file, 'ps', 'md', 'forwarded', 'Land file received from the PS for Planning Recommendation.', $officer);
            $this->tracking->followDesk($file, 'md', 'For MD KAMMA', $officer);

            return $file->refresh();
        });
    }

    /**
     * A desk decision on the file where it sits now.
     *
     * @param  string  $decision  recommended | not_recommended
     */
    public function decide(FileIndexing $file, string $decision, ?string $remarks, User $officer): FileIndexing
    {
        return DB::transaction(function () use ($file, $decision, $remarks, $officer) {
            $file = FileIndexing::query()->lockForUpdate()->findOrFail($file->getKey());
            $at = array_search($file->stage, self::ROUTE, true);
            if ($at === false) {
                throw ValidationException::withMessages(['decision' => 'This file is not on a KAMMA desk.']);
            }
            if (! in_array($decision, ['recommended', 'not_recommended'], true)) {
                throw ValidationException::withMessages(['decision' => 'Choose Recommend or Not recommended.']);
            }
            if ($decision === 'not_recommended' && blank($remarks)) {
                throw ValidationException::withMessages(['remarks' => 'Give the reason the file is not recommended.']);
            }
            if ($decision === 'not_recommended' && $at === 0) {
                throw ValidationException::withMessages(['decision' => 'The MD KAMMA returns a file to the PS with "Return to the Ministry".']);
            }

            $from = $file->stage;
            $to = $decision === 'recommended'
                ? (self::ROUTE[$at + 1] ?? 'returned_to_ps')
                : self::ROUTE[$at - 1];

            if ($to === 'returned_to_ps') {
                return $this->returnToPs($file, $remarks, $officer);
            }

            $file->update(['stage' => $to]);
            $this->log($file, $from, $to, $decision, $remarks, $officer);
            $purpose = $decision === 'recommended'
                ? 'For '.KammaDesk::label($to)
                : 'Not recommended by the '.KammaDesk::label($from).'; returned';
            // Planning Recommendation is the MD KAMMA's approval: the physical file goes back to the MD's office.
            $this->tracking->followDesk($file, $to === 'planning_recommendation' ? 'md' : $to, $purpose, $officer);

            return $file->refresh();
        });
    }

    /**
     * Phase 4: the land file is detached from the shadow file and goes back to the PS (Ministry of Land) for the next
     * stage. The shadow file and its KAMMA documents stay archived in KLAES-METRO.
     */
    public function returnToPs(FileIndexing $file, ?string $remarks, User $officer): FileIndexing
    {
        return DB::transaction(function () use ($file, $remarks, $officer) {
            if ($file->stage === 'returned_to_ps') {
                throw ValidationException::withMessages(['decision' => 'This land file has already gone back to the PS.']);
            }
            $from = $file->stage;
            $file->update(['stage' => 'returned_to_ps', 'returned_to_ps_at' => now()]);
            $this->log($file, $from, 'returned_to_ps', 'returned', $remarks ?: 'Land file returned to the Ministry of Land (PS).', $officer);
            $this->tracking->followDesk($file, 'ps', 'Returned to the Ministry of Land (PS)', $officer);

            return $file->refresh();
        });
    }

    private function log(FileIndexing $file, ?string $from, string $to, string $decision, ?string $remarks, User $officer): void
    {
        DeskMovement::create([
            'file_indexing_id' => $file->getKey(),
            'from_stage' => $from,
            'to_stage' => $to,
            'decision' => $decision,
            'remarks' => filled($remarks) ? trim($remarks) : null,
            'acted_by' => $officer->getKey(),
            'acted_by_name' => $officer->name,
            'acted_at' => now(),
        ]);
    }
}
