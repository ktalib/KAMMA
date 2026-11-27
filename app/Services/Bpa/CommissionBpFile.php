<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\User;
use App\Services\Tracking\FileTracking;
use App\Support\Bpa\BpaStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Commissions the BP file of a paid application: BP-[Year]-[Serial] (user 2026-11-22: "it should be BP-2026-1") (briefing 2026-11-22: "BP is the prefix,
 * the year, and serial number"). The serial restarts on 1 January. Unpadded, like KLAES land-file numbers
 * (RES-2016-452), e.g. BP-2026-1. Numbers come from BpNumbers, so they never land on an indexed old file's number.
 *
 * An application started from a BP file (BP indexing / commissioning, 2026-11-25) already has its number: once its
 * bill is paid it goes straight to the Director (Arch.) through forward().
 */
class CommissionBpFile
{
    public function __construct(private BpNumbers $numbers, private BpFiles $bpFiles, private MoveFile $moves, private FileTracking $tracking)
    {
    }

    public function handle(Application $application, User $officer): Application
    {
        return DB::transaction(function () use ($application, $officer) {
            $application = Application::query()->with('bill')->lockForUpdate()->findOrFail($application->getKey());

            if ($application->bp_file_no !== null) {
                throw ValidationException::withMessages(['application' => "{$application->application_no} is already commissioned as {$application->bp_file_no}."]);
            }
            if ($application->bill?->status !== 'Paid') {
                throw ValidationException::withMessages(['application' => "{$application->application_no} cannot be commissioned until its application bill is paid."]);
            }

            $year = (int) now()->format('Y');
            [$serial, $row] = $this->numbers->nextForCommissioning($year);
            $bpFile = $this->bpFiles->fromApplication($application, $year, $serial, $row, $officer);
            $application->update([
                'bp_file_no' => $bpFile->bp_file_no,
                'bp_file_id' => $bpFile->id,
                // A new BP file goes straight to the Director (Arch.) for recommendation (briefing 2026-11-22).
                'stage' => BpaStage::DIRECTOR,
                'status' => BpaStage::status(BpaStage::DIRECTOR),
                'commissioned_at' => now(),
                'commissioned_by' => $officer->getKey(),
                'commissioned_by_name' => $officer->name,
            ]);
            $this->moves->record($application, BpaStage::ARCHITECTURE, BpaStage::DIRECTOR, 'commissioned', null, $officer);

            // The physical BP file starts its tracking in Architecture and is sent to the Director (Arch.).
            $this->tracking->startForBpaApplication($application, $officer);
            $this->tracking->followBpaDesk($application, BpaStage::DIRECTOR, 'Commissioned; for recommendation', $officer);

            return $application;
        });
    }

    /** A paid application whose BP file was indexed or commissioned first: on to the Director (Arch.). Call inside a transaction. */
    public function forward(Application $application, User $officer): Application
    {
        $bp = $application->bpFile;
        $application->update([
            'stage' => BpaStage::DIRECTOR,
            'status' => BpaStage::status(BpaStage::DIRECTOR),
            'commissioned_at' => $bp?->indexed_at ?? now(),
            'commissioned_by' => $bp?->indexed_by ?? $officer->getKey(),
            'commissioned_by_name' => $bp?->indexed_by_name ?? $officer->name,
        ]);
        $this->moves->record($application, BpaStage::ARCHITECTURE, BpaStage::DIRECTOR, 'paid', null, $officer);
        $this->tracking->startForBpaApplication($application, $officer);
        $this->tracking->followBpaDesk($application, BpaStage::DIRECTOR, 'Application paid; for recommendation', $officer);

        return $application;
    }
}
