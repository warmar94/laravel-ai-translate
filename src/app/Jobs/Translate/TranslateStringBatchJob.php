<?php

namespace App\Jobs\Translate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Translate\AITranslator;
use App\Models\Translate\TranslationProgress;
use Illuminate\Support\Facades\Log;

/**
 * Translates a batch of strings to a target locale via OpenAI.
 *
 * Dispatched from the dashboard "Translate All Keys" action.
 * Each job handles one batch (configurable size, default 20 strings)
 * for one target locale. Skips strings that are already translated.
 *
 * Writes translated strings directly to lang/{locale}.json and
 * updates the TranslationProgress model for real-time dashboard display.
 */
class TranslateStringBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $maxExceptions = 2;
    protected $logProcess = false;

    public function __construct(
        public array $strings,
        public string $targetLocale
    ) {
        $this->logProcess = config('translation.log_process', false);
    }

    public function handle(AITranslator $translator): void
    {
        try {
            $filePath = lang_path("{$this->targetLocale}.json");

            // Load existing translations
            $existing = [];
            if (file_exists($filePath)) {
                $existing = json_decode(file_get_contents($filePath), true) ?? [];
            }

            $translatedCount = 0;

            foreach ($this->strings as $key => $sourceText) {
                // Skip if already translated (value differs from key)
                if (isset($existing[$key]) && $existing[$key] !== $key) {
                    continue;
                }

                $translated = $translator->translate($sourceText, $this->targetLocale);

                if ($translated) {
                    $existing[$key] = $translated;
                    $translatedCount++;
                }

                // Small delay between API calls to respect rate limits
                usleep(100000); // 0.1s
            }

            // Write back if we translated anything
            if ($translatedCount > 0) {
                ksort($existing);

                file_put_contents(
                    $filePath,
                    json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );

                if ($this->logProcess) {
                    Log::info("Translated {$translatedCount} strings to {$this->targetLocale}");
                }
            }

            // Update progress for dashboard display
            $progress = TranslationProgress::translation()
                ->forLocale($this->targetLocale)
                ->first();

            if ($progress) {
                $newCompleted = $progress->completed + count($this->strings);

                $progress->update([
                    'completed' => $newCompleted,
                    'updated_at' => now(),
                    'completed_at' => $newCompleted >= $progress->total ? now() : null,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Translation batch failed for {$this->targetLocale}", [
                'error' => $e->getMessage(),
            ]);

            $progress = TranslationProgress::translation()
                ->forLocale($this->targetLocale)
                ->first();

            if ($progress) {
                $progress->increment('failed', count($this->strings));
                $progress->update(['updated_at' => now()]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Translation job permanently failed for {$this->targetLocale}", [
            'error' => $exception->getMessage(),
        ]);
    }
}