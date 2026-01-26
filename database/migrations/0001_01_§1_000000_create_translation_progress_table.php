<?php
// database/migrations/xxxx_create_translation_progress_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_progress', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['url_collection', 'string_extraction', 'translation']);
            $table->string('locale', 10)->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('completed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->unique(['type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_progress');
    }
};