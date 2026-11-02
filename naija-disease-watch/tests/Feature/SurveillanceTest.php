<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveillanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_dashboard_discloses_demo_data(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('synthetic records')->assertViewHas('states', fn ($states) => $states->count() === 37);
    }

    public function test_filters_apply_to_all_records(): void
    {
        $this->get(route('home', ['state' => 'Lagos', 'disease' => 'Cholera', 'year' => 2022]))
            ->assertOk()
            ->assertViewHas('records', fn ($records) => $records->count() === 12 && $records->every(fn ($record) => $record['state'] === 'Lagos' && $record['disease'] === 'Cholera' && $record['year'] === 2022));
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->getJson(route('home', ['state' => 'Unknown', 'year' => 2099]))->assertUnprocessable()->assertJsonValidationErrors(['state', 'year']);
    }

    public function test_csv_contains_only_filtered_records_and_demo_disclosure(): void
    {
        $response = $this->get(route('home', ['state' => 'Lagos', 'disease' => 'Cholera', 'export' => 'csv']));
        $response->assertOk()->assertDownload('naija-disease-watch-demo.csv');
        $content = $response->streamedContent();
        $this->assertStringContainsString('SYNTHETIC DEMONSTRATION DATA', $content);
        $this->assertStringContainsString('Lagos,Cholera,1,2023', $content);
        $this->assertStringNotContainsString('Abia', $content);
        $this->assertCount(14, array_filter(explode("\n", trim($content))));
    }
}
