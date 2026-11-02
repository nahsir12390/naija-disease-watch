<?php

namespace App\Models;

use Database\Factories\DiseaseReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiseaseReport extends Model
{
    /** @use HasFactory<DiseaseReportFactory> */
    use HasFactory;

    protected $fillable = ['state', 'disease', 'year', 'month', 'cases', 'deaths', 'recoveries', 'source', 'source_url', 'uploaded_by', 'week', 'case_type', 'source_report_id'];

    protected $attributes = ['week' => 0, 'case_type' => 'reported'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'month' => 'integer', 'week' => 'integer', 'cases' => 'integer', 'deaths' => 'integer', 'recoveries' => 'integer'];
    }
}
