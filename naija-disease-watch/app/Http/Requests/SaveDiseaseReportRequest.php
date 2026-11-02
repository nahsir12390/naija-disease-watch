<?php

namespace App\Http\Requests;

use App\Services\DiseaseReportRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveDiseaseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-datasets') ?? false;
    }

    public function rules(): array
    {
        return DiseaseReportRules::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => DiseaseReportRules::validateTotals($validator, $this->all()));
    }
}
