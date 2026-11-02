<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdministrator extends Command
{
    protected $signature = 'app:promote-administrator {email : Email of an existing user}';

    protected $description = 'Grant dataset management access to an existing user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('No account found for that email. Register an account first.');

            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => true])->save();
        $this->info('Administrator access granted.');

        return self::SUCCESS;
    }
}
