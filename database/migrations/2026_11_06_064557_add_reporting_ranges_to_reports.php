<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_reports', function (Blueprint $table): void {
            $table->dropUnique('disease_reports_period_unique');
            $table->unsignedTinyInteger('week_end')->default(0);
            $table->unique(['state', 'disease', 'year', 'month', 'week', 'week_end', 'case_type'], 'disease_reports_period_unique');
        });
        Schema::table('source_reports', function (Blueprint $table): void {
            $table->unsignedTinyInteger('week_end')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('disease_reports', function (Blueprint $table): void {
            $table->dropUnique('disease_reports_period_unique');
            $table->dropColumn('week_end');
            $table->unique(['state', 'disease', 'year', 'month', 'week', 'case_type'], 'disease_reports_period_unique');
        });
        Schema::table('source_reports', function (Blueprint $table): void {
            $table->dropColumn('week_end');
        });
    }
};
