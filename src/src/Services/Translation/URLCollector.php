<?php
// app/Services/Translation/URLCollector.php

namespace Warmar\LaravelAiTranslate\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class URLCollector
{
    protected array $urls = [];
    protected $logProcess = false;
    
    public function __construct()
    {
        $this->logProcess = config('translation.log_process', false);
    }
    
    public function collectFromAPIs(array $apiEndpoints): array
    {
        if ($this->logProcess) {
            Log::info("Collecting URLs from APIs", ['endpoints' => $apiEndpoints]);
        }
        
        foreach ($apiEndpoints as $name => $endpoint) {
            try {
                if ($this->logProcess) {
                    Log::info("Fetching from API: {$endpoint}");
                }
                
                $response = Http::timeout(30)->get($endpoint);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Assume API returns array of URLs
                    if (is_array($data)) {
                        $this->urls = array_merge($this->urls, $data);
                        
                        if ($this->logProcess) {
                            Log::info("Added URLs from {$endpoint}", ['count' => count($data)]);
                        }
                    } else {
                        if ($this->logProcess) {
                            Log::warning("API response is not an array", ['endpoint' => $endpoint]);
                        }
                    }
                } else {
                    Log::error("API request failed", [
                        'endpoint' => $endpoint,
                        'status' => $response->status()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to collect URLs from {$endpoint}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $this->urls;
    }
    
    public function addStaticUrls(array $staticUrls): void
    {
        if ($this->logProcess) {
            Log::info("Adding static URLs", ['count' => count($staticUrls)]);
        }
        $this->urls = array_merge($this->urls, $staticUrls);
    }
    
    public function addManualUrls(array $manualUrls): void
    {
        if ($this->logProcess) {
            Log::info("Adding manual URLs", ['count' => count($manualUrls)]);
        }
        $this->urls = array_merge($this->urls, $manualUrls);
    }
    
    public function getUniqueUrls(): array
    {
        return array_unique($this->urls);
    }
    
    public function saveToConfig(): int
    {
        $urls = $this->getUniqueUrls();
        
        if ($this->logProcess) {
            Log::info("Saving URLs to config", ['total' => count($urls)]);
        }
        
        $data = [
            'static' => [],
            'dynamic' => [],
            'manual' => [],
            'all' => $urls,
            'generated_at' => now()->toIso8601String(),
            'total' => count($urls),
        ];
        
        $path = config_path('urls.json');
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        
        if ($this->logProcess) {
            Log::info("URLs saved to {$path}");
        }
        
        return count($urls);
    }
    
    public function loadFromConfig(): array
    {
        $path = config_path('urls.json');
        
        if (!file_exists($path)) {
            if ($this->logProcess) {
                Log::warning("URLs config file not found: {$path}");
            }
            return [];
        }
        
        $data = json_decode(file_get_contents($path), true);
        $urls = $data['all'] ?? [];
        
        if ($this->logProcess) {
            Log::info("Loaded URLs from config", ['count' => count($urls)]);
        }
        
        return $urls;
    }
}