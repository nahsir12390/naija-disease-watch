<?php

namespace App\Http\Requests;

use App\Services\DiseaseReportRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveDiseaseReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['week_end' => $this->input('week_end', 0)]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-datasets') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return DiseaseReportRules::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => DiseaseReportRules::validateTotals($validator, $this->all()));
    }
}
