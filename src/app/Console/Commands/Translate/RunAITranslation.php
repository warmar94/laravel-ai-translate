<?php

namespace App\Console\Commands\Translate;

use App\Jobs\Translate\TranslateStringBatchJob;
use App\Models\Translate\TranslationProgress;
use App\Services\Translate\AITranslator;
use App\Services\Translate\StringExtractor;
use Illuminate\Console\Command;

/**
 * Dispatches TranslateStringBatchJob for every untranslated key in en.json
 * across all configured target locales.
 *
 * Mirrors TranslateMenu::translateAll() exactly — same three-step flow:
 *   1. Build per-locale untranslated string sets
 *   2. Create all progress records upfront
 *   3. Dispatch batches locale by locale (FIFO queue order)
 *
 * Usage:
 *   php artisan translate:run
 *   php artisan translate:run --locale=ar
 *   php artisan translate:run --locale=ar --locale=de
 */
class RunAITranslation extends Command
{
    protected $signature = 'translate:run
                            {--locale=* : Limit to specific locale(s), e.g. --locale=ar --locale=de}';

    protected $description = 'Translate all untranslated strings via AI for all (or specified) target locales';

    public function handle(): int
    {
        $extractor    = new StringExtractor();
        $translator   = new AITranslator();
        $sourceLocale = config('translation.source_locale', 'en');

        // ── Preflight checks ────────────────────────────────────────────
        if (!$translator->isConfigured()) {
            $this->error('OpenAI API key is not configured. Set OPENAI_API_KEY in your .env.');
            return self::FAILURE;
        }

        $sourceKeys = $extractor->getAllKeys($sourceLocale);
        if (empty($sourceKeys)) {
            $this->warn("No strings found in {$sourceLocale}.json. Run translate:extract first.");
            return self::FAILURE;
        }

        // ── Determine which locales to process ──────────────────────────
        $allLocales     = $translator->getTargetLocales();
        $filterLocales  = $this->option('locale');
        $locales        = !empty($filterLocales)
            ? array_intersect($allLocales, (array) $filterLocales)
            : $allLocales;

        if (empty($locales)) {
            $this->warn('No matching target locales found. Check your translation config.');
            return self::FAILURE;
        }

        $batchSize = config('translation.translation.batch_size', 20);

        // ── Step 1: Build per-locale untranslated sets ──────────────────
        $localeWork = [];
        foreach ($locales as $locale) {
            $existing     = $extractor->getAllKeys($locale);
            $untranslated = [];

            foreach ($sourceKeys as $key => $value) {
                if (!isset($existing[$key]) || $existing[$key] === $key) {
                    $untranslated[$key] = $value;
                }
            }

            if (!empty($untranslated)) {
                $localeWork[$locale] = $untranslated;
            }
        }

        if (empty($localeWork)) {
            $this->info('All strings are already translated. Nothing to do.');
            return self::SUCCESS;
        }

        // ── Step 2: Create all progress records upfront ─────────────────
        foreach ($localeWork as $locale => $untranslated) {
            TranslationProgress::updateOrCreate(
                ['type' => 'translation', 'locale' => $locale],
                [
                    'total'        => count($untranslated),
                    'completed'    => 0,
                    'failed'       => 0,
                    'started_at'   => now(),
                    'updated_at'   => now(),
                    'completed_at' => null,
                ]
            );
        }

        // ── Step 3: Dispatch batches locale by locale ───────────────────
        $languages        = config('translation.languages', []);
        $totalUntranslated = 0;

        foreach ($localeWork as $locale => $untranslated) {
            $count     = count($untranslated);
            $name      = $languages[$locale] ?? strtoupper($locale);
            $numBatches = (int) ceil($count / $batchSize);

            $this->line("  → {$name} ({$locale}): {$count} string(s) across {$numBatches} batch(es)");

            $batches = array_chunk($untranslated, $batchSize, true);
            foreach ($batches as $batch) {
                TranslateStringBatchJob::dispatch($batch, $locale);
            }

            $totalUntranslated += $count;
        }

        $localeCount = count($localeWork);
        $this->newLine();
        $this->info("Done. Queued {$totalUntranslated} string(s) across {$localeCount} language(s). Run your queue worker to process them.");

        return self::SUCCESS;
    }
}