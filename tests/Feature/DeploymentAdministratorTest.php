<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeploymentAdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_can_create_an_administrator_without_logging_the_password(): void
    {
        config(['deployment.admin_email' => 'owner@example.com', 'deployment.admin_password' => 'PrivateDemoPassword123!']);
        $this->artisan('app:provision-administrator')->assertSuccessful();
        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('PrivateDemoPassword123!', $user->password));
        $this->post('/login', ['email' => $user->email, 'password' => 'PrivateDemoPassword123!'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        config(['deployment.admin_password' => 'DifferentPassword456!']);
        $this->artisan('app:provision-administrator')->assertSuccessful();
        $this->assertTrue(Hash::check('PrivateDemoPassword123!', $user->fresh()->password));
    }

    public function test_provisioning_does_not_promote_an_existing_non_administrator(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        config(['deployment.admin_email' => $user->email, 'deployment.admin_password' => 'PrivateDemoPassword123!']);
        $this->artisan('app:provision-administrator')->assertFailed();
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_provisioning_rejects_missing_or_short_passwords(): void
    {
        config(['deployment.admin_email' => 'owner@example.com', 'deployment.admin_password' => 'password']);
        $this->artisan('app:provision-administrator')->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_provisioning_is_optional(): void
    {
        config(['deployment.admin_email' => null, 'deployment.admin_password' => null]);
        $this->artisan('app:provision-administrator')->assertSuccessful();
        $this->assertSame(0, User::count());
    }
}
