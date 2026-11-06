<?php

namespace App\Jobs;

use App\Models\SourceReport;
use App\Services\ExtractReportText;
use App\Services\NcdcReports;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncNcdcReportsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 240;

    public int $uniqueFor = 300;

    public int $tries = 1;

    public function handle(NcdcReports $reports, ExtractReportText $extractor): void
    {
        $reports->sync();
        foreach (array_keys(NcdcReports::SOURCES) as $disease) {
            $report = SourceReport::where('disease', $disease)->where('status', 'pending')->whereNull('extracted_text')->orderByDesc('year')->orderByDesc('week')->first();
            if ($report !== null) {
                $extractor->extract($report);
            }
        }
    }
}
