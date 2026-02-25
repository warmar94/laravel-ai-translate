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
| This helper creates both non-prefixed and language-prefixed routes
| Example: /about and /ar/about
|
*/
function langRoute($method, $path, $action, $name = null, $where = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    
    // 1. Main route (no language prefix)
    $mainRoute = Route::$method($path, $action)->middleware('language');
    if ($name) $mainRoute->name($name);
    if (!empty($where)) $mainRoute->where($where);
    
    // 2. Prefixed routes for each language
    foreach ($allowedLangs as $lang) {
        $langRoute = Route::$method('/' . $lang . $path, $action)->middleware('language');
        if ($name) $langRoute->name($lang . '.' . $name);
        if (!empty($where)) $langRoute->where($where);
    }
    
    return $mainRoute;
}

/*
=====================================================
|                TRANSLATION ROUTES                 |
|                                                   |
=====================================================
*/

// Example Page with syntrax to use 
langRoute('get', '/home', fn () => view('home'));

// The Translation Dashboard (no need to translate)
// Simple Livewire component to manage all your translations
Route::get('/translation-dashboard', TranslateMenu::class)
    ->name('translation.dashboard');