<?php

namespace Tests\Feature;

use App\Models\DiseaseReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DatasetManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function upload(string $rows): array
    {
        return ['file' => UploadedFile::fake()->createWithContent('reports.csv', "state,disease,month,year,cases,deaths,recoveries\n".$rows), 'source' => 'Test source', 'source_url' => 'https://example.org/report'];
    }

    public function test_dataset_management_requires_an_administrator(): void
    {
        $this->get(route('datasets.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('datasets.index'))->assertForbidden();
        $this->post(route('datasets.store'), $this->upload('Lagos,Cholera,1,2023,100,5,80'))->assertForbidden();
        $this->get(route('datasets.template'))->assertForbidden();
        $this->actingAs($this->administrator())->get(route('datasets.index'))->assertOk();
    }

    public function test_valid_import_is_persisted_and_drives_public_dashboard(): void
    {
        $this->actingAs($this->administrator())->post(route('datasets.store'), $this->upload('Lagos,Cholera,1,2023,100,5,80'))
            ->assertRedirect(route('datasets.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disease_reports', ['state' => 'Lagos', 'cases' => 100, 'source' => 'Test source']);
        $this->get(route('home'))->assertOk()->assertViewHas('demo', false)->assertViewHas('records', fn ($records) => $records->count() === 1 && $records->sum('cases') === 100)->assertSee('Test source');
        $this->get(route('home', ['mode' => 'demo']))->assertOk()->assertViewHas('demo', true);
        $response = $this->get(route('home', ['mode' => 'imported', 'export' => 'csv']));
        $response->assertDownload('naija-disease-watch-imported.csv');
        $this->assertStringContainsString('https://example.org/report', $response->streamedContent());
    }

    public function test_invalid_later_row_prevents_partial_import(): void
    {
        $this->actingAs($this->administrator())->post(route('datasets.store'), $this->upload("Lagos,Cholera,1,2023,100,5,80\nAbia,Cholera,2,2023,10,9,8"))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('disease_reports', 0);
    }

    public function test_matching_month_is_updated_and_duplicate_file_rows_are_rejected(): void
    {
        DiseaseReport::factory()->create();
        $this->actingAs($this->administrator())->post(route('datasets.store'), $this->upload('Lagos,Cholera,1,2023,200,5,180'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('disease_reports', 1);
        $this->assertDatabaseHas('disease_reports', ['cases' => 200]);
        $this->post(route('datasets.store'), $this->upload("Abia,Cholera,1,2023,100,5,80\nAbia,Cholera,01,2023,100,5,80"))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('disease_reports', 1);
    }

    public function test_unknown_states_and_headers_are_rejected(): void
    {
        $this->actingAs($this->administrator())->post(route('datasets.store'), $this->upload('Unknown,Cholera,1,2023,100,5,80'))->assertSessionHasErrors('file');
        $payload = $this->upload('');
        $payload['file'] = UploadedFile::fake()->createWithContent('bad.csv', 'unexpected,header');
        $this->post(route('datasets.store'), $payload)->assertSessionHasErrors('file');
        $this->assertDatabaseCount('disease_reports', 0);
    }

    public function test_empty_imported_selection_does_not_fall_back_to_demo(): void
    {
        $this->get(route('home', ['mode' => 'imported']))->assertOk()->assertViewHas('records', fn ($records) => $records->isEmpty())->assertSee('No matching records');
    }

    public function test_promotion_requires_an_existing_account(): void
    {
        $user = User::factory()->create();
        $this->artisan('app:promote-administrator', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);
        $this->artisan('app:promote-administrator', ['email' => 'missing@example.org'])->assertFailed();
    }

    public function test_admin_flag_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->fill(['is_admin' => true])->save();
        $this->assertFalse($user->fresh()->is_admin);
    }
}
