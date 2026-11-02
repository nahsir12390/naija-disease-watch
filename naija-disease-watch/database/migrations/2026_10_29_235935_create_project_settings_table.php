<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Naija Disease Watch');
            $table->boolean('sync_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
