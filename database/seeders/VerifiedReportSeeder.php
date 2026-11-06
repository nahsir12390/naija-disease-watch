<?php

namespace Database\Seeders;

use App\Models\DiseaseReport;
use App\Services\DiseaseReportRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class VerifiedReportSeeder extends Seeder
{
    public function run(): void
    {
        if (DiseaseReport::exists()) {
            return;
        }
        $data = json_decode(File::get(database_path('seeders/ncdc-starter-data.json')), true, flags: JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data): void {
            foreach ($data['records'] as $row) {
                $validator = Validator::make($row, DiseaseReportRules::rules());
                $validator->after(fn ($validator) => DiseaseReportRules::validateTotals($validator, $row));
                DiseaseReport::create($validator->validate());
            }
        });
    }
}
