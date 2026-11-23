<?php

namespace App\Services\Kamma;

use App\Models\Dms\FileIndexing;
use App\Models\Kamma\Bill;
use App\Models\Kamma\KsiReport;
use App\Models\User;
use App\Services\Klaes\KsiLookups;
use App\Services\Serials;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * KAMMA bills (KAMMA Workflow v1.0 phases 2 and 3), kept in kamma_bills until KAMMA bills go into KLAES `billing` with
 * source KAMMA (needs user approval + a grant):
 *
 *  - Application Processing Fee: raised on the shadow file. The amount is an OPEN QUESTION (config kamma.bills.processing_fee);
 *    until KAMMA gives it, the bill cannot be raised.
 *  - Betterment: KLAES Physical Planning's formula (pp_betterment_rates: base fee + max(0, area − base area) × rate per extra
 *    m²) on the KSI's RECOMMENDED area (KLAES rule; extension not charged), and the no-BPA penalty:
 *      IF Site_Assessment_Report = Building_Exists AND Land_File_Status != BPA_Approved THEN fee = standard × 4.
 *    Which rate table KAMMA uses is an open question; KLAES PP's rates are used until KAMMA says otherwise.
 *
 * Numbers: BILL-KMA-YYYY-NNNNN (App\Services\Serials, restarts each 1 January).
 */
class Bills
{
    public function __construct(private Serials $serials, private KsiLookups $lookups)
    {
    }

    /**
     * @return array{category:string, area:float, base_fee:float, base_area:float, rate_per_extra_sqm:float, standard:float, multiplier:int, amount:float, penalty:bool}
     */
    public function betterment(string $category, float $area, bool $buildingExists, bool $bpaApproved): array
    {
        $rate = collect($this->lookups->bettermentRates())->first(fn ($r) => strcasecmp($r['category'], $category) === 0);
        if ($rate === null) {
            throw ValidationException::withMessages(['category' => 'Pick a betterment category from the rate table.']);
        }
        $standard = $rate['base_fee'] + max(0, $area - $rate['base_area']) * $rate['rate_per_extra_sqm'];
        $penalty = $buildingExists && ! $bpaApproved;
        $multiplier = $penalty ? (int) config('kamma.bills.no_bpa_penalty_multiplier', 4) : 1;

        return [
            'category' => $rate['category'], 'area' => $area, 'base_fee' => $rate['base_fee'], 'base_area' => $rate['base_area'],
            'rate_per_extra_sqm' => $rate['rate_per_extra_sqm'], 'standard' => round($standard, 2), 'multiplier' => $multiplier,
            'amount' => round($standard * $multiplier, 2), 'penalty' => $penalty,
        ];
    }

    public function processingFee(): ?float
    {
        $fee = config('kamma.bills.processing_fee');

        return $fee === null ? null : (float) $fee;
    }

    public function raiseProcessingFee(FileIndexing $file, User $officer): Bill
    {
        $fee = $this->processingFee();
        if ($fee === null) {
            throw ValidationException::withMessages(['bill_type' => 'The Application Processing Fee amount has not been set by KAMMA yet, so the bill cannot be raised.']);
        }
        if ($file->bills()->where('bill_type', 'processing')->exists()) {
            throw ValidationException::withMessages(['bill_type' => "{$file->file_number} already has a processing fee bill."]);
        }

        return $this->create([
            'bill_type' => 'processing', 'source' => $file->application_type ?: 'conversion', 'file_indexing_id' => $file->id,
            'file_number' => $file->file_number, 'payer_name' => $file->file_title, 'standard_amount' => $fee, 'amount' => $fee,
        ], $officer);
    }

    public function raiseBetterment(KsiReport $report, string $category, bool $bpaApproved, User $officer): Bill
    {
        $area = $report->chargeableArea();
        if ($area === null || $area <= 0) {
            throw ValidationException::withMessages(['ksi_report_id' => 'The KSI report has no recommended site measurement to charge on.']);
        }
        if ($report->bills()->where('bill_type', 'betterment')->exists()) {
            throw ValidationException::withMessages(['ksi_report_id' => 'This KSI report already has a betterment bill.']);
        }
        $c = $this->betterment($category, $area, (bool) $report->building_exists, $bpaApproved);

        return $this->create([
            'bill_type' => 'betterment', 'source' => $report->kind === 'master' ? 'master_ksi' : $report->kind,
            'file_indexing_id' => $report->file_indexing_id, 'ksi_report_id' => $report->id, 'file_number' => $report->file_number,
            'payer_name' => $report->applicant_name, 'category' => $c['category'], 'area' => $c['area'], 'base_fee' => $c['base_fee'],
            'base_area' => $c['base_area'], 'rate_per_extra_sqm' => $c['rate_per_extra_sqm'], 'standard_amount' => $c['standard'],
            'building_exists' => (bool) $report->building_exists, 'bpa_approved' => $bpaApproved, 'penalty_multiplier' => $c['multiplier'],
            'amount' => $c['amount'],
        ], $officer);
    }

    /** Record the applicant's payment (KADCA payment pop-up rules: receipt no. unique, amount must equal the bill). */
    public function pay(Bill $bill, array $d, User $officer): Bill
    {
        if ($bill->isPaid()) {
            throw ValidationException::withMessages(['receipt_no' => "{$bill->bill_no} is already paid."]);
        }
        if (abs((float) $d['amount_paid'] - (float) $bill->amount) > 0.009) {
            throw ValidationException::withMessages(['amount_paid' => 'The amount paid must equal the bill: ₦'.number_format((float) $bill->amount, 2).'.']);
        }
        $bill->update([
            'status' => 'paid', 'receipt_no' => trim($d['receipt_no']), 'amount_paid' => $d['amount_paid'], 'paid_on' => $d['paid_on'],
            'channel' => $d['channel'], 'channel_other' => $d['channel'] === 'Other' ? trim((string) ($d['channel_other'] ?? '')) : null,
        ]);

        return $bill;
    }

    private function create(array $data, User $officer): Bill
    {
        return DB::transaction(fn () => Bill::create($data + [
            'bill_no' => sprintf('BILL-KMA-%s-%05d', now()->format('Y'), $this->serials->next('kamma-bill')),
            'status' => 'unpaid', 'created_by' => $officer->getKey(), 'created_by_name' => $officer->name,
        ]));
    }
}
