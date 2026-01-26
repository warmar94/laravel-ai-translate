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
        $this->info('You\'re all set! 🚀');
        $this->info('For detailed configuration, advanced routing, Livewire integration, RTL support, and more:');
        $this->info('Read the Github Readme: https://github.com/warmar94/laravel-ai-translate');
        $this->info('Read the full documentation: https://warmardev.com/docs/laravel-translate.html');
    }
}