<?php

namespace App\Jobs\Translate;

use App\Models\Translate\MissingTranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes buffered missing translation keys.
 *
 * Dispatched by TranslationServiceProvider after each request.
 * Handles two types of keys:
 *
 *   Source keys (en) → Added to lang/en.json with exclusive file locking
 *                      to prevent race conditions between concurrent jobs.
 *
 *   Target keys (ar, es...) → Recorded in the missing_translations table
 *                              via MissingTranslation::record() (upsert).
 *
 * File locking (LOCK_EX) ensures only one job writes to en.json at a time.
 * Laravel's translator reads the file with plain file_get_contents() which
 * doesn't use flock — so the app is never blocked by the lock.
 */
class ProcessMissingKeysJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        protected array $sourceKeys,
        protected array $targetKeys,
        protected string $sourceLocale
    ) {}

    public function handle(): void
    {
        // ── Source Keys → lang/en.json ──────────────────────────────
        if (!empty($this->sourceKeys)) {
            try {
                $path = lang_path("{$this->sourceLocale}.json");

                // Open with 'c+' — create if missing, don't truncate
                $fp = fopen($path, 'c+');
                if ($fp && flock($fp, LOCK_EX)) {
                    // Read current contents while holding the lock
                    $contents = stream_get_contents($fp);
                    $translations = $contents ? json_decode($contents, true) ?? [] : [];

                    // Only add keys that don't already exist
                    $added = 0;
                    foreach ($this->sourceKeys as $key) {
                        if (!array_key_exists($key, $translations)) {
                            $translations[$key] = $key;
                            $added++;
                        }
                    }

                    // Write back only if we actually added something
                    if ($added > 0) {
                        ksort($translations);
                        ftruncate($fp, 0);
                        rewind($fp);
                        fwrite($fp, json_encode(
                            $translations,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        ));
                    }

                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            } catch (\Throwable $e) {
                Log::error('ProcessMissingKeysJob: Failed to write source keys', [
                    'error' => $e->getMessage(),
                    'count' => count($this->sourceKeys),
                ]);
            }
        }

        // ── Target Keys → missing_translations table ────────────────
        // MissingTranslation::record() is an upsert — if the key+locale
        // already exists, it increments occurrences and updates last_seen.
        foreach ($this->targetKeys as $entry) {
            try {
                MissingTranslation::record($entry['key'], $entry['locale']);
            } catch (\Throwable $e) {
                Log::error('ProcessMissingKeysJob: Failed to record missing key', [
                    'key' => $entry['key'],
                    'locale' => $entry['locale'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessMissingKeysJob permanently failed', [
            'error' => $exception->getMessage(),
            'source_keys' => count($this->sourceKeys),
            'target_keys' => count($this->targetKeys),
        ]);
    }
}