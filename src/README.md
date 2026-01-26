# Laravel AI Translation System

Complete multilingual framework for Laravel with AI translation, automatic routing, SEO optimization, and RTL support.

## Installation

### 1. Install via Composer
```bash
composer require warmar/laravel-ai-translate
```

### 2. Publish Config & Migration
```bash
php artisan vendor:publish --provider="Warmar\LaravelAiTranslate\LaravelAiTranslateServiceProvider"
```

### 3. Run Migration
```bash
php artisan migrate
```

### 4. Add to .env
```env
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini
TRANSLATION_COLLECTION_MODE=false
TRANSLATION_LOG_PROCESS=false
```

### 5. Register Middleware

Add to `bootstrap/app.php`:
```php
$middleware->alias([
    'language' => LanguageMiddleware::class,
]);
```

### 6. Add langRoute Helper

Add this function to your `routes/web.php`:
```php
function langRoute($method, $path, $action, $name = null, $where = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    
    $mainRoute = Route::$method($path, $action)->middleware('language');
    if ($name) $mainRoute->name($name);
    if (!empty($where)) $mainRoute->where($where);
    
    foreach ($allowedLangs as $lang) {
        $langRoute = Route::$method('/' . $lang . $path, $action)->middleware('language');
        if ($name) $langRoute->name($lang . '.' . $name);
        if (!empty($where)) $langRoute->where($where);
    }
    
    return $mainRoute;
}
```

### 7. Register @__t() Directive & View Helpers

Add to your `app/Providers/AppServiceProvider.php` boot method:
```php
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

public function boot(): void
{
    // Register @__t() directive
    Blade::directive('__t', function ($expression) {
        if (config('translation.translation_collection_mode')) {
            return "<?php echo '<!--T_START:' . {$expression} . ':T_END-->' . {$expression}; ?>";
        }
        return "<?php echo __({$expression}); ?>";
    });

    // Register view helpers
    View::composer('*', function ($view) {
        $langCode = app()->getLocale();
        $isRtl = in_array($langCode, config('translation.rtl_languages', []));
        
        $view->with('langCode', $langCode);
        $view->with('isRtl', $isRtl);
        
        $view->with('langUrl', function ($routeName, $parameters = []) use ($langCode) {
            if ($langCode === 'en') {
                return route($routeName, $parameters);
            }
            return route($langCode . '.' . $routeName, $parameters);
        });
        
        $view->with('isRoute', function ($routeName) use ($langCode) {
            $currentRoute = request()->route()?->getName();
            if (!$currentRoute) return false;
            
            return $currentRoute === $routeName || 
                   $currentRoute === $langCode . '.' . $routeName;
        });
    });
}
```

## Usage

### Create Language Routes
```php
langRoute('get', '/', HomePage::class, 'home');
langRoute('get', '/about', About::class, 'about');
langRoute('get', '/products/{slug}', ProductShow::class, 'products.show');
```

### Mark Strings for Translation
```blade
<h1>@__t('Welcome to our website')</h1>
<p>@__t('We provide the best service')</p>
```

### Use View Helpers
```blade
<a href="{{ $langUrl('about') }}">@__t('About')</a>

@if($isRtl)
    <div dir="rtl">{{ $content }}</div>
@endif
```

### Access Translation Dashboard

Navigate to `/translation-dashboard` to manage translations.

## Full Documentation

[View complete documentation](https://github.com/warmar/laravel-ai-translate)

## License

MIT