<?php
// app/Livewire/Translation/TranslateMenu.php

namespace Warmar\LaravelAiTranslate\Livewire\Translation;

use Livewire\Component;
use Warmar\LaravelAiTranslate\Services\Translation\URLCollector;
use Warmar\LaravelAiTranslate\Services\Translation\StringExtractor;
use Warmar\LaravelAiTranslate\Services\Translation\AITranslator;
use Warmar\LaravelAiTranslate\Jobs\ScanUrlForStringsJob;
use Warmar\LaravelAiTranslate\Jobs\TranslateStringBatchJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranslateMenu extends Component
{
    // URL Collection
    public $manualUrls = '';
    public $apiEndpoints = '';
    public $totalUrls = 0;

    protected $logProcess;
    
    // Progress tracking
    public $stringExtractionProgress = [
        'total' => 0,
        'completed' => 0,
        'failed' => 0,
        'percentage' => 0,
        'status' => 'idle', // idle, running, completed, failed
    ];
    
    public $translationProgress = [];
    
    // Status messages
    public $statusMessage = '';
    public $statusType = 'info'; // info, success, error, warning
    public $isProcessing = false;
    
    // Stats
    public $totalKeysInEnJson = 0;
    
    public function mount()
    {
        $this->logProcess = config('translation.log_process', false);
        $this->loadProgress();
        $this->loadExistingUrls();
        $this->loadStats();
    }
    
    public function loadExistingUrls()
    {
        $collector = new URLCollector();
        $urls = $collector->loadFromConfig();
        $this->totalUrls = count($urls);
    }
    
    public function loadStats()
    {
        $extractor = new StringExtractor();
        $keys = $extractor->getAllKeys('en');
        $this->totalKeysInEnJson = count($keys);
    }
    
    public function loadProgress()
    {
        // Get string extraction progress
        $extractionProgress = DB::table('translation_progress')
            ->where('type', 'string_extraction')
            ->whereNull('locale')
            ->first();
            
        if ($extractionProgress) {
            $this->stringExtractionProgress = [
                'total' => $extractionProgress->total,
                'completed' => $extractionProgress->completed,
                'failed' => $extractionProgress->failed,
                'percentage' => $extractionProgress->total > 0 
                    ? round(($extractionProgress->completed / $extractionProgress->total) * 100, 1)
                    : 0,
                'status' => $this->getStatus($extractionProgress),
            ];
        }
        
        // Get translation progress for each locale
        $translator = new AITranslator();
        $locales = $translator->getTargetLocales();
        
        foreach ($locales as $locale) {
            $progress = DB::table('translation_progress')
                ->where('type', 'translation')
                ->where('locale', $locale)
                ->first();
                
            if ($progress) {
                $this->translationProgress[$locale] = [
                    'total' => $progress->total,
                    'completed' => $progress->completed,
                    'failed' => $progress->failed,
                    'percentage' => $progress->total > 0 
                        ? round(($progress->completed / $progress->total) * 100, 1)
                        : 0,
                    'status' => $this->getStatus($progress),
                ];
            } else {
                $this->translationProgress[$locale] = [
                    'total' => 0,
                    'completed' => 0,
                    'failed' => 0,
                    'percentage' => 0,
                    'status' => 'idle',
                ];
            }
        }
        
        // Update stats after progress check
        $this->loadStats();
    }
    
    protected function getStatus($progress): string
    {
        if ($progress->total == 0) {
            return 'idle';
        }
        
        if ($progress->completed == $progress->total) {
            return 'completed';
        }
        
        if ($progress->completed > 0) {
            return 'running';
        }
        
        return 'idle';
    }
    
    public function generateUrls()
    {
        try {
            $collector = new URLCollector();
            
            // Add manual URLs (one per line)
            if ($this->manualUrls) {
                $manualArray = array_filter(
                    array_map('trim', explode("\n", $this->manualUrls))
                );
                $collector->addManualUrls($manualArray);
            }
            
            // Add API endpoints (one per line, format: name|url)
            if ($this->apiEndpoints) {
                $apiArray = array_filter(
                    array_map('trim', explode("\n", $this->apiEndpoints))
                );
                
                $endpoints = [];
                foreach ($apiArray as $line) {
                    // Support both "url" and "name|url" formats
                    if (str_contains($line, '|')) {
                        [$name, $url] = explode('|', $line, 2);
                        $endpoints[trim($name)] = trim($url);
                    } else {
                        $endpoints[] = trim($line);
                    }
                }
                
                $collector->collectFromAPIs($endpoints);
            }
            
            // Save to config/urls.json
            $this->totalUrls = $collector->saveToConfig();
            
            $this->statusMessage = "Generated {$this->totalUrls} URLs successfully! Ready to collect strings.";
            $this->statusType = 'success';
            
        } catch (\Exception $e) {
            $this->statusMessage = "Error: " . $e->getMessage();
            $this->statusType = 'error';
            \Log::error('URL generation failed: ' . $e->getMessage());
        }
    }

    public function collectStrings()
    {
        if ($this->logProcess) {
            Log::info("=== COLLECT STRINGS STARTED ===");
        }
        
        try {
            $collector = new URLCollector();
            $urls = $collector->loadFromConfig();
            
            if ($this->logProcess) {
                Log::info("Loaded URLs", ['count' => count($urls), 'urls' => $urls]);
            }
            
            if (empty($urls)) {
                $this->statusMessage = "No URLs found. Please generate URLs first.";
                $this->statusType = 'error';
                
                if ($this->logProcess) {
                    Log::warning("No URLs found");
                }
                return;
            }
            
            // Reset or create progress tracking
            if ($this->logProcess) {
                Log::info("Creating progress tracking entry");
            }
            
            DB::table('translation_progress')->updateOrInsert(
                ['type' => 'string_extraction', 'locale' => null],
                [
                    'total' => count($urls),
                    'completed' => 0,
                    'failed' => 0,
                    'started_at' => now(),
                    'updated_at' => now(),
                    'completed_at' => null,
                ]
            );
            
            if ($this->logProcess) {
                Log::info("Progress tracking created");
            }
            
            // Dispatch jobs for each URL
            $delay = config('translation.urls.delay_between_requests', 1);
            
            if ($this->logProcess) {
                Log::info("Dispatching jobs", ['delay' => $delay]);
            }
            
            foreach ($urls as $index => $url) {
                if ($this->logProcess) {
                    Log::info("Dispatching job #{$index} for URL: {$url}");
                }
                ScanUrlForStringsJob::dispatch($url, $delay);
            }
            
            $this->statusMessage = "Started collecting strings from {$this->totalUrls} URLs! Check progress below.";
            $this->statusType = 'success';
            $this->isProcessing = true;
            
            if ($this->logProcess) {
                Log::info("=== COLLECT STRINGS COMPLETED ===");
            }
            
            // Refresh progress immediately
            $this->loadProgress();
            
        } catch (\Exception $e) {
            $this->statusMessage = "Error: " . $e->getMessage();
            $this->statusType = 'error';
            Log::error('String collection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function translateAll()
    {
        try {
            $extractor = new StringExtractor();
            $sourceLocale = config('translation.source_locale', 'en');
            $sourceKeys = $extractor->getAllKeys($sourceLocale);
            
            if (empty($sourceKeys)) {
                $this->statusMessage = "No strings found in {$sourceLocale}.json. Please collect strings first.";
                $this->statusType = 'error';
                return;
            }
            
            $translator = new AITranslator();
            
            // Check if API key is configured
            if (!$translator->isConfigured()) {
                $this->statusMessage = "OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.";
                $this->statusType = 'error';
                return;
            }
            
            $locales = $translator->getTargetLocales();
            $batchSize = config('translation.translation.batch_size', 20);
            
            $totalUntranslated = 0;
            
            foreach ($locales as $locale) {
                // Get existing translations
                $existing = $extractor->getAllKeys($locale);
                
                // Find untranslated keys
                $untranslated = [];
                foreach ($sourceKeys as $key => $value) {
                    if (!isset($existing[$key]) || $existing[$key] === $key) {
                        $untranslated[$key] = $value;
                    }
                }
                
                if (empty($untranslated)) {
                    continue;
                }
                
                $totalUntranslated += count($untranslated);
                
                // Reset or create progress tracking
                DB::table('translation_progress')->updateOrInsert(
                    ['type' => 'translation', 'locale' => $locale],
                    [
                        'total' => count($untranslated),
                        'completed' => 0,
                        'failed' => 0,
                        'started_at' => now(),
                        'updated_at' => now(),
                        'completed_at' => null,
                    ]
                );
                
                // Split into batches and dispatch jobs
                $batches = array_chunk($untranslated, $batchSize, true);
                
                foreach ($batches as $batch) {
                    TranslateStringBatchJob::dispatch($batch, $locale);
                }
            }
            
            if ($totalUntranslated == 0) {
                $this->statusMessage = "All strings are already translated!";
                $this->statusType = 'info';
            } else {
                $this->statusMessage = "Started translating {$totalUntranslated} strings to " . count($locales) . " languages! Check progress below.";
                $this->statusType = 'success';
                $this->isProcessing = true;
            }
            
            // Refresh progress immediately
            $this->loadProgress();
            
        } catch (\Exception $e) {
            $this->statusMessage = "Error: " . $e->getMessage();
            $this->statusType = 'error';
            Log::error('Translation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function refreshProgress()
    {
        $this->loadProgress();
    }
    
    public function resetProgress()
    {
        DB::table('translation_progress')->truncate();
        $this->loadProgress();
        $this->statusMessage = "Progress reset successfully!";
        $this->statusType = 'success';
    }
    
    public function clearUrlsJson()
    {
        $path = config_path('urls.json');
        if (file_exists($path)) {
            unlink($path);
        }
        $this->totalUrls = 0;
        $this->statusMessage = "URLs cleared successfully!";
        $this->statusType = 'success';
    }
    
    public function render()
    {
        return view('livewire.translation.translate-menu')
            ->layoutData([
                'title' => 'Translate Menu',
                'description' => 'Translation and localization tools will be available here soon.',
                'keywords' => 'translation, localization, language tools, coming soon',
            ]);
    }

}