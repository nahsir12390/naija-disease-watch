<?php

namespace App\Console\Commands;

use App\Jobs\SyncNcdcReportsJob;
use App\Models\ProjectSetting;
use Illuminate\Console\Command;

class SyncNcdcReports extends Command
{
    protected $signature = 'app:sync-ncdc-reports';

    protected $description = 'Discover new official NCDC reports for administrator review';

    public function handle(): int
    {
        if (! ProjectSetting::current()->sync_enabled) {
            $this->info('Automatic NCDC checks are disabled in project settings.');

            return self::SUCCESS;
        }
        SyncNcdcReportsJob::dispatch();
        $this->info('NCDC discovery and draft preparation queued.');

        return self::SUCCESS;
    }
}
