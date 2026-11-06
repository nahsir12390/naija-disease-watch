<?php

namespace App\Services;

use App\Models\SourceReport;
use App\Models\SourceSync;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class NcdcReports
{
    public const SOURCES = ['Lassa fever' => 5, 'Cholera' => 7, 'COVID-19' => 14];

    /** @return array{new: int, failed: int} */
    public function sync(): array
    {
        $lock = Cache::lock('ncdc-report-discovery', 120);
        if (! $lock->get()) {
            return ['new' => 0, 'failed' => 0];
        }
        $result = ['new' => 0, 'failed' => 0];
        try {
            foreach (self::SOURCES as $disease => $category) {
                $sync = SourceSync::firstOrCreate(['disease' => $disease]);
                $sync->update(['attempted_at' => now()]);
                try {
                    $response = Http::connectTimeout(8)->timeout(25)->withoutRedirecting()
                        ->get('https://ncdc.gov.ng/diseases/sitreps/', ['cat' => $category, 'name' => $disease.' outbreak in Nigeria']);
                    $response->throw();
                    $reports = $this->discover($response->body(), $disease);
                    if ($reports === []) {
                        throw new \RuntimeException('No report links found. The source page may have changed.');
                    }
                    $new = 0;
                    foreach ($reports as $report) {
                        $model = SourceReport::firstOrCreate(['url_hash' => hash('sha256', $report['url'])], $report);
                        $new += (int) $model->wasRecentlyCreated;
                    }
                    $sync->update(['succeeded_at' => now(), 'discovered_count' => count($reports), 'error' => null]);
                    $result['new'] += $new;
                } catch (Throwable $exception) {
                    report($exception);
                    $sync->update(['error' => 'The NCDC page could not be checked. Existing records are preserved; try again later.']);
                    $result['failed']++;
                }
            }
        } finally {
            $lock->release();
        }

        return $result;
    }

    /** @return list<array{disease: string, title: string, url: string, year: int|null, week: int|null}> */
    public function discover(string $html, string $disease): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $links = (new DOMXPath($document))->query('//a[@href]');
        $reports = [];
        foreach ($links ?: [] as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }
            $href = $link->getAttribute('href');
            $url = str_starts_with($href, '/') && ! str_starts_with($href, '//') ? 'https://ncdc.gov.ng'.$href : $href;
            if (! self::isReportUrl($url) || isset($reports[$url])) {
                continue;
            }
            $filename = $link->getAttribute('download');
            $week = null;
            $year = null;
            if (preg_match('/_(\d{2})(\d{2})(\d{2})_(\d{1,2})\.pdf$/i', $filename, $matches)) {
                $year = 2000 + (int) $matches[3];
                $week = (int) $matches[4];
            }
            $title = trim(preg_replace('/\s+/', ' ', $link->textContent));
            $reports[$url] = ['disease' => $disease, 'title' => mb_substr($title !== '' ? $title : ($filename ?: $disease.' situation report'), 0, 255), 'url' => $url, 'year' => $year, 'week' => $week];
            if (count($reports) >= 12) {
                break;
            }
        }

        return array_values($reports);
    }

    public static function isReportUrl(string $url): bool
    {
        return preg_match('~^https://(?:www\.)?ncdc\.gov\.ng/themes/common/(?:files|docs)/sitreps/[a-zA-Z0-9_.-]+\.pdf$~D', $url) === 1;
    }
}
