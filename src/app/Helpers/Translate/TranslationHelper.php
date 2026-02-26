<?php

/*
|--------------------------------------------------------------------------
| Translation related helper functions
|--------------------------------------------------------------------------
|
| Helper functions and code to expose varaibles and data
| to frontend files (instead of View::share)
|
*/

use Illuminate\Support\Facades\Route;

if (!function_exists('langCode')) {
    function langCode(): string
    {
        return app()->getLocale();
    }
}

if (!function_exists('isRtl')) {
    function isRtl(): bool
    {
        return in_array(app()->getLocale(), config('translation.rtl_languages', []));
    }
}

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