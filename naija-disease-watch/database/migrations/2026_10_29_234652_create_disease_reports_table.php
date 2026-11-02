<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('state');
            $table->string('disease');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('cases');
            $table->unsignedInteger('deaths');
            $table->unsignedInteger('recoveries');
            $table->string('source');
            $table->string('source_url', 2048);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['state', 'disease', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_reports');
    }
};
