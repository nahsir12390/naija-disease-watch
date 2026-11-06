<?php

namespace Tests\Unit;

use App\Services\SurveillanceForecast;
use PHPUnit\Framework\TestCase;

class ForecastTest extends TestCase
{
    public function test_short_or_gapped_history_does_not_produce_a_forecast(): void
    {
        $service = new SurveillanceForecast;
        $this->assertNull($service->calculate([1 => 10, 2 => 20]));
        $this->assertNull($service->calculate([1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => null, 6 => 60, 7 => 70, 8 => 80, 9 => 90]));
        $this->assertNull($service->calculate([1 => null]));
    }

    public function test_baseline_and_rolling_validation_are_computed_from_observed_history(): void
    {
        $series = array_combine(range(1, 8), [10, 20, 30, 40, 50, 60, 70, 80]);
        $forecast = (new SurveillanceForecast)->calculate($series);
        $this->assertSame(65, $forecast['estimate']);
        $this->assertSame(25.0, $forecast['mae']);
        $this->assertSame(8, $forecast['last_period']);
    }

    public function test_observed_zero_counts_are_valid_history(): void
    {
        $forecast = (new SurveillanceForecast)->calculate(array_fill(1, 8, 0));
        $this->assertSame(0, $forecast['estimate']);
        $this->assertSame(0.0, $forecast['mae']);
    }
}
