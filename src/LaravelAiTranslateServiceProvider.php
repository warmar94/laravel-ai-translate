<?php
// src/LaravelAiTranslateServiceProvider.php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish app files
        $this->publishes([
            __DIR__.'/app' => app_path(),
        ], 'ai-translate-app');

        // Publish config
        $this->publishes([
            __DIR__.'/config/translation.php' => config_path('translation.php'),
        ], 'ai-translate-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/database/migrations/create_translation_progress_table.php.stub' => 
                database_path('migrations/' . date('Y_m_d_His') . '_create_translation_progress_table.php'),
        ], 'ai-translate-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views'),
        ], 'ai-translate-views');
    }
}