<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missing_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 500);
            $table->string('locale', 10)->default('en');
            $table->bigInteger('occurrences')->unsigned()->default(1);
            $table->datetime('first_seen')->useCurrent();
            $table->datetime('last_seen')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['key', 'locale'], 'uq_key_locale');
            $table->index(['locale'], 'idx_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_translations');
    }
};