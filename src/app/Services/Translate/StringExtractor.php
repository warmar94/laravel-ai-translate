<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Log;

/**
 * Visits URLs internally to trigger Laravel's translator.
 *
 * When extractFromUrl() is called (via ScanUrlForStringsJob),
 * it sets $collectionMode = true so the handleMissingKeysUsing
 * hook in TranslationServiceProvider collects keys — even when
 * runtime_collection is disabled in config.
 *
 * The $collectionMode flag is static so it's shared across the
 * entire process. It's wrapped in a try/finally to guarantee
 * cleanup even if the page render throws an exception.
 */
class StringExtractor
{
    /**
     * Static flag: true during active URL scans, false otherwise.
     * Checked by TranslationServiceProvider to gate key collection
     * when runtime_collection is disabled.
     */
    public static bool $collectionMode = false;

    protected array $languageFiles;
    protected bool $logProcess = false;

    public function __construct()
    {
        $this->languageFiles = config('translation.language_files', []);
        $this->logProcess = config('translation.log_process', false);
    }

    /**
     * Visit a URL internally via app()->handle().
     *
     * Enables collectionMode so the missing key handler buffers
     * every __() call that doesn't find a translation. The actual
     * saving happens via ProcessMissingKeysJob dispatched by
     * the terminating callback in TranslationServiceProvider.
     */
    public function extractFromUrl(string $url): void
    {
        if ($this->logProcess) {
            Log::info("Scanning URL: {$url}");
        }

        try {
            self::$collectionMode = true;

            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '/';
            $query = $parsedUrl['query'] ?? '';

            if ($query) {
                $path .= '?' . $query;
            }

            // Internal request — no HTTP, no network, no deadlock
            $request = \Illuminate\Http\Request::create($path, 'GET');
            app()->handle($request);

            if ($this->logProcess) {
                Log::info("Scanned: {$url}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to scan {$url}", [
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Always reset — even if the page render throws
            self::$collectionMode = false;
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
     * Get configured language file paths.
     */
    public function getLanguageFiles(): array
    {
        return $this->languageFiles;
    }
}