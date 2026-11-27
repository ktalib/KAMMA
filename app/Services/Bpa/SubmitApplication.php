<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\BpFile;
use App\Models\Portal\PortalApplicant;
use App\Models\User;
use App\Services\Klaes\IndexedFiles;
use App\Services\Serials;
use App\Support\Bpa\BpaForm;
use App\Support\LandUse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves a Building Plan Approval application at the Assessment Stage: the land-file snapshot from KLAES,
 * the land-use check, the uploaded plans and the application bill (rate × plots). Application and bill
 * numbers are yearly serials, e.g. BPA-APP/2026/00001 and BPA-BILL/2026/00001.
 *
 * Staff enter it with an officer; the applicant portal (since 2026-11-25) sends it with the portal applicant instead,
 * so it lands in the same staff Applications list with no receiving officer yet.
 */
class SubmitApplication
{
    public function __construct(private IndexedFiles $files, private Serials $serials)
    {
    }

    /**
     * @param  array<string, mixed>  $data  validated form input
     * @param  list<UploadedFile>  $uploads
     */
    public function handle(array $data, array $uploads, ?User $officer, ?PortalApplicant $applicant = null): Application
    {
        // Property details always come from KLAES (or from the BP file the application starts from), never from the posted form.
        $bpFile = null;
        if (! empty($data['bp_file_id'])) {
            $bpFile = BpFile::query()->live()->find((int) $data['bp_file_id']);
            if ($bpFile === null) {
                throw ValidationException::withMessages(['bp_file_id' => 'That BP file is not on the register. Open it again from BP Files.']);
            }
            if ($bpFile->bpa_application_id) {
                throw ValidationException::withMessages(['bp_file_id' => "{$bpFile->bp_file_no} already has an application."]);
            }
            $file = $bpFile->landFile();
        } else {
            $file = $this->files->find((int) ($data['klaes_file_indexing_id'] ?? 0));
        }
        if ($file === null) {
            throw ValidationException::withMessages([
                'klaes_file_indexing_id' => 'That land file was not found in KLAES (or it is deleted or decommissioned). Pick the file again.',
            ]);
        }

        [$check, $note] = LandUse::check($data['proposed_use'], $file['land_use_code']);
        $rate = (int) config('kamma.bpa.application_fee_per_plot');
        // Registration = a building put up without approval: billed at 4 × the normal fee (briefing 2026-11-22, user 2026-11-25).
        $type = ($data['application_type'] ?? null) === BpaForm::REGISTRATION ? BpaForm::REGISTRATION : BpaForm::FRESH;
        $multiplier = $type === BpaForm::REGISTRATION ? (int) config('kamma.bpa.registration_multiplier', 4) : 1;
        $plots = (int) $data['number_of_plots'];
        $disk = Storage::disk(config('kamma.bpa.uploads_disk'));
        $stored = [];

        try {
            return DB::transaction(function () use ($data, $uploads, $officer, $applicant, $file, $bpFile, $type, $multiplier, $check, $note, $rate, $plots, $disk, &$stored) {
                if ($bpFile && BpFile::query()->whereKey($bpFile->id)->lockForUpdate()->value('bpa_application_id')) {
                    throw ValidationException::withMessages(['bp_file_id' => "{$bpFile->bp_file_no} already has an application."]);
                }
                $year = (int) now()->format('Y');
                $applicationNo = sprintf('BPA-APP/%d/%05d', $year, $this->serials->next('bpa_application', $year));

                $application = Application::create([
                    'application_no' => $applicationNo,
                    'status' => 'Awaiting payment',
                    'application_type' => $type,
                    'bp_file_id' => $bpFile?->id,
                    'bp_file_no' => $bpFile?->bp_file_no,
                    'klaes_file_indexing_id' => $file['id'],
                    'file_number' => $file['file_number'],
                    'file_title' => $file['file_title'],
                    'plot_no' => $file['plot_no'],
                    'district' => $file['district'],
                    'lga' => $file['lga'],
                    'location' => $file['location'],
                    'plot_size' => $file['plot_size'],
                    'file_land_use' => $file['land_use'],
                    'file_land_use_code' => $file['land_use_code'],
                    'title_status' => $file['title_status'],
                    'proposed_use' => $data['proposed_use'],
                    'proposed_use_other' => $data['proposed_use'] === LandUse::OTHER ? $data['proposed_use_other'] : null,
                    'land_use_check' => $check,
                    'land_use_note' => $note,
                    'developer_name' => $data['developer_name'],
                    'cadastral_zone' => $data['cadastral_zone'] ?? null,
                    'cadastral_zone_plot_no' => $data['cadastral_zone_plot_no'] ?? null,
                    'street_name' => $data['street_name'] ?? null,
                    'dwelling_plot_floor' => $data['dwelling_plot_floor'] ?? null,
                    'dwelling_unit' => $data['dwelling_unit'] ?? null,
                    'contact_address' => $data['contact_address'] ?? null,
                    'developer_phone' => $data['developer_phone'] ?? null,
                    'developer_email' => $data['developer_email'] ?? null,
                    'representative_phone' => $data['representative_phone'] ?? null,
                    'representative_email' => $data['representative_email'] ?? null,
                    'submission_type' => $data['submission_type'],
                    'submission_type_other' => $data['submission_type'] === BpaForm::OTHER ? $data['submission_type_other'] : null,
                    'documents_submitted' => array_values($data['documents'] ?? []),
                    'documents_other' => in_array(BpaForm::OTHER, $data['documents'] ?? [], true) ? $data['documents_other'] : null,
                    'drawings_submitted' => array_values($data['drawings'] ?? []),
                    'plan_received_as' => $data['plan_received_as'],
                    'number_of_plots' => $plots,
                    'submitted_by_name' => $data['submitted_by_name'] ?? null,
                    'submitted_on' => $data['submitted_on'] ?? null,
                    'received_by' => $officer?->getKey(),
                    'received_by_name' => $officer?->name,
                    'received_by_rank' => $officer?->rank,
                    'portal_applicant_id' => $applicant?->id,
                ]);

                $folder = 'kamma/bpa-applications/'.Str::slug(str_replace('/', '-', $applicationNo));
                foreach ($uploads as $upload) {
                    $ext = strtolower($upload->getClientOriginalExtension());
                    $path = $upload->storeAs($folder, Str::uuid().'.'.$ext, ['disk' => config('kamma.bpa.uploads_disk')]);
                    $stored[] = $path;

                    $application->files()->create([
                        'kind' => in_array($ext, BpaForm::CAD_EXTENSIONS, true) ? 'cad' : 'plan',
                        'original_name' => mb_substr($upload->getClientOriginalName(), 0, 300),
                        'stored_path' => $path,
                        'extension' => $ext,
                        'size_bytes' => $upload->getSize(),
                        'uploaded_by' => $officer?->getKey(),
                    ]);
                }

                $application->bill()->create([
                    'bill_no' => sprintf('BPA-BILL/%d/%05d', $year, $this->serials->next('bpa_bill', $year)),
                    'bill_type' => $multiplier > 1 ? "BPA Registration Fee ({$multiplier} × normal fee, no prior approval)" : 'BPA Application Fee',
                    'rate' => $rate * $multiplier,
                    'quantity' => $plots,
                    'amount' => $rate * $multiplier * $plots,
                    'status' => 'Unpaid',
                    'created_by' => $officer?->getKey(),
                ]);

                $bpFile?->update(['bpa_application_id' => $application->id]);

                return $application;
            });
        } catch (\Throwable $e) {
            // Nothing was saved, so don't leave the uploaded files behind.
            foreach ($stored as $path) {
                $disk->delete($path);
            }
            throw $e;
        }
    }
}
