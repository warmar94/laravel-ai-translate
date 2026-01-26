<?php

namespace Warmar\LaravelAiTranslate;

use Illuminate\Support\ServiceProvider;
use Warmar\LaravelAiTranslate\Console\Commands\InstallAiTranslateCommand;  //import command

class LaravelAiTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Register your new install command
            $this->commands([
                InstallAiTranslateCommand::class,
            ]);
        }

        // Existing publishes (user can run vendor:publish manually)
        $this->publishes([
            __DIR__.'/app' => app_path(),
        ], 'ai-translate-app');

        $this->publishes([
            __DIR__.'/config/translation.php' => config_path('translation.php'),
        ], 'ai-translate-config');

        $this->publishes([
            __DIR__.'/database/migrations/create_translation_progress_table.php.stub' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_translation_progress_table.php'),
        ], 'ai-translate-migrations');

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views'),
        ], 'ai-translate-views');
    }
}