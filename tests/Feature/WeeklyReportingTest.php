<?php

namespace Tests\Feature;

use App\Models\DiseaseReport;
use App\Models\User;
use App\Services\SurveillanceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WeeklyReportingTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $attributes = []): array
    {
        return array_replace(['state' => 'Lagos', 'disease' => 'Cholera', 'month' => 0, 'week' => 40, 'year' => 2026, 'case_type' => 'confirmed', 'cases' => 20, 'deaths' => null, 'recoveries' => null, 'source' => 'Test source', 'source_url' => 'https://example.org/report'], $attributes);
    }

    public function test_records_can_be_added_edited_and_deleted_through_the_website(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('datasets.create'))->assertOk();
        $this->post(route('records.store'), $this->row())->assertSessionHasNoErrors()->assertRedirect(route('datasets.index'));
        $record = DiseaseReport::firstOrFail();
        $this->get(route('records.edit', $record))->assertOk();
        $this->put(route('records.update', $record), $this->row(['cases' => 25]))->assertSessionHasNoErrors();
        $this->assertSame(25, $record->fresh()->cases);
        $this->delete(route('records.destroy', $record))->assertRedirect(route('datasets.index'));
        $this->assertDatabaseCount('disease_reports', 0);
    }

    public function test_monthly_weekly_national_and_case_classifications_are_never_summed_together(): void
    {
        DiseaseReport::factory()->create($this->row());
        DiseaseReport::factory()->create($this->row(['state' => 'Nigeria', 'cases' => 500]));
        DiseaseReport::factory()->create($this->row(['case_type' => 'suspected', 'cases' => 80]));
        DiseaseReport::factory()->create($this->row(['week' => 0, 'month' => 10, 'cases' => 1000]));
        $this->get(route('home', ['mode' => 'imported', 'frequency' => 'weekly', 'case_type' => 'confirmed', 'coverage' => 'states']))->assertOk()->assertViewHas('records', fn ($records) => $records->sum('cases') === 20)->assertViewHas('monthly', fn ($series) => $series[40] === 20 && $series[39] === null);
        $this->get(route('home', ['mode' => 'imported', 'coverage' => 'national']))->assertOk()->assertViewHas('records', fn ($records) => $records->sum('cases') === 500);
    }

    public function test_weekly_csv_supports_blank_outcomes_and_preserves_periods(): void
    {
        $file = UploadedFile::fake()->createWithContent('weekly.csv', "state,disease,week,year,case_type,cases,deaths,recoveries\nNigeria,Lassa fever,40,2026,confirmed,3,1,\n");
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('datasets.store'), ['file' => $file, 'source' => 'Test source', 'source_url' => 'https://example.org/report'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disease_reports', ['state' => 'Nigeria', 'week' => 40, 'month' => 0, 'case_type' => 'confirmed', 'recoveries' => null]);
        $this->get(route('datasets.template', ['frequency' => 'weekly']))->assertDownload();
    }

    public function test_invalid_periods_and_duplicate_records_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('records.store'), $this->row(['month' => 1]))->assertSessionHasErrors('month');
        $this->post(route('records.store'), $this->row(['week' => 54]))->assertSessionHasErrors('week');
        $this->post(route('records.store'), $this->row())->assertSessionHasNoErrors();
        $this->post(route('records.store'), $this->row())->assertSessionHasErrors('cases');
        $this->assertDatabaseCount('disease_reports', 1);
    }

    public function test_period_outcomes_can_relate_to_cases_from_earlier_periods(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('records.store'), $this->row(['cases' => 1, 'deaths' => 2, 'recoveries' => 5]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disease_reports', ['cases' => 1, 'deaths' => 2, 'recoveries' => 5]);
    }

    public function test_two_week_records_are_not_counted_as_single_week_records(): void
    {
        DiseaseReport::factory()->create($this->row(['week' => 7, 'week_end' => 8, 'disease' => 'COVID-19', 'year' => 2023, 'cases' => 26]));
        $this->get(route('home', ['disease' => 'COVID-19']))->assertOk()->assertViewHas('frequency', 'fortnightly')->assertViewHas('monthly', fn ($series) => $series[4] === 26 && $series[3] === null);
        $this->get(route('home', ['mode' => 'imported', 'frequency' => 'weekly', 'year' => 2023]))->assertOk()->assertViewHas('records', fn ($records) => $records->isEmpty());
    }

    public function test_two_week_csv_preserves_the_interval_and_rejects_unsupported_ranges(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $file = UploadedFile::fake()->createWithContent('two-weeks.csv', "state,disease,week,week_end,year,case_type,cases,deaths,recoveries\nNigeria,COVID-19,7,8,2023,confirmed,26,0,19\n");
        $this->post(route('datasets.store'), ['file' => $file, 'source' => 'NCDC report', 'source_url' => 'https://example.org/report'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disease_reports', ['week' => 7, 'week_end' => 8, 'cases' => 26, 'recoveries' => 19]);
        $this->post(route('records.store'), $this->row(['week' => 7, 'week_end' => 10]))->assertSessionHasErrors('week_end');
        $this->get(route('datasets.template', ['frequency' => 'fortnightly']))->assertDownload();
    }

    public function test_state_map_contains_all_states_and_fct(): void
    {
        $this->get(route('home'))->assertOk()->assertViewHas('map', fn ($map) => count($map['regions']) === 37 && count(array_diff(SurveillanceData::STATES, array_column($map['regions'], 'name'))) === 0);
    }
}
