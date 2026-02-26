<?php

namespace App\Providers\Translate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Models\Translate\MissingTranslation;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        Lang::handleMissingKeysUsing(function (string $key, array $replace, ?string $locale, bool $fallbackUsed) {

            $locale = $locale ?? app()->getLocale();
            $sourceLocale = config('translation.source_locale', 'en');

            // Skip empty keys
            if (trim($key) === '') {
                return $key;
            }

            // Skip Laravel internal translation groups
            $skipPrefixes = ['validation.', 'pagination.', 'passwords.', 'auth.', 'http-statuses.'];
            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    return $key;
                }
            }

            // Skip keys that look like file-based groups (e.g. "messages.welcome")
            // JSON translations use full sentences, not dot-notation group keys
            if (preg_match('/^[a-z_]+\.[a-z_]+/i', $key) && !str_contains($key, ' ')) {
                return $key;
            }

            // Source language → add to en.json
            if ($locale === $sourceLocale) {
                try {
                    $path = lang_path("{$sourceLocale}.json");
                    $translations = file_exists($path)
                        ? json_decode(file_get_contents($path), true) ?? []
                        : [];

                    if (!array_key_exists($key, $translations)) {
                        $translations[$key] = $key;
                        ksort($translations);
                        file_put_contents(
                            $path,
                            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        );
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to collect source key', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }

                return $key;
            }

            // Target language → record in translations_missing table
            try {
                MissingTranslation::record($key, $locale);
            } catch (\Throwable $e) {
                Log::error('Failed to record missing translation', [
                    'key' => $key,
                    'locale' => $locale,
                    'error' => $e->getMessage(),
                ]);
            }

            return $key;
        });
    }
}