<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('disease');
            $table->string('title');
            $table->string('url', 2048);
            $table->string('url_hash', 64)->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('week')->nullable();
            $table->string('status')->default('pending');
            $table->text('extracted_text')->nullable();
            $table->json('suggestions')->nullable();
            $table->string('extraction_error')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_reports');
    }
};
