<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_reports', function (Blueprint $table): void {
            $table->dropUnique(['state', 'disease', 'year', 'month']);
            $table->unsignedTinyInteger('week')->default(0);
            $table->string('case_type')->default('reported');
            $table->unsignedInteger('deaths')->nullable()->change();
            $table->unsignedInteger('recoveries')->nullable()->change();
            $table->foreignId('source_report_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['state', 'disease', 'year', 'month', 'week', 'case_type'], 'disease_reports_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('disease_reports', function (Blueprint $table): void {
            $table->dropUnique('disease_reports_period_unique');
            $table->dropConstrainedForeignId('source_report_id');
            $table->dropColumn(['week', 'case_type']);
        });
    }
};
