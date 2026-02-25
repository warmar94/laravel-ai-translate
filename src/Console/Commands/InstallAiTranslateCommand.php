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
            '--force' => true,
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

        // Publish app files (models, services, jobs, livewire, middleware, providers, helpers)
        $this->call('vendor:publish', [
            '--provider' => 'Warmar\\LaravelAiTranslate\\LaravelAiTranslateServiceProvider',
            '--tag' => 'ai-translate-app',
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Publishing complete!');

        // Optional: migrate
        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('Almost done! Complete these final steps:');

        // Step 1: Register service provider
        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=yellow>Step 1</>',
            'Register the TranslationServiceProvider'
        );
        $this->line('  Add to <comment>bootstrap/providers.php</comment>:');
        $this->newLine();
        $this->line('  <fg=green>App\Providers\Translate\TranslationServiceProvider::class,</>');

        // Step 2: Register helper in composer.json
        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=yellow>Step 2</>',
            'Register the global helper functions'
        );
        $this->line('  Add to the <comment>autoload.files</comment> array in <comment>composer.json</comment>:');
        $this->newLine();
        $this->line('  <fg=green>"app/Helpers/Translate/TranslationHelper.php"</>');
        $this->newLine();
        $this->line('  Then run: <comment>composer dump-autoload</comment>');

        // Step 3: Register middleware
        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=yellow>Step 3</>',
            'Register the language middleware'
        );
        $this->line('  Add to <comment>bootstrap/app.php</comment> withMiddleware:');
        $this->newLine();
        $this->line('  <fg=green>$middleware->alias([</>');
        $this->line('  <fg=green>    \'language\' => \App\Http\Middleware\Translate\LanguageMiddleware::class,</>');
        $this->line('  <fg=green>]);</>');

        // Step 4: Set env
        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=yellow>Step 4</>',
            'Add your OpenAI API key to .env'
        );
        $this->newLine();
        $this->line('  <fg=green>OPENAI_API_KEY=your-api-key-here</>');
        $this->line('  <fg=green>OPENAI_MODEL=gpt-4o-mini</>');

        $this->newLine();
        $this->info('You\'re all set! 🚀');
        $this->newLine();
        $this->info('For detailed configuration, advanced routing, Livewire integration, RTL support, and more:');
        $this->info('Read the Github README: https://github.com/warmar94/laravel-ai-translate');
        $this->info('Read the full documentation: https://warmardev.com/docs/laravel-translate.html');
    }
}