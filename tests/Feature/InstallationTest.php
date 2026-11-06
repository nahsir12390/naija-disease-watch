<?php

namespace Tests\Feature;

use App\Models\DiseaseReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databasePath = sys_get_temp_dir().'/ndw-install-'.Str::uuid().'.sqlite';
        config(['database.connections.sqlite.database' => $this->databasePath]);
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        foreach ([$this->databasePath, $this->databasePath.'-wal', $this->databasePath.'-shm'] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_fresh_install_creates_the_database_and_source_attributed_data_without_default_accounts(): void
    {
        $originalKey = config('app.key');
        $this->artisan('app:install')->assertSuccessful();
        $this->assertFileExists($this->databasePath);
        $this->assertSame($originalKey, config('app.key'));
        $this->assertSame(0, User::count());
        $this->assertGreaterThan(50, DiseaseReport::count());
        $this->get(route('home'))->assertOk()->assertViewHas('demo', false);
        $this->get(route('home', ['disease' => 'COVID-19']))->assertOk()->assertViewHas('frequency', 'fortnightly')->assertViewHas('records', fn ($records) => $records->where('state', '!=', 'Nigeria')->sum('cases') === 26);
        $this->get(route('home', ['disease' => 'Cholera']))->assertOk()->assertViewHas('caseType', 'suspected');
    }

    public function test_install_can_be_rerun_without_overwriting_data_or_keys(): void
    {
        $originalKey = config('app.key');
        $this->artisan('app:install')->assertSuccessful();
        $count = DiseaseReport::count();
        $record = DiseaseReport::firstOrFail();
        $record->update(['cases' => 999]);
        $this->artisan('app:install')->assertSuccessful();
        $this->assertSame($count, DiseaseReport::count());
        $this->assertSame(999, $record->fresh()->cases);
        $this->assertSame($originalKey, config('app.key'));
    }

    public function test_install_can_leave_the_dataset_empty(): void
    {
        $this->artisan('app:install', ['--without-starter-data' => true])->assertSuccessful();
        $this->assertSame(0, DiseaseReport::count());
        $this->get(route('home'))->assertOk()->assertViewHas('demo', true);
    }

    public function test_optional_demo_administrator_can_log_in_without_being_reset_on_rerun(): void
    {
        $this->artisan('app:install', ['--without-starter-data' => true])->assertSuccessful();
        $this->artisan('app:demo-admin')->assertSuccessful();
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $user->update(['password' => 'ChangedPassword456!']);
        $this->artisan('app:demo-admin')->assertFailed();
        $this->assertTrue(Hash::check('ChangedPassword456!', $user->fresh()->password));
    }

    public function test_demo_administrator_is_blocked_in_production(): void
    {
        $this->artisan('app:install', ['--without-starter-data' => true])->assertSuccessful();
        $this->app->instance('env', 'production');
        $this->artisan('app:demo-admin')->assertFailed();
        $this->assertSame(0, User::count());
    }
}
