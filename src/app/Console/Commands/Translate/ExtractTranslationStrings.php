<?php

namespace App\Console\Commands\Translate;

use App\Jobs\Translate\ScanUrlForStringsJob;
use App\Models\Translate\TranslationProgress;
use App\Services\Translate\URLCollector;
use Illuminate\Console\Command;

/**
 * Dispatches ScanUrlForStringsJob for every active URL in translation_urls.
 *
 * Mirrors TranslateMenu::collectStrings() exactly.
 * Safe to run daily via scheduler — jobs are idempotent (ProcessMissingKeysJob
 * only adds keys that don't already exist in en.json).
 *
 * Usage:
 *   php artisan translate:extract
 *   php artisan translate:extract --delay=2
 */
class ExtractTranslationStrings extends Command
{
    protected $signature = 'translate:extract
                            {--delay= : Seconds between URL scans (overrides config)}';

    protected $description = 'Scan all active URLs and collect missing translation strings into en.json';

    public function handle(): int
    {
        $collector = new URLCollector();
        $urls = $collector->getExtractableUrls();

        if (empty($urls)) {
            $this->warn('No active URLs found. Add URLs via the Translation Manager dashboard first.');
            return self::FAILURE;
        }

        $delay = $this->option('delay') !== null
            ? (int) $this->option('delay')
            : config('translation.urls.delay_between_requests', 1);

        $total = count($urls);

        // Reset / create the progress record so the dashboard reflects this run
        TranslationProgress::updateOrCreate(
            ['type' => 'string_extraction', 'locale' => null],
            [
                'total'        => $total,
                'completed'    => 0,
                'failed'       => 0,
                'started_at'   => now(),
                'updated_at'   => now(),
                'completed_at' => null,
            ]
        );

        $this->info("Queuing {$total} URL(s) for string extraction (delay: {$delay}s between scans)…");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($urls as $url) {
            ScanUrlForStringsJob::dispatch($url, $delay);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$total} scan job(s) queued. Run your queue worker to process them.");

        return self::SUCCESS;
    }
}