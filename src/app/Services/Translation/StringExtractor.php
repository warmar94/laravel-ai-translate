<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class StringExtractor
{
    public static bool $collectionMode = false;

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
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '/';
            $query = $parsedUrl['query'] ?? '';

            if ($query) {
                $path .= '?' . $query;
            }

            if ($this->logProcess) {
                Log::info("Parsed URL to path: {$path}");
            }

            self::$collectionMode = true;

            if (config('translation.extraction.clear_cache', true)) {
                Artisan::call('view:clear');
            }

            $request = \Illuminate\Http\Request::create($path, 'GET');
            $response = app()->handle($request);

            $html = $response->getContent();

            if ($this->logProcess) {
                Log::info("Got HTML response", ['length' => strlen($html)]);
            }

            preg_match_all('/<!--T_START:(.*?):T_END-->/', $html, $matches);

            $keys = array_map(function ($key) {
                return html_entity_decode($key, ENT_QUOTES, 'UTF-8');
            }, $matches[1]);

            $uniqueKeys = array_unique($keys);

            if ($this->logProcess) {
                Log::info("Extracted keys from {$url}", ['count' => count($uniqueKeys)]);
            }

            return $uniqueKeys;

        } catch (\Exception $e) {
            Log::error("Failed to extract strings from {$url}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        } finally {
            self::$collectionMode = false;
        }
    }

    public function saveToLanguageFile(array $keys, string $locale = null): int
    {
        if (!$locale) {
            $locale = config('translation.source_locale', 'en');
        }

        $filePath = $this->languageFiles[$locale] ?? lang_path("{$locale}.json");

        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?? [];
        }

        $newCount = 0;
        foreach ($keys as $key) {
            if (!isset($existing[$key])) {
                $existing[$key] = $key;
                $newCount++;
            }
        }

        ksort($existing);

        file_put_contents(
            $filePath,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if ($this->logProcess) {
            Log::info("Saved {$newCount} new keys to {$locale}.json", ['total' => count($existing)]);
        }

        return $newCount;
    }

    public function getAllKeys(string $locale = null): array
    {
        if (!$locale) {
            $locale = config('translation.source_locale', 'en');
        }

        $filePath = $this->languageFiles[$locale] ?? lang_path("{$locale}.json");

        if (!file_exists($filePath)) {
            return [];
        }

        return json_decode(file_get_contents($filePath), true) ?? [];
    }

    public function getLanguageFiles(): array
    {
        return $this->languageFiles;
    }
}