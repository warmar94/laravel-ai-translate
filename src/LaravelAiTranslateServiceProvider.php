<?php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;
use Warmar\LaravelAiTranslate\Console\Commands\InstallAiTranslateCommand;

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Register install command
            $this->commands([
                InstallAiTranslateCommand::class,
            ]);

            // Migration
            $this->publishes([
                __DIR__ . '/database/migrations/2026_01_26_170933_create_translation_progress_table.php' =>
                    database_path('migrations/2026_01_26_170933_create_translation_progress_table.php'),
            ], 'ai-translate-migrations');
        }

        // App files (services, jobs, livewire, middleware, providers, helpers)
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