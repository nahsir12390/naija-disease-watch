<?php

namespace App\Services;

use App\Models\DiseaseReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @phpstan-type Record array{state: string, disease: string, month: int, year: int, cases: int, deaths: int|null, recoveries: int|null, source: string, source_url: string, week: int, week_end: int, case_type: string} */
class SurveillanceData
{
    public const STATES = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];

    public const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public const DISEASES = ['Cholera', 'Lassa fever', 'COVID-19'];

    /** @return Collection<int, covariant Record> */
    public function records(string $state = '', string $disease = '', int $year = 2023, bool $demo = true, string $frequency = 'monthly', string $caseType = 'reported', string $coverage = 'states'): Collection
    {
        if (! $demo) {
            return self::queryForFrequency($frequency)->where('year', $year)->where('case_type', $caseType)
                ->when($coverage === 'national', fn ($query) => $query->where('state', 'Nigeria'), fn ($query) => $query->where('state', '!=', 'Nigeria'))
                ->when($state !== '', fn ($query) => $query->where('state', $state))
                ->when($disease !== '', fn ($query) => $query->where('disease', $disease))
                ->orderBy('state')->orderBy('disease')->orderBy('month')->orderBy('week')
                ->get()->map(fn (DiseaseReport $report): array => [
                    'state' => $report->state, 'disease' => $report->disease, 'month' => $report->month,
                    'year' => $report->year, 'cases' => $report->cases, 'deaths' => $report->deaths,
                    'recoveries' => $report->recoveries, 'source' => $report->source, 'source_url' => $report->source_url,
                    'week' => $report->week, 'week_end' => $report->week_end, 'case_type' => $report->case_type,
                ]);
        }
        $records = collect();
        foreach (self::STATES as $index => $name) {
            foreach (self::DISEASES as $diseaseIndex => $diseaseName) {
                for ($month = 1; $month <= 12; $month++) {
                    $cases = 15 + (($index * 47 + $month * 31 + $diseaseIndex * 83 + $year) % 230);
                    $deaths = (int) floor($cases * [0.018, 0.065, 0.009][$diseaseIndex]);
                    $records->push(['state' => $name, 'disease' => $diseaseName, 'month' => $month, 'year' => $year, 'cases' => $cases, 'deaths' => $deaths, 'recoveries' => (int) floor(($cases - $deaths) * 0.86), 'source' => 'Synthetic demonstration data', 'source_url' => '', 'week' => 0, 'week_end' => 0, 'case_type' => 'reported']);
                }
            }
        }

        return $records->filter(fn (array $record): bool => ($state === '' || $record['state'] === $state) && ($disease === '' || $record['disease'] === $disease))->values();
    }

    /** @return Builder<DiseaseReport> */
    public static function queryForFrequency(string $frequency): Builder
    {
        return match ($frequency) {
            'weekly' => DiseaseReport::where('week', '>', 0)->where('week_end', 0),
            'fortnightly' => DiseaseReport::where('week', '>', 0)->where('week_end', '>', 0),
            default => DiseaseReport::where('month', '>', 0),
        };
    }
}
