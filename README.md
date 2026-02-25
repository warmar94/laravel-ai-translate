# Laravel AI Translation System

A **complete multilingual framework** for Laravel applications. This isn't just a translation tool - it's a full-featured localization system with AI-powered translations, automatic routing, SEO optimization, and RTL support out of the box.

**Built on top of Laravel's default translation system, but adds:**
- ✅ Automatic language-prefixed routing (`/about`, `/ar/about`)
- ✅ Smart language detection middleware
- ✅ AI-powered translation with OpenAI
- ✅ Automatic string collection from rendered pages
- ✅ Real-time translation dashboard
- ✅ Complete SEO implementation (hreflang, canonical URLs)
- ✅ RTL language support
- ✅ Global helper functions for easy integration

## 🌟 Features

### 🎯 Core Translation Features
- 🤖 **AI-Powered Translations**: Leverages OpenAI GPT-4 for context-aware, high-quality translations
- 🔍 **Automatic String Collection**: Intelligently scans your application and collects all translatable strings
- 🌐 **Multi-Language Support**: Translate your entire application into unlimited languages
- 📊 **Real-Time Dashboard**: Beautiful Livewire-powered interface with live progress tracking
- ⚡ **Queue-Based Processing**: Scalable batch processing for thousands of strings
- 🎯 **Custom Blade Directive**: Simple `@__t()` syntax for marking translatable strings

### 🚀 Advanced Routing & Localization
- 🛣️ **Smart Language Routing**: Automatic language-prefixed URLs (`/about`, `/ar/about`, `/es/about`)
- 🔧 **Custom `langRoute()` Helper**: One function to create all language routes automatically
- 🌍 **Language Middleware**: Intelligent language detection and switching
- 📝 **SEO-Optimized**: Auto-generated hreflang tags, canonical URLs, and x-default handling
- 🔄 **Language Switcher**: Built-in helpers for creating language selection menus
- ↔️ **RTL Support**: Full right-to-left language support with automatic detection
- 🎨 **Global Helper Functions**: `langUrl()`, `isRtl()`, `isRoute()`, `langCode()`, and `isCollectionMode()` available everywhere

## 📋 Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [File Structure](#file-structure)
- [Configuration](#configuration)
- [Routing System](#routing-system)
- [Language Middleware](#language-middleware)
- [Usage](#usage)
- [View Helpers](#view-helpers)
- [How It Works](#how-it-works)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)
- [API Reference](#api-reference)

## ⚙️ Requirements

- PHP 8.2+
- Laravel 11+ / 12+
- OpenAI API Key
- Queue worker (Redis recommended, Database queue supported)
- Livewire 3.x / 4.x


## 📦 Installation

### 1. Publish Laravel's Default Localization

```bash
php artisan lang:publish
```

### 2. Install the package via Composer
```bash
composer require warmar/laravel-ai-translate
```

### 3. Install the package assets
```bash
php artisan ai-translate:install
```

### 4. Register the Service Provider

Add the `TranslationServiceProvider` to your `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Translate\TranslationServiceProvider::class,
];
```

> **Note:** The package ships with its own `TranslationServiceProvider` that registers the `@__t()` Blade directive and all translation functionality. You do **not** need to modify your `AppServiceProvider`.

### 5. Register Language Middleware

Add the language middleware to `bootstrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

//Declare the middleware
use App\Http\Middleware\Translate\LanguageMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add language middleware
        $middleware->alias([
            'language' => LanguageMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

```

### 6. Configure Environment

Add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

That's it for `.env`. All other translation settings are managed directly in `config/translation.php` (see [Configuration](#configuration)).

> **Note:** Collection mode, URL delay, and logging are configured in `config/translation.php` — not in `.env`. Collection mode is handled automatically at runtime by the string extraction system and requires no manual toggling.

### 7. Register Global Helper Functions

The package uses globally autoloaded helper functions instead of `View::share`. Add the helper file to your `composer.json` autoload section:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Helpers/Translate/TranslationHelper.php"
        ]
    }
}
```

Then regenerate the autoload files:

```bash
composer dump-autoload
```

The helper file provides these global functions for use in Blade templates, controllers, Livewire components, and anywhere else in your application:

| Function | Returns | Description |
|---|---|---|
| `langCode()` | `string` | Current locale code (`'en'`, `'ar'`, etc.) |
| `isRtl()` | `bool` | Whether current locale is RTL |
| `langUrl($route, $params)` | `string` | Language-prefixed URL for a named route |
| `isRoute($name)` | `bool` | Whether current route matches (works with language prefixes) |
| `isCollectionMode()` | `bool` | Whether string collection is currently active |

### 8. Configure Queue Workers

**Development Environment:**

If using `composer run dev`, queue workers are typically already running.

**Production/Dedicated Server:**

Start queue workers manually:

```bash
php artisan queue:work
```

(For production, use a process manager like Supervisor)

### 9. Create Required Database Tables
If you installed via Laravel Command skip this Step. Raw SQL also provided.

Create a migration for the `translation_progress` table:

```bash
php artisan make:migration create_translation_progress_table
```

Add the following schema:

```php
Schema::create('translation_progress', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['url_collection', 'string_extraction', 'translation']);
    $table->string('locale', 10)->nullable();
    $table->unsignedInteger('total')->default(0);
    $table->unsignedInteger('completed')->default(0);
    $table->unsignedInteger('failed')->default(0);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('updated_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    
    $table->unique(['type', 'locale']);
});
```

Run the migration:

```bash
php artisan migrate
```

### 10. Install Required Files
If you installed via Laravel Command skip this Step.
Copy the following files to your Laravel application:

**Configuration:**
- `config/translation.php`

**Service Providers:**
- `app/Providers/Translate/TranslationServiceProvider.php`

**Helpers:**
- `app/Helpers/Translate/TranslationHelper.php` (contains translation helper functions)

**Services:**
- `app/Services/Translate/StringExtractor.php`
- `app/Services/Translate/URLCollector.php`
- `app/Services/Translate/AITranslator.php`

**Jobs:**
- `app/Jobs/Translate/ScanUrlForStringsJob.php`
- `app/Jobs/Translate/TranslateStringBatchJob.php`

**Middleware:**
- `app/Http/Middleware/Translate/LanguageMiddleware.php`

**Livewire Components:**
- `app/Livewire/Translate/TranslateMenu.php`
- `resources/views/livewire/translation/translate-menu.blade.php`

**Blade Views:**
- `resources/views/lang.blade.php`


### 11. Clear Cache

Clear configuration and view caches:

```bash
php artisan view:clear
php artisan config:clear
php artisan config:cache
```

## 📁 File Structure

```
your-laravel-app/
│
├── app/
│   ├── Helpers/
│   │   └── Translate/
│   │       └── TranslationHelper.php              # Global helper functions (langCode, isRtl, etc.)
│   │
│   ├── Http/
│   │   └── Middleware/
│   │       └── Translate/
│   │           └── LanguageMiddleware.php          # Handles language detection and routing
│   │
│   ├── Jobs/
│   │   └── Translate/
│   │       ├── ScanUrlForStringsJob.php            # Extracts strings from URLs
│   │       └── TranslateStringBatchJob.php         # Translates string batches
│   │
│   ├── Livewire/
│   │   └── Translate/
│   │       └── TranslateMenu.php                   # Dashboard controller
│   │
│   ├── Providers/
│   │   └── Translate/
│   │       └── TranslationServiceProvider.php      # Registers @__t() directive
│   │
│   └── Services/
│       └── Translate/
│           ├── AITranslator.php                    # OpenAI API integration
│           ├── StringExtractor.php                 # String extraction logic
│           └── URLCollector.php                    # URL collection logic
│
├── config/
│   ├── translation.php                             # Main configuration file
│   └── urls.json                                   # Generated URL list (auto-created)
│
├── lang/
│   ├── en.json                                     # English strings (source)
│   ├── ar.json                                     # Arabic translations
│   └── [locale].json                               # Additional languages
│
├── resources/
│   └── views/
│       ├── lang.blade.php                          # SEO hreflang tags
│       └── livewire/
│           └── translation/
│               └── translate-menu.blade.php        # Dashboard UI
│
└── routes/
    └── web.php                                     # Route definitions with langRoute()
```

## 🛣️ Routing System

### The `langRoute()` Helper

The system includes a powerful `langRoute()` helper that automatically creates routes for all configured languages. Copy this into your `routes/web.php`:

```php
/**
 * Language Route Helper
 * Creates both non-prefixed (English) and language-prefixed routes
 * Example: /about and /ar/about
 *
 * IMPORTANT: Always pass middleware as the 6th parameter — do NOT chain
 * ->middleware() on the return value, as that only applies to the
 * English route. The $middleware parameter applies to ALL language routes.
 */
function langRoute($method, $path, $action, $name = null, $where = [], $middleware = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    $allMiddleware = array_merge(['language'], (array) $middleware);
    
    // 1. Main route (no language prefix - English)
    $mainRoute = Route::$method($path, $action)->middleware($allMiddleware);
    if ($name) $mainRoute->name($name);
    if (!empty($where)) $mainRoute->where($where);
    
    // 2. Prefixed routes for each language
    foreach ($allowedLangs as $lang) {
        $langRoute = Route::$method('/' . $lang . $path, $action)->middleware($allMiddleware);
        if ($name) $langRoute->name($lang . '.' . $name);
        if (!empty($where)) $langRoute->where($where);
    }
    
    return $mainRoute;
}
```

### Function Signature

```php
langRoute(
    string $method,       // HTTP method: 'get', 'post', 'put', 'delete', etc.
    string $path,         // URL path: '/about', '/products/{slug}'
    mixed  $action,       // Controller or Livewire class
    ?string $name,        // Route name (optional)
    array  $where,        // Where constraints (optional): ['id' => '[0-9]+']
    array  $middleware     // Additional middleware (optional): ['auth', 'verified']
): \Illuminate\Routing\Route
```

### Using `langRoute()` in Your Routes

Instead of defining routes multiple times for each language, use `langRoute()`:

```php
// Old way (repetitive):
Route::get('/about', About::class)->name('about');
Route::get('/ar/about', About::class)->name('ar.about');
Route::get('/es/about', About::class)->name('es.about');

// New way (automatic):
langRoute('get', '/about', About::class, 'about');
```

This **automatically creates**:
- `/about` → English (canonical)
- `/ar/about` → Arabic
- `/es/about` → Spanish (if configured)

### Route Examples

```php
// Simple routes (public, no extra middleware)
langRoute('get', '/', HomePage::class, 'home');
langRoute('get', '/contact', Contact::class, 'contact');
langRoute('get', '/about', About::class, 'about');

// Routes with parameters
langRoute('get', '/products/{slug}', ProductShow::class, 'products.show');
langRoute('get', '/blog/{category}/{slug}', BlogPost::class, 'blog.post');

// Routes with middleware (passed as 6th parameter)
langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth', 'verified']);
langRoute('get', '/profile', Profile::class, 'profile', [], ['auth']);
langRoute('get', '/admin', Admin::class, 'admin', [], ['admin']);

// Routes with where constraints (5th parameter)
langRoute('get', '/user/{id}', UserProfile::class, 'user.profile', ['id' => '[0-9]+']);

// Routes with BOTH where constraints AND middleware
langRoute('get', '/orders/{orderNumber}', OrderDetail::class, 'orders.show', [], ['auth', 'verified']);

// POST routes work too
langRoute('post', '/contact', ContactSubmit::class, 'contact.submit');
```

> ⚠️ **IMPORTANT — Middleware Security**
>
> Never chain `->middleware()` on `langRoute()`. The return value is only the English (non-prefixed) route — all language-prefixed routes (`/ar/...`, `/de/...`, etc.) are created inside the function and never returned. Chaining middleware only protects the English route while leaving every other language completely unprotected.
>
> ```php
> // ❌ WRONG — only English route gets auth, /ar/dashboard is unprotected!
> langRoute('get', '/dashboard', Dashboard::class, 'dashboard')
>     ->middleware(['auth']);
>
> // ✅ CORRECT — all language routes get auth
> langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth']);
> ```

### Generating Language-Specific URLs

Use the `langUrl()` helper in your views:

```blade
{{-- Generates URL for current language --}}
<a href="{{ langUrl('about') }}">About Us</a>

{{-- With parameters --}}
<a href="{{ langUrl('products.show', ['slug' => $product->slug]) }}">
    {{ $product->name }}
</a>

{{-- Result (if current language is Arabic): --}}
{{-- /ar/about --}}
{{-- /ar/products/example-product --}}
```

## 🔐 Language Middleware

The `LanguageMiddleware` handles language detection and URL management automatically.

### How It Works

1. **Detects Language Prefix**: Checks if URL starts with a 2-letter language code
2. **Validates Language**: Ensures the prefix matches configured languages
3. **Handles English Special Case**: Redirects `/en/...` to `/...` (SEO best practice)
4. **Sets Application Locale**: Updates Laravel's active locale
5. **Stores in Session**: Persists language choice across requests

### Middleware Code

Already included in your installation:

```php
<?php
namespace App\Http\Middleware\Translate;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedLangs = array_keys(config('translation.languages'));
        $firstSegment = $request->segment(1);

        // Only consider it a language prefix if:
        // - It exists
        // - It's EXACTLY 2 lowercase letters
        // - It matches one of our allowed languages
        if (
            $firstSegment &&
            strlen($firstSegment) === 2 &&
            ctype_lower($firstSegment) &&
            in_array($firstSegment, $allowedLangs, true)
        ) {
            $lang = $firstSegment;

            // Special case: redirect /en/... to plain /... (SEO)
            if ($lang === 'en') {
                $pathWithoutLang = '/' . implode('/', array_slice($request->segments(), 1));
                if ($pathWithoutLang === '/') $pathWithoutLang = '';
                return redirect($pathWithoutLang ?: '/', 301);
            }

            // Valid non-English language prefix
            Session::put('language', $lang);
            app()->setLocale($lang);
            return $next($request);
        }

        // Default: no valid 2-letter language prefix → English
        Session::put('language', 'en');
        app()->setLocale('en');

        return $next($request);
    }
}
```

### Registering the Middleware

Add to `bootstrap/app.php` (see [Installation Step 5](#5-register-language-middleware)).

## 🎨 View Helpers

The system provides global helper functions via Composer autoloading. These are available everywhere — Blade templates, controllers, Livewire components, middleware, and more.

### Available Helpers

```blade
{{-- Current language code --}}
{{ langCode() }} {{-- Output: 'en', 'ar', 'es', etc. --}}

{{-- RTL detection --}}
@if(isRtl())
    <div dir="rtl" class="text-right">
        Arabic or Hebrew content
    </div>
@endif

{{-- Generate language-specific URLs --}}
<a href="{{ langUrl('about') }}">About</a>
<a href="{{ langUrl('products.show', ['id' => 5]) }}">Product</a>

{{-- Check current route --}}
@if(isRoute('about'))
    <li class="active">About</li>
@endif

{{-- Works with language prefixes automatically --}}
@if(isRoute('products.show'))
    <span class="badge">Current</span>
@endif
```

### Real-World Example: Header Navigation

Here's how to use the helpers in a navigation header:

```blade
<header>
    <nav class="{{ isRtl() ? 'flex-row-reverse' : '' }}">
        <a href="{{ langUrl('home') }}" 
           class="{{ isRoute('home') ? 'active' : '' }}">
            @__t('Home')
        </a>
        
        <a href="{{ langUrl('about') }}" 
           class="{{ isRoute('about') ? 'active' : '' }}">
            @__t('About')
        </a>
        
        <a href="{{ langUrl('contact') }}" 
           class="{{ isRoute('contact') ? 'active' : '' }}">
            @__t('Contact')
        </a>
    </nav>
</header>
```

### Language Switcher Example

Create a language switcher menu using the helpers:

```blade
<div class="language-selector">
    @foreach(config('translation.languages') as $locale => $name)
        @php
            // Get current path without language prefix
            $currentPath = request()->path();
            $currentLang = app()->getLocale();
            
            if ($currentLang !== 'en') {
                $currentPath = preg_replace('#^' . $currentLang . '(/|$)#', '', $currentPath);
            }
            
            // Build URL for target language
            if ($locale === 'en') {
                $switchUrl = '/' . $currentPath;
            } else {
                $switchUrl = '/' . $locale . '/' . $currentPath;
            }
            
            $switchUrl = preg_replace('#/+#', '/', $switchUrl);
            $isActive = (app()->getLocale() === $locale);
        @endphp
        
        <a href="{{ $switchUrl }}" 
           class="{{ $isActive ? 'active' : '' }}">
            {{ config('translation.language_names.' . langCode() . '.' . $locale) }}
        </a>
    @endforeach
</div>
```

### Using in Livewire Components

The helpers work seamlessly with Livewire:

```blade
<div>
    <a href="{{ langUrl('profile') }}" wire:navigate>
        @__t('My Profile')
    </a>
    
    <button wire:click="$set('locale', '{{ langCode() }}')" 
            class="{{ isRtl() ? 'mr-auto' : 'ml-auto' }}">
        @__t('Save Changes')
    </button>
</div>
```

## 🔧 Configuration

### Main Configuration File

The `config/translation.php` file contains all system settings:

```php
return [
    // Enable detailed logging for debugging
    'log_process' => false,
    
    // Source language (strings are extracted to this language first)
    'source_locale' => 'en',
    
    // Available languages with their full names
    'languages' => [
        'en' => 'English',
        'ar' => 'Arabic',
        // Add more languages here
    ],
    
    // How each language name appears in each language's interface
    'language_names' => [
        'en' => [
            'en' => 'English',
            'ar' => 'Arabic',
        ],
        'ar' => [
            'en' => 'الإنجليزية',
            'ar' => 'العربية',
        ],
    ],
    
    // Languages to translate to (excluding source)
    'target_locales' => ['ar'],
    
    // RTL languages
    'rtl_languages' => ['ar', 'he'],
    
    // Language file paths
    'language_files' => [
        'en' => lang_path('en.json'),
        'ar' => lang_path('ar.json'),
    ],
    
    // URL scanning settings
    'urls' => [
        'delay_between_requests' => 1, // seconds
        'batch_size' => 50,
        'timeout' => 20,
    ],
    
    // String extraction settings
    'extraction' => [
        'scan_internal' => true,
        'clear_cache' => true,
    ],
    
    // AI translation settings
    'translation' => [
        'ai_provider' => 'openai',
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_key' => env('OPENAI_API_KEY'),
        'batch_size' => 20,
        'concurrent_jobs' => 5,
        'rate_limit_per_minute' => 300,
        'max_retries' => 3,
        'system_prompt' => 'You are a professional translator. Translate the following text to {language}. Return ONLY the translated text with no explanations, greetings, or additional commentary. Preserve any HTML tags, placeholders like :name, and formatting.',
    ],
];
```

> **Note:** Only `OPENAI_API_KEY` and `OPENAI_MODEL` use `.env` variables. All other settings like `log_process`, `delay_between_requests`, and `batch_size` are configured directly in this file. Collection mode is handled automatically at runtime — there is no config entry for it.

### Adding New Languages

To add a new language:

1. **Update `config/translation.php`:**

```php
'languages' => [
    'en' => 'English',
    'ar' => 'Arabic',
    'es' => 'Spanish',  // New language
    'fr' => 'French',   // New language
],

'target_locales' => ['ar', 'es', 'fr'],

'language_files' => [
    'en' => lang_path('en.json'),
    'ar' => lang_path('ar.json'),
    'es' => lang_path('es.json'),
    'fr' => lang_path('fr.json'),
],
```

2. **Add RTL support if needed:**

```php
'rtl_languages' => ['ar', 'he'],
```

3. **Create empty JSON files:**

```bash
touch lang/es.json
touch lang/fr.json
```

## 🌍 Complete Routing Example

Here's a real-world example showing how everything works together in `routes/web.php`:

```php
<?php
use Illuminate\Support\Facades\Route;

// Your Livewire components
use App\Livewire\Pages\Home;
use App\Livewire\Pages\About;
use App\Livewire\Pages\Contact;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Products\ProductShow;
use App\Livewire\User\Dashboard;
use App\Livewire\User\Profile;

/**
 * Language Route Helper
 * Copy this function into your routes/web.php file
 */
function langRoute($method, $path, $action, $name = null, $where = [], $middleware = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    $allMiddleware = array_merge(['language'], (array) $middleware);
    
    // Main route (no prefix - English)
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
|--------------------------------------------------------------------------
| Public Routes (no auth required)
|--------------------------------------------------------------------------
*/

langRoute('get', '/', Home::class, 'home');
langRoute('get', '/about', About::class, 'about');
langRoute('get', '/contact', Contact::class, 'contact');

// Products with parameter
langRoute('get', '/products', ProductIndex::class, 'products.index');
langRoute('get', '/products/{slug}', ProductShow::class, 'products.show');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| Middleware is passed as the 6th parameter so it applies to ALL
| language-prefixed routes, not just the English route.
|--------------------------------------------------------------------------
*/

langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth']);
langRoute('get', '/profile', Profile::class, 'profile', [], ['auth', 'verified']);

/*
|--------------------------------------------------------------------------
| POST Routes
|--------------------------------------------------------------------------
*/

langRoute('post', '/contact', ContactSubmit::class, 'contact.submit');
langRoute('post', '/logout', Logout::class, 'logout');
```

**This automatically creates:**

```
GET  /                         → home
GET  /ar/                      → ar.home
GET  /es/                      → es.home

GET  /about                    → about
GET  /ar/about                 → ar.about
GET  /es/about                 → es.about

GET  /products/{slug}          → products.show
GET  /ar/products/{slug}       → ar.products.show
GET  /es/products/{slug}       → es.products.show

GET  /dashboard                → dashboard        (language, auth)
GET  /ar/dashboard             → ar.dashboard     (language, auth)
GET  /es/dashboard             → es.dashboard     (language, auth)
```

## 🚀 Usage

### Step 1: Mark Strings for Translation

In your Blade templates, use the `@__t()` directive:

```blade
{{-- Include hreflang tags in your layout --}}
@include('lang')

{{-- Mark strings for translation --}}
<h1>@__t('Welcome to Our Website')</h1>
<p>@__t('We provide the best service in the industry')</p>

{{-- Works with variables too --}}
<button>@__t('Contact Us')</button>
```

### Step 2: Access the Translation Dashboard

Navigate to the dashboard:

```
http://your-app.com/translation-dashboard
```

### Step 3: Generate URLs

In the dashboard:

1. **Add Manual URLs** (one per line):
   ```
   https://your-app.com/home
   https://your-app.com/about
   https://your-app.com/contact
   ```

2. **Add API Endpoints** (optional - for dynamic URLs):
   ```
   https://your-app.com/api/sitemap/pages
   https://your-app.com/api/sitemap/products
   ```

3. Click **"Generate URLs"**

### Step 4: Collect Strings

1. Click **"Collect Strings"** in the dashboard

2. Monitor the real-time progress bar

3. That's it! Collection mode is handled automatically at runtime — no need to toggle any `.env` or config values.

> **How it works:** When you click "Collect Strings", the system dispatches queue jobs that internally enable collection mode only for the duration of each scan request. Normal user traffic is never affected. See [How It Works](#how-it-works) for details.

### Step 5: Translate All Keys

1. Verify OpenAI API key is configured

2. Click **"Translate All Keys"**

3. Monitor translation progress for each language

4. Translations are processed in batches via queue workers

## 🔍 How It Works

### The @__t() Directive

The system uses a custom Blade directive that operates in two modes:

#### Collection Mode (automatic, during string scanning)

```php
@__t('Hello World')
```

Outputs:
```html
<!--T_START:Hello World:T_END-->Hello World
```

The HTML comments act as markers that the scanner can detect and extract.

#### Normal Mode (default, during regular requests)

```php
@__t('Hello World')
```

Outputs (if locale is 'ar'):
```
مرحبا بالعالم
```

Simply calls Laravel's `__()` helper to fetch the translated string.

#### How Collection Mode Works

Collection mode is **not** a global toggle. It's a runtime-only static property on the `StringExtractor` class:

```php
// StringExtractor.php
public static bool $collectionMode = false;
```

When the `StringExtractor` scans a URL, it temporarily enables collection mode for that specific internal request only:

```php
try {
    self::$collectionMode = true;    // Enable for this scan
    $response = app()->handle($request);  // Render the page
    // ... extract markers ...
} finally {
    self::$collectionMode = false;   // Always disable after
}
```

The `@__t()` Blade directive checks this property at runtime via the `isCollectionMode()` helper:

```php
Blade::directive('__t', function ($expression) {
    return "<?php if(isCollectionMode()): ?>" .
        "<?php echo '<!--T_START:' ... :T_END-->' . __({$expression}); ?>" .
        "<?php else: ?>" .
        "<?php echo __({$expression}); ?>" .
        "<?php endif; ?>";
});
```

This means:
- **Normal user requests** → `isCollectionMode()` returns `false` → no HTML comment markers, zero overhead
- **String extraction jobs** → `isCollectionMode()` returns `true` only during the scan → markers are injected → then immediately disabled
- **No `.env` or config toggling needed** → fully automatic
- **Cache-safe** → works correctly even with cached Blade views because the check happens at runtime, not compile time

### String Collection Process

1. **ScanUrlForStringsJob** dispatched for each URL
2. `StringExtractor` enables collection mode via static property
3. Job makes an internal Laravel request to the URL
4. The `@__t()` directive detects collection mode and injects HTML comment markers
5. HTML response is scanned for `<!--T_START:...:T_END-->` markers
6. Collection mode is disabled in a `finally` block (guaranteed cleanup)
7. Unique strings are extracted and saved to `lang/en.json`
8. Progress tracking is updated in the database

### Translation Process

1. Source strings loaded from `lang/en.json`
2. Existing translations loaded from target language files
3. Untranslated strings identified
4. **TranslateStringBatchJob** dispatched with batches of 20 strings
5. OpenAI API called for each string
6. Translations saved back to language files
7. Progress tracking updated

### Language Routing

The `LanguageMiddleware` handles language detection:

```php
// English (default) - no prefix
https://your-app.com/about

// Arabic - language prefix
https://your-app.com/ar/about

// Spanish - language prefix
https://your-app.com/es/about
```

The `langRoute()` helper automatically creates routes for all languages:

```php
langRoute('get', '/about', About::class, 'about');
```

Creates:
- `/about` → English (canonical)
- `/ar/about` → Arabic
- `/es/about` → Spanish

### SEO Implementation

The `lang.blade.php` file generates proper SEO tags:

```html
<!-- Canonical URL (always English, no prefix) -->
<link rel="canonical" href="https://your-app.com/about" />

<!-- Default language -->
<link rel="alternate" hreflang="x-default" href="https://your-app.com/about" />

<!-- Language alternates -->
<link rel="alternate" hreflang="en" href="https://your-app.com/about" />
<link rel="alternate" hreflang="ar" href="https://your-app.com/ar/about" />
<link rel="alternate" hreflang="es" href="https://your-app.com/es/about" />
```

## 🎯 Advanced Features

### Progress Tracking

The system tracks progress in real-time using the `translation_progress` table:

```php
// Check extraction progress
$progress = DB::table('translation_progress')
    ->where('type', 'string_extraction')
    ->whereNull('locale')
    ->first();

// Check translation progress for Arabic
$progress = DB::table('translation_progress')
    ->where('type', 'translation')
    ->where('locale', 'ar')
    ->first();
```

### API Endpoints for Dynamic URLs

If you have dynamic content (products, articles, etc.), create API endpoints:

```php
// routes/api.php
Route::get('/sitemap/products', function () {
    $products = Product::all();
    return $products->map(function($product) {
        return url('/products/' . $product->slug);
    });
});
```

Then add the endpoint in the dashboard:
```
https://your-app.com/api/sitemap/products
```

### Custom Translation Prompts

Customize the AI behavior in `config/translation.php`:

```php
'system_prompt' => 'You are a professional translator specializing in e-commerce. Translate the following text to {language}. Maintain a friendly, persuasive tone. Return ONLY the translated text.',
```

### Rate Limiting

The system includes built-in rate limiting to respect OpenAI's API rate limits:

```php
// config/translation.php
'rate_limit_per_minute' => 300,
```

Adjust based on your OpenAI tier:
- Free tier: 60-100 requests/minute
- Paid tier: 300-3500 requests/minute

## 🐛 Troubleshooting

### Strings Not Being Collected

1. **Clear view cache** (important — stale compiled views won't have the runtime check):
   ```bash
   php artisan view:clear
   ```

2. **Enable debug logging** in `config/translation.php`:
   ```php
   'log_process' => true,
   ```
   Then check `storage/logs/laravel.log` for detailed extraction logs.

3. **Verify routes are accessible:**
   ```bash
   curl http://127.0.0.1:8000/home
   ```

4. **Verify the service provider is registered** in `bootstrap/providers.php`:
   ```php
   App\Providers\Translate\TranslationServiceProvider::class,
   ```

5. **Verify helper functions are autoloaded:**
   ```bash
   composer dump-autoload
   php artisan tinker
   >>> isCollectionMode()
   # Should return false
   ```

### Translations Not Working

1. **Check OpenAI API key:**
   ```bash
   php artisan tinker
   >>> config('translation.translation.api_key')
   ```

2. **Verify queue workers are running:**
   ```bash
   ps aux | grep "queue:work"
   ```

3. **Check failed jobs:**
   ```bash
   php artisan queue:failed
   ```

4. **Monitor queue:**
   ```bash
   php artisan queue:work --verbose
   ```

### Language Routes Not Working

1. **Clear route cache:**
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

2. **Verify middleware is registered:**
   ```bash
   php artisan route:list
   ```

3. **Check language configuration:**
   ```php
   php artisan tinker
   >>> config('translation.languages')
   ```

### Middleware Not Applying to Language-Prefixed Routes

If authenticated routes are accessible without login on language-prefixed URLs (e.g., `/ar/dashboard` works without auth), you are chaining middleware instead of passing it as a parameter:

```php
// ❌ WRONG — middleware only applies to English route
langRoute('get', '/dashboard', Dashboard::class, 'dashboard')
    ->middleware(['auth']);

// ✅ CORRECT — middleware applies to ALL language routes
langRoute('get', '/dashboard', Dashboard::class, 'dashboard', [], ['auth']);
```

See [Routing System](#routing-system) for details.

## 📖 API Reference

### StringExtractor Service

```php
use App\Services\Translate\StringExtractor;

$extractor = new StringExtractor();

// Extract strings from a URL
$keys = $extractor->extractFromUrl('https://your-app.com/home');

// Save keys to language file
$newCount = $extractor->saveToLanguageFile($keys, 'en');

// Get all keys from a language file
$allKeys = $extractor->getAllKeys('en');

// Check if collection mode is active
$isActive = StringExtractor::$collectionMode;
```

### AITranslator Service

```php
use App\Services\Translate\AITranslator;

$translator = new AITranslator();

// Translate a single string
$translated = $translator->translate('Hello World', 'ar');

// Translate a batch
$translations = $translator->translateBatch([
    'hello' => 'Hello',
    'world' => 'World',
], 'ar');

// Check if configured
if ($translator->isConfigured()) {
    // API key is set
}
```

### URLCollector Service

```php
use App\Services\Translate\URLCollector;

$collector = new URLCollector();

// Add manual URLs
$collector->addManualUrls([
    'https://your-app.com/home',
    'https://your-app.com/about',
]);

// Collect from API endpoints
$collector->collectFromAPIs([
    'products' => 'https://your-app.com/api/sitemap/products',
]);

// Save to config
$total = $collector->saveToConfig();

// Load from config
$urls = $collector->loadFromConfig();
```

### Global Helper Functions

```php
// Get current locale
$locale = langCode(); // 'en', 'ar', etc.

// Check RTL
if (isRtl()) {
    // Apply RTL styles
}

// Generate language-aware URL
$url = langUrl('products.show', ['slug' => 'example']);

// Check current route (works with language prefixes)
if (isRoute('about')) {
    // Current page is About
}

// Check collection mode (useful in controllers/middleware)
if (isCollectionMode()) {
    // String extraction is currently running
}
```


## 👨‍💻 Developer: 
https://warmardev.com/

## Docs
https://warmardev.com/docs/laravel-translate.html

## Video
https://youtu.be/-NVuDoGsgv8

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits
Built with ❤️ using:
- Laravel
- Livewire
- OpenAI GPT-4
- TailwindCSS

## 📞 Support
For issues, questions, or contributions:
- Open an issue on GitHub.

**Note:** Remember to never commit your `.env` file or expose your OpenAI API key publicly.