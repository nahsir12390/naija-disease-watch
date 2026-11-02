<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_syncs', function (Blueprint $table): void {
            $table->id();
            $table->string('disease')->unique();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->unsignedInteger('discovered_count')->default(0);
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_syncs');
    }
};
