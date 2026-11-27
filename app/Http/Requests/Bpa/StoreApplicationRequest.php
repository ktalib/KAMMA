<?php

namespace App\Http\Requests\Bpa;

use App\Support\Bpa\BpaForm;
use App\Support\LandUse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The paper form asks for CAPITAL LETTERS, so names, places and addresses are saved in upper case.
     * Emails are lower case; phone numbers and identifiers keep their format (KLAES rule).
     */
    protected function prepareForValidation(): void
    {
        $upper = ['developer_name', 'cadastral_zone', 'cadastral_zone_plot_no', 'street_name', 'dwelling_plot_floor',
            'dwelling_unit', 'contact_address', 'submitted_by_name', 'submission_type_other', 'documents_other', 'proposed_use_other'];
        $clean = [];

        foreach ($upper as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = mb_strtoupper($this->squash($this->input($field)));
            }
        }
        foreach (['developer_email', 'representative_email'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = mb_strtolower(trim($this->input($field)));
            }
        }
        foreach (['developer_phone', 'representative_phone'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim($this->input($field));
            }
        }

        $this->merge($clean);
    }

    public function rules(): array
    {
        $maxKb = (int) config('kamma.bpa.upload_max_kb');
        $types = implode(',', array_merge(BpaForm::PLAN_EXTENSIONS, BpaForm::CAD_EXTENSIONS));

        return [
            // From a BP file (BP Files > Start application) the land file comes with it.
            'bp_file_id' => ['nullable', 'integer'],
            'klaes_file_indexing_id' => ['required_without:bp_file_id', 'nullable', 'integer'],
            'application_type' => ['nullable', Rule::in(BpaForm::APPLICATION_TYPES)],
            'proposed_use' => ['required', Rule::in([...array_keys(LandUse::PROPOSED), LandUse::OTHER])],
            'proposed_use_other' => ['nullable', 'required_if:proposed_use,'.LandUse::OTHER, 'string', 'max:200'],

            'developer_name' => ['required', 'string', 'max:300'],
            'cadastral_zone' => ['nullable', 'string', 'max:100'],
            'cadastral_zone_plot_no' => ['nullable', 'string', 'max:150'],
            'street_name' => ['nullable', 'string', 'max:200'],
            'dwelling_plot_floor' => ['nullable', 'string', 'max:100'],
            'dwelling_unit' => ['nullable', 'string', 'max:100'],
            'contact_address' => ['required', 'string', 'max:500'],
            'developer_phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'developer_email' => ['nullable', 'email', 'max:150'],
            'representative_phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'representative_email' => ['nullable', 'email', 'max:150'],

            'submission_type' => ['required', Rule::in(BpaForm::SUBMISSION_TYPES)],
            'submission_type_other' => ['nullable', 'required_if:submission_type,'.BpaForm::OTHER, 'string', 'max:200'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['distinct', Rule::in(BpaForm::DOCUMENTS)],
            'documents_other' => [Rule::requiredIf(in_array(BpaForm::OTHER, (array) $this->input('documents', []), true)), 'nullable', 'string', 'max:300'],
            'drawings' => ['nullable', 'array'],
            'drawings.*' => ['distinct', Rule::in(BpaForm::DRAWINGS)],

            'plan_received_as' => ['required', Rule::in(BpaForm::PLAN_RECEIVED_AS)],
            'plan_files' => ['required', 'array', 'min:1', 'max:10'],
            'plan_files.*' => ['file', 'extensions:'.$types, 'max:'.$maxKb],

            'number_of_plots' => ['required', 'integer', 'min:1', 'max:100'],
            'submitted_by_name' => ['nullable', 'string', 'max:300'],
            'submitted_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'klaes_file_indexing_id' => 'land file',
            'proposed_use_other' => 'proposed use (specify)',
            'submission_type_other' => 'type of submission (specify)',
            'documents_other' => 'other document (specify)',
            'plan_files' => 'building plan',
            'plan_files.*' => 'building plan file',
            'developer_phone' => "developer's mobile no.",
            'representative_phone' => "representative's mobile no.",
        ];
    }

    public function messages(): array
    {
        return [
            'klaes_file_indexing_id.required_without' => 'Pick the land file from KLAES first.',
            'plan_files.required' => 'Upload the building plan: the scanned booklet, a PDF, or the DXF/DWG drawing.',
            'plan_files.*.extensions' => 'A building plan file must be PDF, JPG, PNG, TIFF, DXF or DWG.',
        ];
    }

    private function squash(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
