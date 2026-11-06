<?php

namespace Tests\Feature;

use App\Livewire\Settings\DeleteUserForm;
use App\Models\ProjectSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_owner_can_activate_from_local_browser_only_once(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app['env'] = 'local';
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->get(route('dashboard'))->assertOk()->assertSee('Make this my administrator account');
        $this->post(route('administration.claim'))->assertRedirect(route('administration.index'));
        $this->assertTrue($first->fresh()->is_admin);
        $this->actingAs($second)->post(route('administration.claim'))->assertStatus(409);
        $this->assertFalse($second->fresh()->is_admin);
    }

    public function test_initial_owner_setup_is_unavailable_on_a_public_production_site(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app['env'] = 'production';
        $this->actingAs(User::factory()->create())->post(route('administration.claim'))->assertForbidden();
    }

    public function test_researchers_cannot_change_project_settings_or_roles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('administration.index'))->assertForbidden();
        $this->put(route('administration.role', $user), ['is_admin' => true])->assertForbidden();
        $this->put(route('administration.settings'), ['name' => 'Changed', 'sync_enabled' => true])->assertForbidden();
    }

    public function test_administrators_can_manage_other_users_but_cannot_revoke_their_own_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $this->actingAs($admin)->get(route('administration.index'))->assertOk();
        $this->put(route('administration.role', $user), ['is_admin' => 1])->assertRedirect();
        $this->assertTrue($user->fresh()->is_admin);
        $this->put(route('administration.role', $user), ['is_admin' => 0])->assertRedirect();
        $this->assertFalse($user->fresh()->is_admin);
        $this->put(route('administration.role', $admin), ['is_admin' => 0])->assertUnprocessable();
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_project_name_changes_apply_to_the_public_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->put(route('administration.settings'), ['name' => 'Nigeria Health Observatory', 'sync_enabled' => 0])->assertRedirect();
        $this->assertFalse(ProjectSetting::current()->sync_enabled);
        $this->get(route('home'))->assertOk()->assertSee('Nigeria Health Observatory');
    }

    public function test_last_administrator_cannot_delete_their_account(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        Livewire::test(DeleteUserForm::class)->set('password', 'password')->call('deleteUser')->assertHasErrors('password');
        $this->assertNotNull($user->fresh());
    }
}
