<?php

//Default
use Illuminate\Support\Facades\Route;

// Declare Livewire Translator class here
use App\Livewire\Translate\TranslateMenu;

/*
|--------------------------------------------------------------------------
| Language Route Helper
|--------------------------------------------------------------------------
|
| Creates both non-prefixed (English) and language-prefixed routes
| for all configured languages. Example: /about and /ar/about
|
| IMPORTANT: Always pass middleware as the 6th parameter — do NOT chain
| ->middleware() on the return value, as that only applies to the
| English route. The $middleware parameter applies to ALL language routes.
|
| Usage:
|   langRoute('get', '/about', About::class, 'about');
|   langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth']);
|   langRoute('get', '/user/{id}', User::class, 'user.show', ['id' => '[0-9]+'], ['auth']);
|
*/
function langRoute($method, $path, $action, $name = null, $where = [], $middleware = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    $allMiddleware = array_merge(['language'], (array) $middleware);
    
    // Main route (no language prefix - English)
    $mainRoute = Route::$method($path, $action)->middleware($allMiddleware);
    if ($name) $mainRoute->name($name);
    if (!empty($where)) $mainRoute->where($where);
    
    // Language-prefixed routes
    foreach ($allowedLangs as $lang) {
        $langRoute = Route::$method('/' . $lang . $path, $action)->middleware($allMiddleware);
        if ($name) $langRoute->name($lang . '.' . $name);
        if (!empty($where)) $langRoute->where($where);
    }
    
    return $mainRoute;
}

/*
=====================================================
|                   PAGE ROUTES                     |
=====================================================
*/

// Example: public page with translation syntax
langRoute('get', '/home', fn () => view('home'));

/*
=====================================================
|             AUTHENTICATED ROUTES                  |
|                                                   |
|  Pass middleware as 6th parameter to langRoute()  |
|  so it applies to ALL language-prefixed routes.   |
=====================================================
*/

// langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth', 'verified']);
// langRoute('get', '/profile', Profile::class, 'profile', [], ['auth']);

/*
=====================================================
|                TRANSLATION ROUTES                 |
|             (No language prefix needed)           |
=====================================================
*/

// The Translation Dashboard
// Simple Livewire component to manage all your translations
Route::get('/translation-dashboard', TranslateMenu::class)
    ->name('translation.dashboard');