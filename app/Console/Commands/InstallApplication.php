<?php

namespace App\Console\Commands;

use App\Models\ProjectSetting;
use Database\Seeders\VerifiedReportSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallApplication extends Command
{
    protected $signature = 'app:install {--without-starter-data : Start with an empty dataset}';

    protected $description = 'Prepare the database and project settings without replacing existing keys or records';

    public function handle(): int
    {
        if (! config('app.key') && $this->call('key:generate', ['--force' => true, '--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        if (config('database.default') === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if (! is_string($path) || $path === ':memory:') {
                $this->error('Installation needs a persistent SQLite database path.');

                return self::FAILURE;
            }
            if (! File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, '');
            }
        }
        if ($this->call('migrate', ['--force' => true, '--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        ProjectSetting::current();
        if (! $this->option('without-starter-data')) {
            if ($this->call('db:seed', ['--class' => VerifiedReportSeeder::class, '--force' => true, '--no-interaction' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        $this->info('Installation complete. Start composer run dev, register an account, and activate administrator access from your local workspace.');

        return self::SUCCESS;
    }
}
