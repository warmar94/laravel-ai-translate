<?php
// app/Jobs/TranslateStringBatchJob.php

namespace App\Jobs\Translate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Translate\AITranslator;
use Illuminate\Support\Facades\DB;
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
            
            // Load existing translations
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
                
                // Translate
                $translated = $translator->translate($sourceText, $this->targetLocale);
                
                if ($translated) {
                    $existing[$key] = $translated;
                    $translatedCount++;
                }
                
                // Small delay to respect rate limits
                usleep(100000); // 0.1 second
            }
            
            if ($translatedCount > 0) {
                // Sort alphabetically
                ksort($existing);
                
                // Save back to file
                file_put_contents(
                    $filePath,
                    json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
                
                if ($this->logProcess) {
                    Log::info("Translated {$translatedCount} strings to {$this->targetLocale}");
                }
            }
            
            // Update progress
            $progress = DB::table('translation_progress')
                ->where('type', 'translation')
                ->where('locale', $this->targetLocale)
                ->first();
            
            if ($progress) {
                $newCompleted = $progress->completed + count($this->strings);
                $isComplete = $newCompleted >= $progress->total;
                
                DB::table('translation_progress')
                    ->where('type', 'translation')
                    ->where('locale', $this->targetLocale)
                    ->update([
                        'completed' => $newCompleted,
                        'updated_at' => now(),
                        'completed_at' => $isComplete ? now() : null,
                    ]);
            }
                
        } catch (\Exception $e) {
            Log::error("Translation batch failed for {$this->targetLocale}", [
                'error' => $e->getMessage()
            ]);
            
            // Update failed count
            DB::table('translation_progress')
                ->where('type', 'translation')
                ->where('locale', $this->targetLocale)
                ->update([
                    'failed' => DB::raw('failed + ' . count($this->strings)),
                    'updated_at' => now(),
                ]);
                
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error("Translation job permanently failed for {$this->targetLocale}", [
            'error' => $exception->getMessage()
        ]);
    }
}