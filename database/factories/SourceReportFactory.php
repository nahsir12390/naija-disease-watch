<?php

namespace Database\Factories;

use App\Models\SourceReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SourceReport> */
class SourceReportFactory extends Factory
{
    public function definition(): array
    {
        $url = 'https://ncdc.gov.ng/themes/common/files/sitreps/'.fake()->unique()->uuid().'.pdf';

        return ['disease' => 'Lassa fever', 'title' => 'Lassa fever situation report, week 40', 'url' => $url, 'url_hash' => hash('sha256', $url), 'year' => 2026, 'week' => 40, 'status' => 'pending'];
    }
}
