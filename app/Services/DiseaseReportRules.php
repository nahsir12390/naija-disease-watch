<?php

namespace App\Services;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DiseaseReportRules
{
    public const CASE_TYPES = ['reported', 'confirmed', 'suspected'];

    public const KEY = ['state', 'disease', 'year', 'month', 'week', 'week_end', 'case_type'];

    /** @return array<string, array<mixed>> */
    public static function rules(): array
    {
        return [
            'state' => ['required', Rule::in([...SurveillanceData::STATES, 'Nigeria'])],
            'disease' => ['required', Rule::in(SurveillanceData::DISEASES)],
            'month' => ['required', 'integer', 'between:0,12'],
            'week' => ['required', 'integer', 'between:0,53'],
            'week_end' => ['required', 'integer', 'between:0,53'],
            'year' => ['required', 'integer', 'between:2000,'.now()->year],
            'case_type' => ['required', Rule::in(self::CASE_TYPES)],
            'cases' => ['required', 'integer', 'between:0,100000000'],
            'deaths' => ['nullable', 'integer', 'between:0,100000000'],
            'recoveries' => ['nullable', 'integer', 'between:0,100000000'],
            'source' => ['required', 'string', 'max:255'],
            'source_url' => ['required', 'url:http,https', 'max:2048'],
        ];
    }

    /** @param array<string, mixed> $row */
    public static function validateTotals(Validator $validator, array $row): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        if (((int) $row['month'] > 0) === ((int) $row['week'] > 0)) {
            $validator->errors()->add('month', 'Specify a month or an epidemiological week, using zero for the unused field.');
        }
        $end = (int) ($row['week_end'] ?? 0);
        if ($end > 0 && ((int) $row['week'] < 1 || $end !== (int) $row['week'] + 1 || (int) $row['week'] % 2 !== 1)) {
            $validator->errors()->add('week_end', 'A two-week record must cover an odd-numbered start week and the following week, within one year.');
        }
    }
}
