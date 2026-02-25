<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_urls', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->boolean('active')->default(true);
            $table->tinyInteger('is_api')->default(0);
            $table->timestamps();

            $table->index('active');
            $table->index('is_api');
            $table->index(['active', 'is_api']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_urls');
    }
};