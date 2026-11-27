<?php

namespace App\Http\Requests\Bpa;

use App\Services\Bpa\RecordPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    // A failed save reopens the same bill's pop-up.
    protected $errorBag = 'payment';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'receipt_no' => mb_strtoupper(trim((string) $this->input('receipt_no'))),
            'amount_paid' => str_replace([',', '₦', ' '], '', (string) $this->input('amount_paid')),
            'payment_channel_other' => is_string($this->input('payment_channel_other')) ? mb_strtoupper(trim($this->input('payment_channel_other'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'receipt_no' => ['required', 'string', 'max:100'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_channel' => ['required', Rule::in(RecordPayment::CHANNELS)],
            'payment_channel_other' => ['nullable', 'required_if:payment_channel,Other', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['receipt_no' => 'receipt number', 'paid_on' => 'payment date', 'payment_channel_other' => 'payment channel (specify)'];
    }
}
