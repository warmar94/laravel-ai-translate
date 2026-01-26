<?php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;
use Warmar\LaravelAiTranslate\Console\Commands\InstallAiTranslateCommand;

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Register your new install command
            $this->commands([
                InstallAiTranslateCommand::class,
            ]);

            // Publish migrations (auto-timestamp + .stub removal by Laravel)
            $this->publishesMigrations([
                __DIR__ . '/database/migrations' => database_path('migrations'),
            ], 'ai-translate-migrations');
        }

        // Existing publishes (user can run vendor:publish manually)
        $this->publishes([
            __DIR__ . '/app' => app_path(),
        ], 'ai-translate-app');

        $this->publishes([
            __DIR__ . '/config/translation.php' => config_path('translation.php'),
        ], 'ai-translate-config');

        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views'),
        ], 'ai-translate-views');
    }
}