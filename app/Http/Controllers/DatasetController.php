<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDiseaseReportRequest;
use App\Models\DiseaseReport;
use App\Services\DiseaseReportRules;
use App\Services\ImportDiseaseReports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetController extends Controller
{
    public function index(): View
    {
        return view('datasets', ['reports' => DiseaseReport::query()->latest('updated_at')->paginate(20), 'recordCount' => DiseaseReport::count()]);
    }

    public function store(Request $request, ImportDiseaseReports $importer): RedirectResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'], 'source' => ['required', 'string', 'max:255', 'not_regex:/^[=+@-]/'], 'source_url' => ['required', 'url:http,https', 'max:2048']]);
        $count = $importer->import($request->file('file'), $validated['source'], $validated['source_url'], $request->user()->id);

        return to_route('datasets.index')->with('status', "Imported {$count} records. Existing matching periods and case classifications were updated.");
    }

    public function create(): View
    {
        return view('records.edit', ['record' => new DiseaseReport(['month' => 0, 'week' => 1, 'year' => now()->year])]);
    }

    public function edit(DiseaseReport $diseaseReport): View
    {
        return view('records.edit', ['record' => $diseaseReport]);
    }

    public function save(SaveDiseaseReportRequest $request): RedirectResponse
    {
        $row = $request->validated();
        if (DiseaseReport::where(array_intersect_key($row, array_flip(DiseaseReportRules::KEY)))->exists()) {
            throw ValidationException::withMessages(['cases' => 'A matching record already exists. Edit that record instead.']);
        }
        DiseaseReport::create([...$row, 'uploaded_by' => $request->user()->id]);

        return to_route('datasets.index')->with('status', 'Disease record saved.');
    }

    public function update(SaveDiseaseReportRequest $request, DiseaseReport $diseaseReport): RedirectResponse
    {
        $row = $request->validated();
        if (DiseaseReport::where(array_intersect_key($row, array_flip(DiseaseReportRules::KEY)))->whereKeyNot($diseaseReport->id)->exists()) {
            throw ValidationException::withMessages(['cases' => 'Another record already exists for this location, period and case classification.']);
        }
        $diseaseReport->update([...$row, 'uploaded_by' => $request->user()->id]);

        return to_route('datasets.index')->with('status', 'Disease record updated.');
    }

    public function destroy(DiseaseReport $diseaseReport): RedirectResponse
    {
        $diseaseReport->delete();

        return to_route('datasets.index')->with('status', 'Disease record deleted.');
    }

    public function template(Request $request): StreamedResponse
    {
        $frequency = $request->query('frequency');
        $header = match ($frequency) {
            'weekly' => ImportDiseaseReports::WEEKLY_HEADER, 'fortnightly' => ImportDiseaseReports::FORTNIGHTLY_HEADER, default => ImportDiseaseReports::HEADER
        };

        return response()->streamDownload(function () use ($header): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open the CSV output stream.');
            }
            fputcsv($stream, $header, escape: '');
            fclose($stream);
        }, 'disease-report-template.csv', ['Content-Type' => 'text/csv']);
    }
}
