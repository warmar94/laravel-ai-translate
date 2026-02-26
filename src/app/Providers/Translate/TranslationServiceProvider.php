<?php

namespace App\Providers\Translate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use App\Services\Translate\MissingKeyBufferService;
use App\Services\Translate\StringExtractor;
use App\Jobs\Translate\ProcessMissingKeysJob;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register the MissingKeyBufferService as a singleton.
     * This service accumulates missing translation keys in memory
     * during a single request lifecycle — no disk or DB I/O.
     */
    public function register(): void
    {
        $this->app->singleton(MissingKeyBufferService::class);
    }

    /**
     * Hook into Laravel's translator to detect missing keys.
     *
     * Two collection modes:
     *   1. Runtime collection (config: extraction.runtime_collection = true)
     *      → Always intercepts missing keys from live user traffic
     *   2. Active scan only (runtime_collection = false)
     *      → Only collects when StringExtractor::$collectionMode is true
     *        (set by ScanUrlForStringsJob during dashboard "Collect Strings")
     *
     * Keys are buffered in memory, then dispatched as a single
     * ProcessMissingKeysJob after the response is sent.
     */
    public function boot(): void
    {
        $buffer = $this->app->make(MissingKeyBufferService::class);

        /*
        |----------------------------------------------------------------------
        | Missing Key Handler
        |----------------------------------------------------------------------
        |
        | Fires every time __() can't find a translation key.
        | Filters out Laravel internals, then buffers valid keys.
        |
        | Source locale keys (en) → saved to en.json by the job
        | Target locale keys (ar, es...) → saved to missing_translations table
        |
        */
        Lang::handleMissingKeysUsing(function (string $key, array $replace, ?string $locale, bool $fallbackUsed) use ($buffer) {
            $locale = $locale ?? app()->getLocale();
            $sourceLocale = config('translation.source_locale', 'en');

            // ── Collection Gate ─────────────────────────────────────────
            // If runtime collection is off AND we're not in an active scan,
            // skip entirely — zero overhead for normal requests.
            $runtimeCollection = config('translation.extraction.runtime_collection', false);
            if (!$runtimeCollection && !StringExtractor::$collectionMode) {
                return $key;
            }

            // ── Filter: Empty Keys ──────────────────────────────────────
            if (trim($key) === '') {
                return $key;
            }

            // ── Filter: File Path Artifacts ─────────────────────────────
            // Laravel sometimes resolves group translation files to full
            // Windows paths (e.g. "Users\\...\\lang\\en\\auth"). These
            // contain backslashes — real translation strings never do.
            if (str_contains($key, '\\')) {
                return $key;
            }

            // ── Filter: Bare Laravel Group Names ────────────────────────
            // When Laravel looks up "auth" before resolving "auth.failed",
            // the bare group name hits the handler first.
            $skipGroups = ['auth', 'pagination', 'passwords', 'validation', 'http-statuses'];
            if (in_array($key, $skipGroups, true)) {
                return $key;
            }

            // ── Filter: Laravel Internal Prefixes ───────────────────────
            // Dot-notation keys from Laravel's built-in lang files.
            $skipPrefixes = ['validation.', 'pagination.', 'passwords.', 'auth.', 'http-statuses.'];
            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    return $key;
                }
            }

            // ── Filter: Dot-Notation Group Keys ─────────────────────────
            // Keys like "messages.welcome" are file-based group translations,
            // not JSON keys. JSON keys use full sentences with spaces.
            if (preg_match('/^[a-z_]+\.[a-z_]+/i', $key) && !str_contains($key, ' ')) {
                return $key;
            }

            // ── Filter: HTTP Status Messages ────────────────────────────
            // 404/500 error pages trigger __() with these status messages.
            $skipExact = ['Not Found', 'Server Error', 'Forbidden', 'Unauthorized'];
            if (in_array($key, $skipExact, true)) {
                return $key;
            }

            // ── Buffer the Key ──────────────────────────────────────────
            if ($locale === $sourceLocale) {
                $buffer->addSourceKey($key);
            } else {
                $buffer->addTargetKey($key, $locale);
            }

            return $key;
        });

        /*
        |----------------------------------------------------------------------
        | Post-Response Dispatch
        |----------------------------------------------------------------------
        |
        | After the response is sent to the browser, dispatch a single job
        | with all buffered keys. This ensures:
        |   - Zero I/O during the request (no file writes, no DB queries)
        |   - One job per request (not one per key)
        |   - Silent failure — if dispatch fails, user is never affected
        |
        */
        $this->app->terminating(function () use ($buffer) {
            if (!$buffer->hasKeys()) {
                return;
            }

            try {
                ProcessMissingKeysJob::dispatch(
                    $buffer->getSourceKeys(),
                    $buffer->getTargetKeys(),
                    config('translation.source_locale', 'en')
                );
            } catch (\Throwable) {
                // Silent — translation collection must never affect the user
            }

            $buffer->flush();
        });
    }
}