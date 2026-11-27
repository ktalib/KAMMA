<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Bill;
use App\Models\User;
use App\Support\Bpa\BpaStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Confirms that a KAMMA bill was paid (receipt number, date, amount, channel). Until KAMMA bills are
 * written into KLAES `billing`, staff confirm the bank/POS receipt here (Architecture › Payment for the BPA
 * initial bill, REV-M Bill Master for the others). Part payments are not taken: the amount must equal the bill,
 * or what is left of a part-paid legacy bill. Only the BPA initial bill moves its application on.
 */
class RecordPayment
{
    public const CHANNELS = ['Bank', 'POS', 'Interswitch', 'USSD', 'Other'];

    /** @param array{receipt_no: string, paid_on: string, amount_paid: string|float, payment_channel: string, payment_channel_other?: ?string} $data */
    public function handle(Bill $bill, array $data, User $officer): Bill
    {
        return DB::transaction(function () use ($bill, $data, $officer) {
            $bill = Bill::query()->lockForUpdate()->findOrFail($bill->getKey());

            if (! in_array($bill->status, ['Unpaid', 'Part paid'], true)) {
                throw ValidationException::withMessages(['receipt_no' => "Bill {$bill->bill_no} is already recorded as paid."]);
            }
            $due = $bill->status === 'Part paid' ? $bill->balance() : round((float) $bill->amount, 2);
            if (round((float) $data['amount_paid'], 2) !== $due) {
                throw ValidationException::withMessages([
                    'amount_paid' => ($bill->status === 'Part paid' ? 'The amount paid must equal the balance left on the bill, ₦' : 'The amount paid must equal the bill, ₦')
                        .number_format($due, 2).'. Part payments are not taken.',
                ]);
            }
            if (Bill::query()->where('id', '!=', $bill->id)->whereRaw('UPPER(receipt_no) = ?', [mb_strtoupper($data['receipt_no'])])->exists()) {
                throw ValidationException::withMessages(['receipt_no' => 'That receipt number is already recorded on another bill.']);
            }

            $bill->update([
                'status' => 'Paid',
                'receipt_no' => $data['receipt_no'],
                'paid_on' => $data['paid_on'],
                'amount_paid' => round((float) $bill->amount_paid * ($bill->status === 'Part paid' ? 1 : 0) + (float) $data['amount_paid'], 2),
                'payment_channel' => $data['payment_channel'],
                'payment_channel_other' => $data['payment_channel'] === 'Other' ? ($data['payment_channel_other'] ?? null) : null,
                'payment_recorded_by' => $officer->getKey(),
                'payment_recorded_by_name' => $officer->name,
                'payment_recorded_at' => now(),
            ]);

            if ($bill->source === 'initial' && $bill->bpa_application_id) {
                $application = $bill->application()->first();
                if ($application?->bp_file_id) {
                    // Started from an indexed / commissioned BP file: it has its number, so on to the Director (Arch.).
                    app(CommissionBpFile::class)->forward($application, $officer);
                } else {
                    $bill->application()->update(['status' => 'Paid, awaiting commissioning', 'stage' => BpaStage::ARCHITECTURE]);
                }
            }

            return $bill;
        });
    }
}
