<?php
// app/Jobs/ScanUrlForStringsJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Translation\StringExtractor;
use Illuminate\Support\Facades\DB;
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
        // Add delay between requests
        if ($this->delaySeconds > 0) {
            sleep($this->delaySeconds);
        }
        
        try {
            // Extract strings from URL
            $keys = $extractor->extractFromUrl($this->url);
            
            if (!empty($keys)) {
                // Save to en.json
                $newCount = $extractor->saveToLanguageFile($keys, 'en');
                
                if ($this->logProcess) {
                    Log::info("Extracted {$newCount} new keys from {$this->url}");
                }
            }
            
            // Update progress
            $progress = DB::table('translation_progress')
                ->where('type', 'string_extraction')
                ->whereNull('locale')
                ->first();
            
            if ($progress) {
                $newCompleted = $progress->completed + 1;
                $isComplete = $newCompleted >= $progress->total;
                
                DB::table('translation_progress')
                    ->where('type', 'string_extraction')
                    ->whereNull('locale')
                    ->update([
                        'completed' => $newCompleted,
                        'updated_at' => now(),
                        'completed_at' => $isComplete ? now() : null,
                    ]);
            }
                
        } catch (\Exception $e) {
            Log::error("Failed to scan {$this->url}", [
                'error' => $e->getMessage()
            ]);
            
            // Update failed count
            DB::table('translation_progress')
                ->where('type', 'string_extraction')
                ->whereNull('locale')
                ->update([
                    'failed' => DB::raw('failed + 1'),
                    'updated_at' => now(),
                ]);
                
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed for URL {$this->url}", [
            'error' => $exception->getMessage()
        ]);
    }
}