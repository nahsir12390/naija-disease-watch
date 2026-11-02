<?php

namespace Database\Factories;

use App\Models\DiseaseReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiseaseReport> */
class DiseaseReportFactory extends Factory
{
    public function definition(): array
    {
        return ['state' => 'Lagos', 'disease' => 'Cholera', 'year' => 2023, 'month' => 1, 'cases' => 100, 'deaths' => 5, 'recoveries' => 80, 'source' => 'Test report', 'source_url' => 'https://example.org/report'];
    }
}
