<?php

namespace App\Services;

class SurveillanceForecast
{
    /**
     * A four-period moving-average baseline with rolling one-step validation.
     *
     * @param  array<int, int|null>  $series
     * @return array{estimate: int, mae: float, last_period: int, history_count: int}|null
     */
    public function calculate(array $series): ?array
    {
        $available = array_filter($series, fn (?int $value): bool => $value !== null);
        if ($available === []) {
            return null;
        }
        $last = max(array_keys($available));
        $history = [];
        for ($period = $last; $period > 0 && isset($series[$period]); $period--) {
            array_unshift($history, $series[$period]);
        }
        if (count($history) < 8) {
            return null;
        }
        $history = array_slice($history, -12);
        $errors = [];
        for ($index = count($history) - 4; $index < count($history); $index++) {
            $prediction = array_sum(array_slice($history, $index - 4, 4)) / 4;
            $errors[] = abs($prediction - $history[$index]);
        }

        return ['estimate' => (int) round(array_sum(array_slice($history, -4)) / 4), 'mae' => round(array_sum($errors) / count($errors), 1), 'last_period' => $last, 'history_count' => count($history)];
    }
}
