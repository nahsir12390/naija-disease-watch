<?php

namespace Tests\Feature;

use App\Jobs\SyncNcdcReportsJob;
use App\Models\ProjectSetting;
use App\Models\SourceReport;
use App\Models\SourceSync;
use App\Models\User;
use App\Services\ExtractReportText;
use App\Services\NcdcReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NcdcReportsTest extends TestCase
{
    use RefreshDatabase;

    private function weeklyRow(array $attributes = []): array
    {
        return array_replace(['state' => 'Nigeria', 'week' => 40, 'year' => 2026, 'case_type' => 'confirmed', 'cases' => 3, 'deaths' => 1, 'recoveries' => null], $attributes);
    }

    public function test_manual_checks_run_in_the_background(): void
    {
        Queue::fake();
        Http::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('sources.sync'))->assertRedirect(route('sources.index'));
        Queue::assertPushed(SyncNcdcReportsJob::class);
        Http::assertNothingSent();
    }

    public function test_background_check_prepares_drafts_without_publishing(): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '.pdf')) {
                return Http::response('%PDF-test');
            }
            $name = md5($request->url());

            return Http::response('<a href="/themes/common/files/sitreps/'.$name.'.pdf" download="Report_031026_40.pdf">Week 40</a>');
        });
        Process::fake(['*' => Process::result(output: "Epi Week: 40 2026\nConfirmed cases\nCurrent week\n(week 40) 28 3 0 1 33.3%")]);
        (new SyncNcdcReportsJob)->handle(app(NcdcReports::class), app(ExtractReportText::class));
        $this->assertDatabaseCount('source_reports', 3);
        $this->assertSame(3, SourceReport::whereNotNull('extracted_text')->count());
        $this->assertDatabaseCount('disease_reports', 0);
    }

    public function test_discovery_uses_only_official_pdf_links_and_keeps_pending_reports_out_of_the_dashboard(): void
    {
        Http::preventStrayRequests();
        Http::fake(['ncdc.gov.ng/*' => Http::response('<table><a href="/themes/common/files/sitreps/report.pdf" download="Report_031026_40.pdf">Week 40</a><a href="https://evil.test/report.pdf">Bad</a><a href="//evil.test/report.pdf">Bad</a></table>')]);
        $service = app(NcdcReports::class);
        $this->assertSame(['new' => 1, 'failed' => 0], $service->sync());
        $this->assertSame(['new' => 0, 'failed' => 0], $service->sync());
        $this->assertDatabaseCount('source_reports', 1);
        $this->assertDatabaseHas('source_reports', ['status' => 'pending', 'year' => 2026, 'week' => 40]);
        $this->assertDatabaseCount('disease_reports', 0);
        $this->get(route('home', ['mode' => 'imported']))->assertOk()->assertSee('No matching records');
    }

    public function test_failed_source_check_preserves_previous_success_and_reports(): void
    {
        SourceReport::factory()->create();
        SourceSync::create(['disease' => 'Lassa fever', 'succeeded_at' => now()->subDay()]);
        Http::fake(['*' => Http::response('Unavailable', 503)]);
        $this->assertSame(3, app(NcdcReports::class)->sync()['failed']);
        $this->assertDatabaseCount('source_reports', 1);
        $this->assertNotNull(SourceSync::where('disease', 'Lassa fever')->first()->succeeded_at);
        $this->assertNotNull(SourceSync::where('disease', 'Lassa fever')->first()->error);
    }

    public function test_scheduled_sync_respects_the_setting(): void
    {
        ProjectSetting::current()->update(['sync_enabled' => false]);
        Http::fake();
        $this->artisan('app:sync-ncdc-reports')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_text_extraction_suggests_current_week_confirmed_figures_without_filling_missing_recoveries(): void
    {
        $report = SourceReport::factory()->create();
        Http::fake(['*' => Http::response('%PDF-test')]);
        Process::fake(['*' => Process::result(output: "Epi Week: 40 2026\nConfirmed cases\nCurrent week\n(week 40) 28 3 0 1 33.3%\nCumulative\n(week 40) 8414 1138 6 268 23.6%")]);
        app(ExtractReportText::class)->extract($report);
        $report->refresh();
        $this->assertSame(3, $report->suggestions['cases']);
        $this->assertSame(1, $report->suggestions['deaths']);
        $this->assertNull($report->suggestions['recoveries']);
        $this->assertDatabaseCount('disease_reports', 0);
    }

    public function test_unrecognized_pdf_does_not_generate_guessed_figures(): void
    {
        $report = SourceReport::factory()->create();
        Http::fake(['*' => Http::response('Not a PDF')]);
        app(ExtractReportText::class)->extract($report);
        $this->assertNotNull($report->fresh()->extraction_error);
        $this->assertNull(app(ExtractReportText::class)->suggest('Cumulative (week 40) 8414 1138 6 268 23.6%', 'Lassa fever'));
    }

    public function test_review_uses_the_actual_two_week_period_from_existing_extracted_text(): void
    {
        $report = SourceReport::factory()->create(['disease' => 'COVID-19', 'year' => 2023, 'week' => 8, 'extracted_text' => 'Epi Week 7 – 8: 13th – 26th February 2023']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('sources.show', $report))->assertOk()->assertViewHas('report', fn ($record) => $record->week === 7 && $record->week_end === 8);
        $this->assertSame(8, $report->fresh()->week);
    }

    public function test_only_administrators_can_review_sources(): void
    {
        $report = SourceReport::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('sources.index'))->assertForbidden();
        $this->post(route('sources.publish', $report), ['records' => [$this->weeklyRow()], 'confirmed_review' => 1])->assertForbidden();
    }

    public function test_review_requires_confirmation_and_publishes_once_with_official_source(): void
    {
        $report = SourceReport::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('sources.index'))->assertOk();
        $this->get(route('sources.show', $report))->assertOk();
        $this->post(route('sources.publish', $report), ['records' => [$this->weeklyRow()]])->assertSessionHasErrors('confirmed_review');
        $this->post(route('sources.publish', $report), ['records' => [$this->weeklyRow()], 'confirmed_review' => 1])->assertRedirect(route('sources.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('disease_reports', ['source_report_id' => $report->id, 'state' => 'Nigeria', 'week' => 40, 'month' => 0, 'cases' => 3, 'recoveries' => null]);
        $this->assertSame('approved', $report->fresh()->status);
        $this->post(route('sources.publish', $report), ['records' => [$this->weeklyRow()], 'confirmed_review' => 1])->assertStatus(409);
        $this->get(route('home'))->assertOk()->assertViewHas('frequency', 'weekly')->assertViewHas('coverage', 'national')->assertSee('Not reported');
    }

    public function test_invalid_later_review_row_does_not_partially_publish(): void
    {
        $report = SourceReport::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('sources.publish', $report), ['confirmed_review' => 1, 'records' => [$this->weeklyRow(), $this->weeklyRow(['state' => 'Unknown'])]])->assertSessionHasErrors('records');
        $this->assertDatabaseCount('disease_reports', 0);
        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_dismissing_a_report_does_not_publish_figures(): void
    {
        $report = SourceReport::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post(route('sources.dismiss', $report))->assertRedirect(route('sources.index'));
        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertDatabaseCount('disease_reports', 0);
    }
}
