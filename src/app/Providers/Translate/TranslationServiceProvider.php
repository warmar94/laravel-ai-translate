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
        | Fires only when __() can't find a key in JSON or group PHP
        | files — Laravel resolves built-in keys (auth.*, validation.*,
        | etc.) before this callback is ever reached.
        |
        | Source keys (en) → saved to en.json by the job
        | Target keys (ar, es...) → saved to missing_translations table
        |
        */
        Lang::handleMissingKeysUsing(function (string $key, array $replace, ?string $locale, bool $fallbackUsed) use ($buffer) {
            $locale = $locale ?? app()->getLocale();
            $sourceLocale = config('translation.source_locale', 'en');

            /*
            |------------------------------------------------------------------
            | Collection Gate
            |------------------------------------------------------------------
            |
            | If runtime collection is off AND we're not in an active scan,
            | skip entirely — zero overhead for normal requests.
            |
            */
            $runtimeCollection = config('translation.extraction.runtime_collection', false);

            if (!$runtimeCollection && !StringExtractor::$collectionMode) {
                return $key;
            }

            /*
            |------------------------------------------------------------------
            | Filter: Empty Keys
            |------------------------------------------------------------------
            |
            | Edge case — __('') or dynamically generated empty strings.
            |
            */
            if (trim($key) === '') {
                return $key;
            }

            /*
            |------------------------------------------------------------------
            | Filter: Vendor Package Keys
            |------------------------------------------------------------------
            |
            | Keys like "package::group.key" come from third-party packages
            | with missing translations. These are noise we don't control
            | and shouldn't collect into our own translation files.
            |
            */
            if (str_contains($key, '::')) {
                return $key;
            }

            /*
            |------------------------------------------------------------------
            | Buffer the Key
            |------------------------------------------------------------------
            |
            | Source locale (en) keys are new strings to add to en.json.
            | Target locale keys are strings that exist in source but
            | haven't been translated yet for the requested locale.
            |
            */
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