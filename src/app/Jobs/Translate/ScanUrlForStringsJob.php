<?php

namespace App\Jobs\Translate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Translate\StringExtractor;
use App\Models\Translate\TranslationProgress;
use Illuminate\Support\Facades\Log;

class ScanUrlForStringsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $maxExceptions = 2;
    protected $logProcess = false;

    public function __construct(
        public string $url,
        public int $delaySeconds = 1
    ) {
        $this->logProcess = config('translation.log_process', false);
    }

    public function handle(StringExtractor $extractor): void
    {
        if ($this->delaySeconds > 0) {
            sleep($this->delaySeconds);
        }

        try {
            $keys = $extractor->extractFromUrl($this->url);

            if (!empty($keys)) {
                $newCount = $extractor->saveToLanguageFile($keys, 'en');

                if ($this->logProcess) {
                    Log::info("Extracted {$newCount} new keys from {$this->url}");
                }
            }

            // Update progress
            $progress = TranslationProgress::stringExtraction()->first();

            if ($progress) {
                $newCompleted = $progress->completed + 1;

                $progress->update([
                    'completed' => $newCompleted,
                    'updated_at' => now(),
                    'completed_at' => $newCompleted >= $progress->total ? now() : null,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to scan {$this->url}", [
                'error' => $e->getMessage(),
            ]);

            $progress = TranslationProgress::stringExtraction()->first();

            if ($progress) {
                $progress->increment('failed');
                $progress->update(['updated_at' => now()]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed for URL {$this->url}", [
            'error' => $exception->getMessage(),
        ]);
    }
}