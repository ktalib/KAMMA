<?php

namespace App\Http\Requests\Bpa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BP file indexing / commissioning / edit. Names and places are saved in capital letters (the BPA paper forms ask
 * for them); file numbers, batch, shelf and serial numbers keep the format typed (KLAES rule), trimmed.
 */
class StoreBpFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['bp_file_title', 'land_file_title', 'district', 'lga', 'location', 'land_use'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = mb_strtoupper($this->squash($this->input($field)));
            }
        }
        foreach (['land_file_number', 'plot_no', 'plot_size', 'registry', 'batch_no', 'shelf_location', 'serial_no', 'remarks'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = $this->squash($this->input($field));
            }
        }
        $clean['land_not_in_klaes'] = $this->boolean('land_not_in_klaes');
        $this->merge($clean);
    }

    public function rules(): array
    {
        $editing = $this->route('bpFile') !== null;
        $indexing = $this->routeIs('bpa.bp-files.store-indexed');

        return [
            'land_not_in_klaes' => ['boolean'],
            'klaes_file_indexing_id' => $editing ? ['prohibited'] : ['exclude_if:land_not_in_klaes,true', 'required', 'integer'],
            'land_file_number' => $editing ? ['prohibited'] : ['exclude_unless:land_not_in_klaes,true', 'required', 'string', 'max:100'],
            'land_file_title' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:500'],
            'plot_no' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:150'],
            'district' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:150'],
            'lga' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:150'],
            'location' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:500'],
            'land_use' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:100'],
            'plot_size' => ['exclude_unless:land_not_in_klaes,true', 'nullable', 'string', 'max:100'],

            'bp_year' => $indexing ? ['required', 'integer', 'min:1900', 'max:'.now()->format('Y')] : ['prohibited'],
            'bp_serial' => $indexing ? ['required', 'integer', 'min:1', 'max:999999'] : ['prohibited'],

            'bp_zone_id' => ['required', 'integer'],
            'bp_file_title' => ['nullable', 'string', 'max:500'],
            'registry' => ['nullable', 'string', 'max:150'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'shelf_location' => ['nullable', 'string', 'max:150'],
            'serial_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'klaes_file_indexing_id' => 'land file',
            'land_file_number' => 'land file number',
            'bp_year' => 'BP year',
            'bp_serial' => 'BP serial number',
            'bp_zone_id' => 'zone',
            'bp_file_title' => 'BP file title',
        ];
    }

    public function messages(): array
    {
        return [
            'klaes_file_indexing_id.required' => 'Pick the land file from KLAES first (or tick "Land file is not in KLAES").',
            'bp_zone_id.required' => 'Pick the zone (or Other if it is not known yet).',
        ];
    }

    private function squash(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
