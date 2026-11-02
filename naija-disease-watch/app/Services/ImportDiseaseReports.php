<?php

namespace App\Services;

use App\Models\DiseaseReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportDiseaseReports
{
    public const HEADER = ['state', 'disease', 'month', 'year', 'cases', 'deaths', 'recoveries'];

    public function import(UploadedFile $file, string $source, string $sourceUrl, int $userId): int
    {
        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded file could not be read.']);
        }
        $rows = [];
        $seen = [];

        try {
            $header = fgetcsv($stream, escape: '');
            if ($header !== false) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
            }
            if ($header !== self::HEADER) {
                throw ValidationException::withMessages(['file' => 'CSV headings must be: '.implode(',', self::HEADER)]);
            }
            $line = 1;
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count(self::HEADER) || $line > 5001) {
                    throw ValidationException::withMessages(['file' => "Invalid column count or more than 5,000 records at row {$line}."]);
                }
                $row = array_combine(self::HEADER, array_map(fn (?string $value): string => trim($value ?? ''), $values));
                $validator = Validator::make($row, [
                    'state' => ['required', Rule::in(SurveillanceData::STATES)],
                    'disease' => ['required', Rule::in(SurveillanceData::DISEASES)],
                    'month' => ['required', 'integer', 'between:1,12'],
                    'year' => ['required', 'integer', 'between:2000,'.now()->year],
                    'cases' => ['required', 'integer', 'between:0,100000000'],
                    'deaths' => ['required', 'integer', 'min:0', 'lte:cases'],
                    'recoveries' => ['required', 'integer', 'min:0', 'lte:cases'],
                ]);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: ".$validator->errors()->first()]);
                }
                if ((int) $row['deaths'] + (int) $row['recoveries'] > (int) $row['cases']) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: deaths and recoveries together cannot exceed cases."]);
                }
                $key = implode('|', [$row['state'], $row['disease'], (int) $row['year'], (int) $row['month']]);
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: duplicate state, disease and month in this file."]);
                }
                $seen[$key] = true;
                $rows[] = [...$row, 'source' => $source, 'source_url' => $sourceUrl, 'uploaded_by' => $userId];
            }
        } finally {
            fclose($stream);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Upload at least one data row.']);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                DiseaseReport::updateOrCreate(array_intersect_key($row, array_flip(['state', 'disease', 'year', 'month'])), $row);
            }
        });

        return count($rows);
    }
}
