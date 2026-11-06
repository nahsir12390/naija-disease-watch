<?php

namespace App\Services;

use App\Models\SourceReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ExtractReportText
{
    public function extract(SourceReport $report): void
    {
        if (! NcdcReports::isReportUrl($report->url)) {
            throw new \InvalidArgumentException('Only official NCDC report PDFs can be downloaded.');
        }
        $path = tempnam(sys_get_temp_dir(), 'ndw-report-');
        if ($path === false) {
            throw new \RuntimeException('Unable to prepare report storage.');
        }
        try {
            $response = Http::connectTimeout(8)->timeout(35)->withoutRedirecting()->withOptions([
                'stream' => true,
                'on_headers' => function (ResponseInterface $response): void {
                    if ((int) $response->getHeaderLine('Content-Length') > 12 * 1024 * 1024) {
                        throw new \RuntimeException('Report exceeds the 12 MB download limit.');
                    }
                },
            ])->get($report->url)->throw();
            $body = $response->toPsrResponse()->getBody();
            $output = fopen($path, 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to save the report for extraction.');
            }
            try {
                $size = 0;
                while (! $body->eof()) {
                    $chunk = $body->read(65536);
                    $size += strlen($chunk);
                    if ($size > 12 * 1024 * 1024) {
                        throw new \RuntimeException('Report exceeds the 12 MB download limit.');
                    }
                    fwrite($output, $chunk);
                }
            } finally {
                fclose($output);
                $body->close();
            }
            if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
                throw new \RuntimeException('The source did not return a PDF document.');
            }
            $result = Process::timeout(20)->run(['pdftotext', '-layout', '-f', '1', '-l', '2', $path, '-']);
            if (! $result->successful() || trim($result->output()) === '') {
                throw new \RuntimeException('Text could not be extracted. Open the original PDF and enter its figures.');
            }
            $text = mb_substr($result->output(), 0, 60000);
            $attributes = ['extracted_text' => $text, 'extraction_error' => null, 'suggestions' => $this->suggest($text, $report->disease)];
            $attributes = [...$attributes, ...($this->period($text) ?? [])];
            $report->update($attributes);
        } catch (Throwable $exception) {
            report($exception);
            $report->update(['extraction_error' => 'Automatic text extraction was unavailable. Open the source PDF and enter the current-period figures manually.']);
        } finally {
            @unlink($path);
        }
    }

    /** @return array{week: int, week_end: int, year: int}|null */
    public function period(string $text): ?array
    {
        if (preg_match('/Epi\s+Weeks?\s*:?\s*(\d{1,2})\s*[–—-]\s*(\d{1,2})[^\n]{0,80}?(20\d{2})/iu', $text, $matches)) {
            return ['week' => (int) $matches[1], 'week_end' => (int) $matches[2], 'year' => (int) $matches[3]];
        }
        if (preg_match('/Epi\s+Week\s*:?\s*(\d{1,2})\s*[,]?\s*(20\d{2})/i', $text, $matches)) {
            return ['week' => (int) $matches[1], 'week_end' => 0, 'year' => (int) $matches[2]];
        }

        return null;
    }

    /** @return array{cases: int, deaths: int, recoveries: null, case_type: string}|null */
    public function suggest(string $text, string $disease): ?array
    {
        if ($disease !== 'Lassa fever' || ! str_contains($text, 'Current week') || ! str_contains($text, 'Confirmed')) {
            return null;
        }
        if (! preg_match('/\(week\s+\d{1,2}\)\s+([\d,]+)\s+([\d,]+)\s+([\d,]+)\s+([\d,]+)\s+[\d.]+%/i', $text, $matches)) {
            return null;
        }
        $cases = (int) str_replace(',', '', $matches[2]);
        $deaths = (int) str_replace(',', '', $matches[4]);

        return ['cases' => $cases, 'deaths' => $deaths, 'recoveries' => null, 'case_type' => 'confirmed'];
    }
}
