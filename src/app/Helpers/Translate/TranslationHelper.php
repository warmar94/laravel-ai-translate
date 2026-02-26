<?php

/*
|--------------------------------------------------------------------------
| Translation Helper Functions
|--------------------------------------------------------------------------
|
| Global helpers for the translation system. Registered via
| composer.json autoload "files" directive.
|
| Available everywhere: Blade templates, controllers, services.
|
*/

use Illuminate\Support\Facades\Route;

/**
 * Get the current locale code.
 * Usage: {{ langCode() }} → "en", "ar", "es"
 */
if (!function_exists('langCode')) {
    function langCode(): string
    {
        return app()->getLocale();
    }
}

/**
 * Check if the current locale is a right-to-left language.
 * Usage: @if(isRtl()) <div dir="rtl">...</div> @endif
 */
if (!function_exists('isRtl')) {
    function isRtl(): bool
    {
        return in_array(app()->getLocale(), config('translation.rtl_languages', []));
    }
}

/**
 * Generate a language-prefixed URL for a named route.
 * English (source) routes have no prefix, others get /{lang}/...
 *
 * Usage: <a href="{{ langUrl('about') }}">About</a>
 *   en → /about
 *   ar → /ar/about
 */
if (!function_exists('langUrl')) {
    function langUrl(string $routeName, array $params = []): string
    {
        $lang = app()->getLocale();
        if ($lang === 'en') {
            return route($routeName, $params);
        }
        return route($lang . '.' . $routeName, $params);
    }
}

/**
 * Check if the current route matches a given name,
 * accounting for language-prefixed route variants.
 *
 * Usage: @if(isRoute('about')) <li class="active">About</li> @endif
 * Matches: "about", "ar.about", "es.about", etc.
 */
if (!function_exists('isRoute')) {
    function isRoute(string $routeName): bool
    {
        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName === $routeName) {
            return true;
        }

        $allowedLangs = array_keys(config('translation.languages'));
        foreach ($allowedLangs as $lang) {
            if ($currentRouteName === $lang . '.' . $routeName) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Check if the string extractor is currently in collection mode.
 * True only during active URL scans (ScanUrlForStringsJob).
 *
 * Usage: Primarily internal — used by TranslationServiceProvider
 * to gate key collection when runtime_collection is disabled.
 */
if (!function_exists('isCollectionMode')) {
    function isCollectionMode(): bool
    {
        return \App\Services\Translate\StringExtractor::$collectionMode;
    }
}