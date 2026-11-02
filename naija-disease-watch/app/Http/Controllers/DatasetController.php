<?php

namespace App\Http\Controllers;

use App\Models\DiseaseReport;
use App\Services\ImportDiseaseReports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return to_route('datasets.index')->with('status', "Imported {$count} records. Existing matching months were updated.");
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open the CSV output stream.');
            }
            fputcsv($stream, ImportDiseaseReports::HEADER, escape: '');
            fclose($stream);
        }, 'disease-report-template.csv', ['Content-Type' => 'text/csv']);
    }
}
