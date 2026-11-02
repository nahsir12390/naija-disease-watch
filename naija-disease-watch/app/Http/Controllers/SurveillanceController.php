<?php

namespace App\Http\Controllers;

use App\Models\DiseaseReport;
use App\Services\SurveillanceData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveillanceController extends Controller
{
    public function __invoke(Request $request, SurveillanceData $data): View|StreamedResponse
    {
        $filters = $request->validate([
            'state' => ['nullable', Rule::in(SurveillanceData::STATES)],
            'disease' => ['nullable', Rule::in(SurveillanceData::DISEASES)],
            'year' => ['nullable', 'integer', 'between:2000,'.now()->year],
            'mode' => ['nullable', Rule::in(['demo', 'imported'])],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);
        $state = $filters['state'] ?? '';
        $disease = $filters['disease'] ?? '';
        $hasImported = DiseaseReport::query()->exists();
        $demo = ($filters['mode'] ?? ($hasImported ? 'imported' : 'demo')) === 'demo';
        $years = $demo ? [2023, 2022] : DiseaseReport::query()->distinct()->orderByDesc('year')->pluck('year')->all();
        $year = (int) ($filters['year'] ?? ($years[0] ?? 2023));
        $records = $data->records($state, $disease, $year, $demo);

        if (($filters['export'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($records, $demo): void {
                $stream = fopen('php://output', 'w');
                if ($stream === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }
                fputcsv($stream, [$demo ? 'SYNTHETIC DEMONSTRATION DATA - not surveillance records' : 'ADMINISTRATOR-IMPORTED DATA - source attribution supplied by uploader'], escape: '');
                fputcsv($stream, ($demo ? ['State', 'Disease', 'Month', 'Year', 'Cases', 'Deaths', 'Recoveries'] : ['State', 'Disease', 'Month', 'Year', 'Cases', 'Deaths', 'Recoveries', 'Source', 'Source URL']), escape: '');
                foreach ($records as $record) {
                    fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/', $value) ? "'".$value : $value, array_values($demo ? array_slice($record, 0, 7) : $record)), escape: '');
                }
                fclose($stream);
            }, $demo ? 'naija-disease-watch-demo.csv' : 'naija-disease-watch-imported.csv', ['Content-Type' => 'text/csv']);
        }

        $monthly = collect(range(1, 12))->mapWithKeys(fn (int $month): array => [$month => $records->contains('month', $month) ? $records->where('month', $month)->sum('cases') : null]);
        $states = $records->groupBy('state')->map(fn ($group) => $group->sum('cases'))->sortDesc();
        $diseases = $records->groupBy('disease')->map(fn ($group) => $group->sum('cases'));

        return view('surveillance', compact('records', 'monthly', 'states', 'diseases', 'state', 'disease', 'year', 'demo', 'years'));
    }
}
