<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        DB::transaction(function () use ($logout): void {
            $administrators = User::where('is_admin', true)->lockForUpdate()->get();
            if (Auth::user()->is_admin && $administrators->count() === 1) {
                throw ValidationException::withMessages(['password' => 'Assign another administrator before deleting your account.']);
            }
            tap(Auth::user(), $logout(...))->delete();
        });

        $this->redirect('/', navigate: true);
    }
}
