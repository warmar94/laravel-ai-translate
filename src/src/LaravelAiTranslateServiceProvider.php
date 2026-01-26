<?php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/translation.php' => config_path('translation.php'),
        ], 'ai-translate-config');

        // Publish migration
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'ai-translate-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/ai-translate'),
        ], 'ai-translate-views');
    }
}