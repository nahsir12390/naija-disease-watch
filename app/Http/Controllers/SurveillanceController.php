<?php

namespace App\Http\Controllers;

use App\Models\DiseaseReport;
use App\Models\SourceSync;
use App\Services\DiseaseReportRules;
use App\Services\SurveillanceData;
use App\Services\SurveillanceForecast;
use App\Services\SurveillanceMap;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveillanceController extends Controller
{
    public function __invoke(Request $request, SurveillanceData $data, SurveillanceForecast $forecasts, SurveillanceMap $maps): View|StreamedResponse
    {
        $filters = $request->validate([
            'state' => ['nullable', Rule::in(SurveillanceData::STATES)],
            'disease' => ['nullable', Rule::in(SurveillanceData::DISEASES)],
            'year' => ['nullable', 'integer', 'between:2000,'.now()->year],
            'mode' => ['nullable', Rule::in(['demo', 'imported'])],
            'frequency' => ['nullable', Rule::in(['weekly', 'monthly', 'fortnightly'])],
            'case_type' => ['nullable', Rule::in(DiseaseReportRules::CASE_TYPES)],
            'coverage' => ['nullable', Rule::in(['national', 'states'])],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);
        $demo = ($filters['mode'] ?? (DiseaseReport::exists() ? 'imported' : 'demo')) === 'demo';
        $disease = $filters['disease'] ?? '';
        $available = DiseaseReport::query()->when($disease !== '', fn ($query) => $query->where('disease', $disease));
        $defaultFrequency = (clone $available)->where('week', '>', 0)->where('week_end', 0)->exists() ? 'weekly' : ((clone $available)->where('week_end', '>', 0)->exists() ? 'fortnightly' : 'monthly');
        $frequency = $demo ? 'monthly' : ($filters['frequency'] ?? $defaultFrequency);
        $query = SurveillanceData::queryForFrequency($frequency)->when($disease !== '', fn ($query) => $query->where('disease', $disease));
        $years = $demo ? [2023, 2022] : (clone $query)->distinct()->orderByDesc('year')->pluck('year')->all();
        $year = (int) ($filters['year'] ?? ($years[0] ?? now()->year));
        $query->where('year', $year);
        $availableTypes = (clone $query)->distinct()->pluck('case_type')->all();
        $defaultType = collect(['confirmed', 'reported', 'suspected'])->first(fn (string $type): bool => in_array($type, $availableTypes, true)) ?? 'reported';
        $caseType = $demo ? 'reported' : ($filters['case_type'] ?? $defaultType);
        $query->where('case_type', $caseType);
        $coverage = $demo ? 'states' : ($filters['coverage'] ?? ((clone $query)->where('state', '!=', 'Nigeria')->exists() ? 'states' : 'national'));
        $state = $coverage === 'national' ? '' : ($filters['state'] ?? '');
        $disease = $filters['disease'] ?? '';
        $records = $data->records($state, $disease, $year, $demo, $frequency, $caseType, $coverage);
        $selection = ['mode' => $demo ? 'demo' : 'imported', 'frequency' => $frequency, 'case_type' => $caseType, 'coverage' => $coverage, 'state' => $state, 'disease' => $disease, 'year' => $year];

        if (($filters['export'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($records, $demo): void {
                $stream = fopen('php://output', 'w');
                if ($stream === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }
                fputcsv($stream, [$demo ? 'SYNTHETIC DEMONSTRATION DATA - not surveillance records' : 'Published records - blank outcomes mean not reported; use source links to verify'], escape: '');
                $columns = $demo ? ['state', 'disease', 'month', 'year', 'cases', 'deaths', 'recoveries'] : ['state', 'disease', 'month', 'week', 'week_end', 'year', 'case_type', 'cases', 'deaths', 'recoveries', 'source', 'source_url'];
                fputcsv($stream, $columns, escape: '');
                foreach ($records as $record) {
                    $values = array_map(fn (string $column) => $record[$column], $columns);
                    fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value, $values), escape: '');
                }
                fclose($stream);
            }, $demo ? 'naija-disease-watch-demo.csv' : 'naija-disease-watch-imported.csv', ['Content-Type' => 'text/csv']);
        }
        $periodCount = match ($frequency) {
            'weekly' => 53, 'fortnightly' => 26, default => 12
        };
        $periodLabels = collect(range(1, $periodCount))->mapWithKeys(fn (int $period): array => [$period => match ($frequency) {
            'weekly' => 'W'.$period, 'fortnightly' => 'W'.($period * 2 - 1).'–'.($period * 2), default => SurveillanceData::MONTHS[$period]
        }]);
        $grouped = $records->groupBy(fn (array $record): int => match ($frequency) {
            'weekly' => $record['week'], 'fortnightly' => intdiv($record['week'] + 1, 2), default => $record['month']
        });
        $monthly = collect(range(1, $periodCount))->mapWithKeys(fn (int $period): array => [$period => $grouped->has($period) ? $grouped[$period]->sum('cases') : null]);
        $states = $records->groupBy('state')->map(fn ($group) => $group->sum('cases'))->sortDesc();
        $diseases = $records->groupBy('disease')->map(fn ($group) => $group->sum('cases'));
        $forecast = $disease !== '' && ($state !== '' || $coverage === 'national') ? $forecasts->calculate($monthly->all()) : null;
        $map = $maps->regions();
        $lastUpdated = DiseaseReport::max('updated_at');
        $lastChecked = SourceSync::max('succeeded_at');

        return view('surveillance', compact('records', 'monthly', 'states', 'diseases', 'state', 'disease', 'year', 'demo', 'years', 'frequency', 'caseType', 'coverage', 'selection', 'periodCount', 'periodLabels', 'forecast', 'map', 'lastUpdated', 'lastChecked'));
    }
}
