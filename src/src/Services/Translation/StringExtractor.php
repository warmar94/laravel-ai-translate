<?php
// app/Services/Translation/StringExtractor.php

namespace Warmar\LaravelAiTranslate\Services\Translation;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class StringExtractor
{
    protected array $languageFiles;
    protected $logProcess = false;
    
    public function __construct()
    {
        $this->languageFiles = config('translation.language_files', []);
        $this->logProcess = config('translation.log_process', false);
        
        if ($this->logProcess) {
            Log::info('StringExtractor initialized', ['language_files' => $this->languageFiles]);
        }
    }
    
    public function extractFromUrl(string $url): array
    {
        if ($this->logProcess) {
            Log::info("Starting extraction from URL: {$url}");
        }
        
        try {
            // Parse URL to get path
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '/';
            $query = $parsedUrl['query'] ?? '';
            
            if ($query) {
                $path .= '?' . $query;
            }
            
            if ($this->logProcess) {
                Log::info("Parsed URL to path: {$path}");
            }
            
            // Enable collection mode temporarily
            Config::set('app.translation_collection_mode', true);
            
            if ($this->logProcess) {
                Log::info("Collection mode enabled");
            }
            
            // Clear view cache to ensure directive works
            if (config('translation.extraction.clear_cache', true)) {
                Artisan::call('view:clear');
                
                if ($this->logProcess) {
                    Log::info("View cache cleared");
                }
            }
            
            // Make internal request
            if ($this->logProcess) {
                Log::info("Making internal request to: {$path}");
            }
            
            $request = \Illuminate\Http\Request::create($path, 'GET');
            $response = app()->handle($request);
            
            // Disable collection mode
            Config::set('app.translation_collection_mode', false);
            
            if ($this->logProcess) {
                Log::info("Collection mode disabled");
            }
            
            $html = $response->getContent();
            
            if ($this->logProcess) {
                Log::info("Got HTML response", ['length' => strlen($html)]);
            }
            
            // Extract all strings between HTML comment markers
            preg_match_all('/<!--T_START:(.*?):T_END-->/', $html, $matches);
            
            // Decode HTML entities
            $keys = array_map(function($key) {
                return html_entity_decode($key, ENT_QUOTES, 'UTF-8');
            }, $matches[1]);
            
            // Return unique keys
            $uniqueKeys = array_unique($keys);
            
            if ($this->logProcess) {
                Log::info("Extracted keys from {$url}", ['count' => count($uniqueKeys), 'keys' => $uniqueKeys]);
            }
            
            return $uniqueKeys;
            
        } catch (\Exception $e) {
            Log::error("Failed to extract strings from {$url}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    public function saveToLanguageFile(array $keys, string $locale = null): int
    {
        // Default to source locale if not specified
        if (!$locale) {
            $locale = config('translation.source_locale', 'en');
        }
        
        if ($this->logProcess) {
            Log::info("Saving keys to {$locale}.json", ['key_count' => count($keys)]);
        }
        
        // Get file path from config
        $filePath = $this->languageFiles[$locale] ?? lang_path("{$locale}.json");
        
        if ($this->logProcess) {
            Log::info("Using file path: {$filePath}");
        }
        
        // Load existing translations
        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?? [];
            
            if ($this->logProcess) {
                Log::info("Loaded existing translations", ['count' => count($existing)]);
            }
        } else {
            if ($this->logProcess) {
                Log::info("No existing file found, creating new");
            }
        }
        
        // Add new keys (key = value for source language)
        $newCount = 0;
        foreach ($keys as $key) {
            if (!isset($existing[$key])) {
                $existing[$key] = $key;
                $newCount++;
            }
        }
        
        if ($this->logProcess) {
            Log::info("New keys to add: {$newCount}");
        }
        
        // Sort alphabetically for easier management
        ksort($existing);
        
        // Save back to file
        $saved = file_put_contents(
            $filePath,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        
        if ($this->logProcess) {
            Log::info("File saved", ['bytes_written' => $saved, 'total_keys' => count($existing)]);
        }
        
        return $newCount;
    }
    
    public function getAllKeys(string $locale = null): array
    {
        // Default to source locale if not specified
        if (!$locale) {
            $locale = config('translation.source_locale', 'en');
        }
        
        $filePath = $this->languageFiles[$locale] ?? lang_path("{$locale}.json");
        
        if (!file_exists($filePath)) {
            if ($this->logProcess) {
                Log::warning("Language file not found: {$filePath}");
            }
            return [];
        }
        
        $keys = json_decode(file_get_contents($filePath), true) ?? [];
        
        if ($this->logProcess) {
            Log::info("Loaded keys from {$locale}.json", ['count' => count($keys)]);
        }
        
        return $keys;
    }
    
    public function getLanguageFiles(): array
    {
        return $this->languageFiles;
    }
}