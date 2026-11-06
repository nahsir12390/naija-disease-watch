<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ProvisionAdministrator extends Command
{
    protected $signature = 'app:provision-administrator';

    protected $description = 'Create the first deployment administrator from environment configuration';

    public function handle(): int
    {
        $email = config('deployment.admin_email');
        $password = config('deployment.admin_password');

        if (! $email && ! $password) {
            $this->info('Deployment administrator provisioning is not configured.');

            return self::SUCCESS;
        }

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12'],
        ]);
        if ($validator->fails()) {
            $this->error('Set a valid DEPLOY_ADMIN_EMAIL and DEPLOY_ADMIN_PASSWORD of at least 12 characters.');

            return self::FAILURE;
        }

        if (User::where('is_admin', true)->exists()) {
            $this->info('An administrator already exists; no accounts were changed.');

            return self::SUCCESS;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('The configured email belongs to an existing account. No permissions were changed.');

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();
        $this->info('Deployment administrator created.');

        return self::SUCCESS;
    }
}
