<?php

namespace App\Services;

class SurveillanceMap
{
    /** @return array{regions: list<array{name: string, path: string}>, source: string, license: string} */
    public function regions(): array
    {
        $json = file_get_contents(public_path('nigeria-states.json'));
        if ($json === false) {
            throw new \RuntimeException('The Nigeria boundary asset is missing.');
        }

        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}
