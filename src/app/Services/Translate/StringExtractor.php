<?php

namespace App\Services\Translate;

use App\Jobs\Translate\ProcessMissingKeysJob;
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
 *
 * KEY DESIGN NOTE — why we bypass terminating() here:
 *
 * This service runs inside a queue worker (ScanUrlForStringsJob).
 * The terminating() callbacks registered on $app are worker-process-level,
 * not per-handle() call — they fire when the worker process itself exits,
 * not after each internal app()->handle(). Relying on terminating() would
 * mean keys accumulate in the buffer but ProcessMissingKeysJob is never
 * dispatched until the worker dies.
 *
 * Instead, after app()->handle() returns, we pull the buffered keys and
 * dispatch ProcessMissingKeysJob::dispatchSync() inline — right now, while
 * we still have the keys. The buffer is then flushed so the next URL scan
 * starts clean.
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
     * Sets collectionMode so the missing key handler buffers every __() call
     * that doesn't find a translation. After handle() returns, keys are
     * dispatched synchronously via ProcessMissingKeysJob::dispatchSync()
     * rather than relying on terminating() — which does not fire per-handle()
     * in a queue worker context.
     */
    public function extractFromUrl(string $url): void
    {
        if ($this->logProcess) {
            Log::info("Scanning URL: {$url}");
        }

        // Flush before each URL so stale keys from a previous scan
        // (e.g. a partial retry) don't bleed into this one.
        $buffer = app(MissingKeyBufferService::class);
        $buffer->flush();

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

            // Dispatch synchronously here — do NOT rely on terminating().
            // In a worker, terminating() fires on process exit, not per handle().
            $sourceKeys = $buffer->getSourceKeys();
            $targetKeys = $buffer->getTargetKeys();

            if ($this->logProcess) {
                Log::info("Scanned: {$url}", [
                    'source_keys' => count($sourceKeys),
                    'target_keys' => count($targetKeys),
                ]);
            }

            if (!empty($sourceKeys) || !empty($targetKeys)) {
                ProcessMissingKeysJob::dispatchSync(
                    $sourceKeys,
                    $targetKeys,
                    config('translation.source_locale', 'en')
                );
            }

        } catch (\Exception $e) {
            Log::error("Failed to scan {$url}", [
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Always reset and flush — even if the page render or job throws
            self::$collectionMode = false;
            $buffer->flush();
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