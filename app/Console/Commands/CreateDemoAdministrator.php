<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateDemoAdministrator extends Command
{
    protected $signature = 'app:demo-admin';

    protected $description = 'Create an optional local demonstration administrator without replacing existing accounts';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Demo administrator creation is available only in local environments.');

            return self::FAILURE;
        }

        if (User::where('email', 'admin@example.com')->exists()) {
            $this->error('That account already exists. Its password and permissions have not been changed.');

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => 'Demo Administrator',
            'email' => 'admin@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_admin' => true,
        ])->save();

        $this->info('Local demo administrator created: admin@example.com');
        $this->info('Password: password');
        $this->warn('Use only for a local demonstration. Change the password before sharing a hosted application.');

        return self::SUCCESS;
    }
}
