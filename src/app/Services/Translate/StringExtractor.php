<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Log;

class StringExtractor
{
    protected array $languageFiles;
    protected bool $logProcess = false;

    public function __construct()
    {
        $this->languageFiles = config('translation.language_files', []);
        $this->logProcess = config('translation.log_process', false);
    }

    /**
     * Visit a URL internally. Laravel's missing key handler
     * in TranslationServiceProvider collects the strings.
     */
    public function extractFromUrl(string $url): void
    {
        if ($this->logProcess) {
            Log::info("Scanning URL: {$url}");
        }

        try {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '/';
            $query = $parsedUrl['query'] ?? '';

            if ($query) {
                $path .= '?' . $query;
            }

            $request = \Illuminate\Http\Request::create($path, 'GET');
            app()->handle($request);

            if ($this->logProcess) {
                Log::info("Scanned: {$url}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to scan {$url}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all keys from a language JSON file.
     */
    public function getAllKeys(string $locale = null): array
    {
        $locale = $locale ?? config('translation.source_locale', 'en');
        $filePath = $this->languageFiles[$locale] ?? lang_path("{$locale}.json");

        if (!file_exists($filePath)) {
            return [];
        }

        return json_decode(file_get_contents($filePath), true) ?? [];
    }

    /**
     * Get configured language files.
     */
    public function getLanguageFiles(): array
    {
        return $this->languageFiles;
    }
}