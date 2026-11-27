<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\User;
use App\Services\Tracking\FileTracking;
use App\Support\Bpa\BpaStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a BPA file between desks and records the move (kadca_bpa_movements), as KLAES Cadastral does:
 * a desk decision (Recommend / Not recommend, with a reason), or Architecture re-sending a returned
 * file to the Director (Arch.).
 */
class MoveFile
{
    public function __construct(private FileTracking $tracking)
    {
    }

    /** A desk's decision on a file that is on that desk. */
    public function decide(Application $application, string $deskSlug, string $decision, ?string $remarks, User $officer): Application
    {
        [$stage, $title, $onRecommend, $onRefuse] = BpaStage::desk($deskSlug);
        $remarks = $remarks !== null ? trim($remarks) : null;

        if ($decision === BpaStage::NOT_RECOMMENDED && ($remarks === null || $remarks === '')) {
            throw ValidationException::withMessages(['remarks' => 'Give the reason the file is not recommended.']);
        }
        if (! in_array($decision, [BpaStage::RECOMMENDED, BpaStage::NOT_RECOMMENDED], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose Recommend or Not recommended.']);
        }

        return $this->move($application, $stage, $decision === BpaStage::RECOMMENDED ? $onRecommend : $onRefuse, $decision, $remarks, $officer,
            "The file is not on the {$title}'s desk any more.");
    }

    /** Architecture sends a returned file back to the Director (Arch.). */
    public function resend(Application $application, ?string $remarks, User $officer): Application
    {
        return $this->move($application, BpaStage::ARCHITECTURE, BpaStage::DIRECTOR, 'resent', $remarks, $officer,
            'Only a file returned to Architecture can be sent back to the Director (Arch.).');
    }

    /** Commissioning puts the new BP file on the Director (Arch.)'s desk. Call inside the commissioning transaction. */
    public function record(Application $application, ?string $from, string $to, string $action, ?string $remarks, User $officer): void
    {
        $application->movements()->create([
            'from_stage' => $from,
            'to_stage' => $to,
            'action' => $action,
            'remarks' => $remarks !== '' ? $remarks : null,
            'acted_by' => $officer->getKey(),
            'acted_by_name' => $officer->name,
            'acted_by_rank' => $officer->rank,
            'acted_at' => now(),
        ]);
    }

    private function move(Application $application, string $from, string $to, string $action, ?string $remarks, User $officer, string $notHere): Application
    {
        return DB::transaction(function () use ($application, $from, $to, $action, $remarks, $officer, $notHere) {
            $application = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            // Two officers on the same file: the second one finds it has already moved.
            if ($application->stage !== $from) {
                throw ValidationException::withMessages(['application' => "{$application->bp_file_no}: {$notHere} It is now with ".BpaStage::label($application->stage).'.']);
            }

            $application->update(['stage' => $to, 'status' => BpaStage::status($to)]);
            $this->record($application, $from, $to, $action, $remarks, $officer);

            // The physical BP file follows the decision to the desk it now sits with.
            $purpose = match ($action) {
                BpaStage::RECOMMENDED => 'Recommended by the '.BpaStage::label($from),
                BpaStage::NOT_RECOMMENDED => 'Not recommended by the '.BpaStage::label($from).'; returned',
                'resent' => 'Corrected; sent back to the Director (Arch.)',
                default => BpaStage::label($to),
            };
            $this->tracking->followBpaDesk($application, $to, $purpose, $officer);

            return $application;
        });
    }
}
