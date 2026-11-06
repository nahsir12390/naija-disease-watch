<?php

namespace App\Services;

use App\Models\DiseaseReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportDiseaseReports
{
    public const HEADER = ['state', 'disease', 'month', 'year', 'cases', 'deaths', 'recoveries'];

    public const WEEKLY_HEADER = ['state', 'disease', 'week', 'year', 'case_type', 'cases', 'deaths', 'recoveries'];

    public const FORTNIGHTLY_HEADER = ['state', 'disease', 'week', 'week_end', 'year', 'case_type', 'cases', 'deaths', 'recoveries'];

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
            if ($header !== self::HEADER && $header !== self::WEEKLY_HEADER && $header !== self::FORTNIGHTLY_HEADER) {
                throw ValidationException::withMessages(['file' => 'Use the exact headings from the monthly, weekly or two-week CSV template.']);
            }
            $weekly = $header !== self::HEADER;
            $line = 1;
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($header) || count($rows) >= 5000) {
                    throw ValidationException::withMessages(['file' => "Invalid columns or more than 5,000 records at row {$line}."]);
                }
                $row = array_combine($header, array_map(fn (?string $value): string => trim($value ?? ''), $values));
                $row = [...$row, 'month' => $weekly ? 0 : $row['month'], 'week' => $weekly ? $row['week'] : 0, 'week_end' => $row['week_end'] ?? 0, 'case_type' => $weekly ? $row['case_type'] : 'reported', 'source' => $source, 'source_url' => $sourceUrl];
                foreach (['deaths', 'recoveries'] as $field) {
                    $row[$field] = $row[$field] === '' ? null : $row[$field];
                }
                $validator = Validator::make($row, DiseaseReportRules::rules());
                $validator->after(fn ($validator) => DiseaseReportRules::validateTotals($validator, $row));
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: ".$validator->errors()->first()]);
                }
                foreach (['month', 'week', 'week_end', 'year'] as $field) {
                    $row[$field] = (int) $row[$field];
                }
                $key = implode('|', array_map(fn (string $field) => $row[$field], DiseaseReportRules::KEY));
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: duplicate location, disease, period and classification."]);
                }
                $seen[$key] = true;
                $rows[] = [...$row, 'uploaded_by' => $userId];
            }
        } finally {
            fclose($stream);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Upload at least one data row.']);
        }
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                DiseaseReport::updateOrCreate(array_intersect_key($row, array_flip(DiseaseReportRules::KEY)), $row);
            }
        });

        return count($rows);
    }
}
