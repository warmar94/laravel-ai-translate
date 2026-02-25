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

            $existing = [];
            if (file_exists($filePath)) {
                $existing = json_decode(file_get_contents($filePath), true) ?? [];
            }

            $translatedCount = 0;

            foreach ($this->strings as $key => $sourceText) {
                // Skip if already translated
                if (isset($existing[$key]) && $existing[$key] !== $key) {
                    continue;
                }

                $translated = $translator->translate($sourceText, $this->targetLocale);

                if ($translated) {
                    $existing[$key] = $translated;
                    $translatedCount++;
                }

                usleep(100000); // 0.1s rate limit buffer
            }

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

            // Update progress
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