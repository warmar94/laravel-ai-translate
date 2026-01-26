<?php

namespace Warmar\LaravelAiTranslate\Console\Commands;

use Illuminate\Console\Command;

class InstallAiTranslateCommand extends Command
{
    protected $signature = 'ai-translate:install';

    protected $description = 'Install and set up the Laravel AI Translate package';

    public function handle(): void
    {
        $this->info('Publishing AI Translate package assets...');

        // Publish config
        $this->call('vendor:publish', [
            '--provider' => 'Warmar\\LaravelAiTranslate\\LaravelAiTranslateServiceProvider',
            '--tag' => 'ai-translate-config',
            '--force' => true,  // optional: overwrite if exists (use with caution)
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--provider' => 'Warmar\\LaravelAiTranslate\\LaravelAiTranslateServiceProvider',
            '--tag' => 'ai-translate-migrations',
            '--force' => true,
        ]);

        // Publish views
        $this->call('vendor:publish', [
            '--provider' => 'Warmar\\LaravelAiTranslate\\LaravelAiTranslateServiceProvider',
            '--tag' => 'ai-translate-views',
            '--force' => true,
        ]);

        // Publish app files (--force!)
        $this->call('vendor:publish', [
            '--provider' => 'Warmar\\LaravelAiTranslate\\LaravelAiTranslateServiceProvider',
            '--tag' => 'ai-translate-app',
            '--force' => true,  // ⚠️ This overwrites files in app/
        ]);

        $this->newLine();

        $this->info('Publishing complete!');

        // Optional: migrate if user wants
        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();

        // Print next steps / reminders
        $this->warn('Next steps:');
        $this->line('1. Add these to your .env file (replace with your actual values):');
        $this->line('   OPENAI_API_KEY=sk-...');
        $this->line('   TRANSLATION_COLLECTION_MODE=false');
        $this->line('   OPENAI_MODEL=gpt-4o-mini');
        $this->line('   TRANSLATION_URL_DELAY=1');
        $this->line('   TRANSLATION_LOG_PROCESS=false');

        $this->newLine();

        $this->line('2. Register the language middleware in bootstrap/app.php (Laravel 11+):');
        $this->comment('   ->withMiddleware(function (Middleware $middleware) {');
        $this->comment('       $middleware->alias([\'language\' => \\Warmar\\LaravelAiTranslate\\Http\\Middleware\\LanguageMiddleware::class]);');
        $this->comment('   })');

        $this->newLine();

        $this->line('3. Set up your translation routes after registering the middleware.');
        $this->line('   Add the necessary route groups or middleware applications as shown in the documentation.');
        $this->line('   → See full routing examples and configuration options in the docs.');

        $this->newLine();

        $this->line('4. If you changed config or .env values, clear the config cache:');
        $this->line('   php artisan config:clear');
        $this->line('   (or php artisan optimize:clear for everything)');

        $this->newLine();

        $this->info('You\'re all set! 🚀');
        $this->info('For detailed configuration, advanced routing, Livewire integration, RTL support, and more:');
        $this->info('Read the full documentation: https://warmardev.com/docs/laravel-translate.html');
    }
}