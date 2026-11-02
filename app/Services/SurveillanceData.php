<?php

namespace App\Services;

use App\Models\DiseaseReport;
use Illuminate\Support\Collection;

class SurveillanceData
{
    public const STATES = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];

    public const DISEASES = ['Cholera', 'Lassa fever', 'COVID-19'];

    /** @return Collection<int, array{state: string, disease: string, month: int, year: int, cases: int, deaths: int, recoveries: int, source: string, source_url: string}> */
    public function records(string $state = '', string $disease = '', int $year = 2023, bool $demo = true): Collection
    {
        if (! $demo) {
            return DiseaseReport::query()->where('year', $year)
                ->when($state !== '', fn ($query) => $query->where('state', $state))
                ->when($disease !== '', fn ($query) => $query->where('disease', $disease))
                ->orderBy('state')->orderBy('disease')->orderBy('month')
                ->get(['state', 'disease', 'month', 'year', 'cases', 'deaths', 'recoveries', 'source', 'source_url'])
                ->map(fn (DiseaseReport $report): array => ['state' => $report->state, 'disease' => $report->disease, 'month' => $report->month, 'year' => $report->year, 'cases' => $report->cases, 'deaths' => $report->deaths, 'recoveries' => $report->recoveries, 'source' => $report->source, 'source_url' => $report->source_url]);
        }

        $records = collect();

        foreach (self::STATES as $index => $name) {
            foreach (self::DISEASES as $diseaseIndex => $diseaseName) {
                for ($month = 1; $month <= 12; $month++) {
                    $cases = 15 + (($index * 47 + $month * 31 + $diseaseIndex * 83 + $year) % 230);
                    $deaths = (int) floor($cases * [0.018, 0.065, 0.009][$diseaseIndex]);
                    $records->push(['state' => $name, 'disease' => $diseaseName, 'month' => $month, 'year' => $year, 'cases' => $cases, 'deaths' => $deaths, 'recoveries' => (int) floor(($cases - $deaths) * 0.86), 'source' => 'Synthetic demonstration data', 'source_url' => '']);
                }
            }
        }

        return $records->filter(fn (array $record): bool => ($state === '' || $record['state'] === $state) && ($disease === '' || $record['disease'] === $disease))->values();
    }
}
