<?php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;
use Warmar\LaravelAiTranslate\Console\Commands\InstallAiTranslateCommand;

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAiTranslateCommand::class,
            ]);

            // Migrations
            $this->publishes([
                __DIR__ . '/database/migrations/2026_01_26_170933_create_translation_progress_table.php' =>
                    database_path('migrations/2026_01_26_170933_create_translation_progress_table.php'),
                __DIR__ . '/database/migrations/2026_01_26_170934__create_translation_urls_table.php' =>
                    database_path('migrations/2026_01_26_170934__create_translation_urls_table.php'),
            ], 'ai-translate-migrations');
        }

        // App files (models, services, jobs, livewire, middleware, providers, helpers)
        $this->publishes([
            __DIR__ . '/app' => app_path(),
        ], 'ai-translate-app');

        // Config
        $this->publishes([
            __DIR__ . '/config/translation.php' => config_path('translation.php'),
        ], 'ai-translate-config');

        // Views
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views'),
        ], 'ai-translate-views');
    }
}