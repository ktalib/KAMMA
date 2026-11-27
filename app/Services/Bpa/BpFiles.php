<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\BpFile;
use App\Models\Bpa\BpGrouping;
use App\Models\Bpa\BpZone;
use App\Models\User;
use App\Services\Klaes\IndexedFiles;
use App\Support\LandUse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * BP file indexing and commissioning (user briefing 2026-11-25). The screen has two halves: on the left the land
 * file picked from KLAES (its indexing record, locked: "those will not change"), on the right the BP file's own
 * indexing. Indexing = an existing BP file under its number from the grouping; commissioning = a new BP file
 * given the next serial of the year. Either way the record then goes on to the Application section.
 */
class BpFiles
{
    public function __construct(private IndexedFiles $klaes, private BpNumbers $numbers)
    {
    }

    /** @param array<string, mixed> $data validated (StoreBpFileRequest) */
    public function index(array $data, User $officer): BpFile
    {
        return DB::transaction(function () use ($data, $officer) {
            $row = $this->numbers->claimForIndexing((int) $data['bp_year'], (int) $data['bp_serial']);
            $file = $this->create(BpFile::INDEXED, $row->year, $row->serial, $data, $officer);
            $this->numbers->markUsed($row, $file, BpGrouping::INDEXED);

            return $file;
        });
    }

    /** @param array<string, mixed> $data validated (StoreBpFileRequest) */
    public function commission(array $data, User $officer): BpFile
    {
        return DB::transaction(function () use ($data, $officer) {
            $year = (int) now()->format('Y');
            [$serial, $row] = $this->numbers->nextForCommissioning($year);
            $file = $this->create(BpFile::COMMISSIONED, $year, $serial, $data, $officer);
            $this->numbers->markUsed($row, $file, BpGrouping::COMMISSIONED);

            return $file;
        });
    }

    /** The BP half only (e.g. set the zone of an "Other" file later). The number and the land file stay. */
    public function update(BpFile $file, array $data, User $officer): BpFile
    {
        $file->update($this->bpHalf($data) + ['updated_by' => $officer->getKey(), 'updated_by_name' => $officer->name]);

        return $file;
    }

    /**
     * A BP file commissioned through the older application path (Architecture > File Commissioning): it gets its
     * BP file row too, so the register holds every BP file.
     */
    public function fromApplication(Application $application, int $year, int $serial, ?BpGrouping $row, User $officer): BpFile
    {
        $file = BpFile::create([
            'bp_file_no' => BpNumbers::format($year, $serial), 'bp_year' => $year, 'bp_serial' => $serial, 'origin' => BpFile::COMMISSIONED,
            'land_in_klaes' => $application->klaes_file_indexing_id !== null, 'klaes_file_indexing_id' => $application->klaes_file_indexing_id,
            'land_file_number' => $application->file_number, 'land_file_title' => $application->file_title,
            'plot_no' => $application->plot_no, 'district' => $application->district, 'lga' => $application->lga, 'location' => $application->location,
            'land_use' => $application->file_land_use, 'land_use_code' => $application->file_land_use_code, 'plot_size' => $application->plot_size,
            'street_name' => $application->street_name, 'phone' => $application->developer_phone, 'address' => $application->contact_address,
            'title_status' => $application->title_status, 'bp_file_title' => $application->developer_name,
            'indexed_by' => $officer->getKey(), 'indexed_by_name' => $officer->name, 'indexed_at' => now(),
            'bpa_application_id' => $application->id,
        ]);
        $this->numbers->markUsed($row, $file, BpGrouping::COMMISSIONED);

        return $file;
    }

    private function create(string $origin, int $year, int $serial, array $data, User $officer): BpFile
    {
        $number = BpNumbers::format($year, $serial);
        if (BpFile::query()->live()->where('bp_file_no', $number)->exists()) {
            throw ValidationException::withMessages(['bp_serial' => "{$number} is already on the register."]);
        }

        return BpFile::create([
            'bp_file_no' => $number, 'bp_year' => $year, 'bp_serial' => $serial, 'origin' => $origin,
            ...$this->landHalf($data),
            ...$this->bpHalf($data),
            'indexed_by' => $officer->getKey(), 'indexed_by_name' => $officer->name, 'indexed_at' => now(),
        ]);
    }

    /** The land file comes from KLAES, never from the posted form; only a file missing from KLAES is typed (and flagged). */
    private function landHalf(array $data): array
    {
        if (empty($data['land_not_in_klaes'])) {
            $f = $this->klaes->find((int) ($data['klaes_file_indexing_id'] ?? 0));
            if ($f === null) {
                throw ValidationException::withMessages(['klaes_file_indexing_id' => 'That land file was not found in KLAES (or it is deleted or decommissioned). Pick it again.']);
            }

            return [
                'land_in_klaes' => true, 'klaes_file_indexing_id' => $f['id'], 'land_file_number' => $f['file_number'],
                'land_file_title' => $f['file_title'], 'plot_no' => $f['plot_no'], 'district' => $f['district'], 'lga' => $f['lga'],
                'location' => $f['location'], 'land_use' => $f['land_use'], 'land_use_code' => $f['land_use_code'],
                'plot_size' => $f['plot_size'], 'street_name' => $f['street_name'], 'phone' => $f['phone'], 'address' => $f['address'],
                'title_status' => $f['title_status'],
            ];
        }

        $code = LandUse::code($data['land_use'] ?? null, $data['land_file_number']);

        return [
            'land_in_klaes' => false, 'klaes_file_indexing_id' => null, 'land_file_number' => $data['land_file_number'],
            'land_file_title' => $data['land_file_title'] ?? null, 'plot_no' => $data['plot_no'] ?? null, 'district' => $data['district'] ?? null,
            'lga' => $data['lga'] ?? null, 'location' => $data['location'] ?? null,
            'land_use' => $data['land_use'] ?? ($code ? LandUse::LABELS[$code] : null), 'land_use_code' => $code,
            'plot_size' => $data['plot_size'] ?? null, 'title_status' => 'Not in KLAES',
        ];
    }

    private function bpHalf(array $data): array
    {
        $zone = BpZone::query()->active()->find($data['bp_zone_id'] ?? 0);
        if ($zone === null) {
            throw ValidationException::withMessages(['bp_zone_id' => 'Pick the zone (or Other if it is not known yet).']);
        }

        return [
            'bp_zone_id' => $zone->id, 'zone_name' => $zone->name,
            'bp_file_title' => $data['bp_file_title'] ?? null,
            'registry' => $data['registry'] ?? null, 'batch_no' => $data['batch_no'] ?? null,
            'shelf_location' => $data['shelf_location'] ?? null, 'serial_no' => $data['serial_no'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];
    }
}
