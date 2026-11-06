<?php

namespace App\Http\Controllers;

use App\Jobs\SyncNcdcReportsJob;
use App\Models\DiseaseReport;
use App\Models\SourceReport;
use App\Models\SourceSync;
use App\Services\DiseaseReportRules;
use App\Services\ExtractReportText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SourceReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,approved,dismissed']])['status'] ?? 'pending';

        return view('sources.index', ['reports' => SourceReport::where('status', $status)->latest('year')->orderByDesc('week')->paginate(15)->withQueryString(), 'syncs' => SourceSync::all(), 'status' => $status]);
    }

    public function sync(): RedirectResponse
    {
        SyncNcdcReportsJob::dispatch();

        return to_route('sources.index')->with('status', 'NCDC check queued. New reports and draft text will appear after the background check finishes. Refresh this page to see the latest results.');
    }

    public function show(SourceReport $sourceReport, ExtractReportText $extractor): View
    {
        if ($sourceReport->extracted_text !== null) {
            $sourceReport->fill($extractor->period($sourceReport->extracted_text) ?? []);
        }

        return view('sources.review', ['report' => $sourceReport]);
    }

    public function extract(SourceReport $sourceReport, ExtractReportText $extractor): RedirectResponse
    {
        abort_unless($sourceReport->status === 'pending', 409, 'This report has already been reviewed.');
        $extractor->extract($sourceReport);

        return back()->with('status', $sourceReport->fresh()->extraction_error ?? 'Text extracted. Check the PDF before publishing the suggested figures.');
    }

    public function publish(Request $request, SourceReport $sourceReport): RedirectResponse
    {
        $request->validate(['confirmed_review' => ['accepted'], 'records' => ['required', 'array', 'min:1', 'max:74']]);
        $rows = [];
        $keys = [];
        foreach (array_values($request->input('records')) as $index => $input) {
            if (! is_array($input)) {
                throw ValidationException::withMessages(['records' => 'Each record must contain a complete set of fields.']);
            }
            $row = [...$input, 'disease' => $sourceReport->disease, 'month' => 0, 'week_end' => $input['week_end'] ?? 0, 'source' => mb_substr('NCDC: '.$sourceReport->title, 0, 255), 'source_url' => $sourceReport->url];
            $validator = Validator::make($row, DiseaseReportRules::rules());
            $validator->after(fn ($validator) => DiseaseReportRules::validateTotals($validator, $row));
            if ($validator->fails()) {
                throw ValidationException::withMessages(['records' => 'Row '.($index + 1).': '.$validator->errors()->first()]);
            }
            $row = $validator->validated();
            foreach (['month', 'week', 'week_end', 'year'] as $field) {
                $row[$field] = (int) $row[$field];
            }
            if ((int) $row['week'] < 1) {
                throw ValidationException::withMessages(['records' => 'An NCDC weekly report requires a week number.']);
            }
            $key = implode('|', array_map(fn (string $field) => $row[$field], DiseaseReportRules::KEY));
            if (isset($keys[$key])) {
                throw ValidationException::withMessages(['records' => 'Duplicate locations, periods and case classifications in this review.']);
            }
            $keys[$key] = true;
            if ($rows !== [] && ($rows[0]['week'] !== $row['week'] || $rows[0]['year'] !== $row['year'] || $rows[0]['week_end'] !== $row['week_end'])) {
                throw ValidationException::withMessages(['records' => 'All rows reviewed from one weekly report must use the same year and week.']);
            }
            $rows[] = [...$row, 'uploaded_by' => $request->user()->id, 'source_report_id' => $sourceReport->id];
        }
        DB::transaction(function () use ($sourceReport, $rows, $request): void {
            $locked = SourceReport::whereKey($sourceReport->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 409, 'This report has already been reviewed.');
            foreach ($rows as $row) {
                DiseaseReport::updateOrCreate(array_intersect_key($row, array_flip(DiseaseReportRules::KEY)), $row);
            }
            $locked->update(['year' => $rows[0]['year'], 'week' => $rows[0]['week'], 'week_end' => $rows[0]['week_end'], 'status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });

        return to_route('sources.index')->with('status', 'Reviewed records published. The public dashboard has been updated.');
    }

    public function dismiss(Request $request, SourceReport $sourceReport): RedirectResponse
    {
        $changed = SourceReport::whereKey($sourceReport->id)->where('status', 'pending')->update(['status' => 'dismissed', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        abort_unless($changed === 1, 409, 'This report has already been reviewed.');

        return to_route('sources.index')->with('status', 'Report dismissed. No figures were published.');
    }
}
