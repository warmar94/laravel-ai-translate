# Laravel AI Translation System

**Docs:** https://warmardev.com/docs/laravel-translate.html

A **complete multilingual framework** for Laravel applications. This isn't just a translation tool - it's a full-featured localization system with AI-powered translations, automatic routing, SEO optimization, and RTL support out of the box.

**Built on top of Laravel's default translation system** — no custom Blade directives or proprietary syntax. Uses standard `__()` everywhere, so your templates stay clean and portable.

**What it adds on top of Laravel's built-in translation:**

- ✅ Automatic language-prefixed routing (`/about`, `/ar/about`)
- ✅ Smart language detection middleware
- ✅ AI-powered translation with OpenAI
- ✅ **Dual string collection** — active URL scanning + passive runtime detection via Laravel's `handleMissingKeysUsing`, with configurable `runtime_collection` toggle
- ✅ Real-time translation dashboard with live progress tracking
- ✅ **Missing Keys dashboard** — auto-detected untranslated strings from live traffic with per-locale tracking
- ✅ Complete SEO implementation (hreflang, canonical URLs)
- ✅ RTL language support
- ✅ Global helper functions for easy integration
- ✅ Database-backed URL management with API endpoint auto-fetching
- ✅ Eloquent models for all database operations
- ✅ Inline manual translation editor per language
- ✅ Per-string AI translation (individual or batch)

## 🌟 Features

### 🎯 Core Translation Features

- 🤖 **AI-Powered Translations**: Leverages OpenAI GPT-4 for context-aware, high-quality translations
- 🔍 **Dual String Collection**: Active URL scanning + passive detection via Laravel's native `handleMissingKeysUsing` hook — new strings are caught automatically from live traffic. A configurable `runtime_collection` flag controls whether passive collection runs on every request or only during active scans, giving you full control over production overhead
- 🌐 **Multi-Language Support**: Translate your entire application into unlimited languages
- 📊 **Real-Time Dashboard**: Beautiful Livewire-powered interface with live progress tracking
- ⚡ **Queue-Based Processing**: Scalable batch processing for thousands of strings
- 🎯 **Zero Custom Syntax**: Uses Laravel's standard `__()` function — no proprietary directives, no learning curve, templates stay clean and portable
- 🗃️ **Missing Keys Tracking**: Database-backed `translation_missing` table with occurrence counts, first/last seen timestamps, grouped by locale — automatically populated from live traffic
- 🔧 **Local API Deadlock Prevention**: Internal request handling for local API endpoints avoids single-threaded `php artisan serve` deadlock

### 🔗 URL Management

- 🗄️ **Database-Backed URLs**: All URLs stored in a dedicated `translation_urls` table with full CRUD support
- 🌐 **API Endpoint Auto-Fetching**: Add API endpoints that return JSON arrays of URLs — the system fetches and imports them automatically
- 🔄 **Re-Fetchable Endpoints**: Saved API endpoints can be re-triggered at any time to discover new URLs
- 🔍 **Search & Filter**: Search URLs inline, toggle active/inactive, bulk add or clear
- 📊 **Indexed for Scale**: Database columns indexed for performance with tens of thousands of URLs

### 🚀 Advanced Routing & Localization

- 🛣️ **Smart Language Routing**: Automatic language-prefixed URLs (`/about`, `/ar/about`, `/es/about`)
- 🔧 **Custom `langRoute()` Helper**: One function to create all language routes automatically
- 🌍 **Language Middleware**: Intelligent language detection and switching
- 📝 **SEO-Optimized**: Auto-generated hreflang tags, canonical URLs, and x-default handling
- 🔄 **Language Switcher**: Built-in helpers for creating language selection menus
- ↔️ **RTL Support**: Full right-to-left language support with automatic detection
- 🎨 **Global Helper Functions**: `langUrl()`, `isRtl()`, `isRoute()`, and `langCode()` available everywhere

## 📋 Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Database Schema](#database-schema)
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

> **Note:** The package ships with its own `TranslationServiceProvider` that hooks into Laravel's `handleMissingKeysUsing` for automatic string collection and missing key detection. You do **not** need to modify your `AppServiceProvider`.

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

> **Note:** URL delay, logging, and other settings are configured in `config/translation.php` — not in `.env`. String collection is handled automatically at runtime via Laravel's `handleMissingKeysUsing` hook and requires no manual toggling.

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

The system uses three database tables managed via Eloquent models. If you installed via the Laravel install command, skip this step.

#### Option A: Laravel Migrations

```bash
php artisan migrate
```

#### Option B: Raw SQL

```sql
-- translation_urls: Stores all URLs for string extraction and saved API endpoints
CREATE TABLE `translation_urls` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` TEXT NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_api` TINYINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `translation_urls_active_index` (`active`),
    INDEX `translation_urls_is_api_index` (`is_api`),
    INDEX `translation_urls_active_is_api_index` (`active`, `is_api`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- translation_progress: Tracks extraction and translation job progress
CREATE TABLE `translation_progress` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('string_extraction', 'translation') NOT NULL,
    `locale` VARCHAR(10) NULL DEFAULT NULL,
    `total` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed` INT UNSIGNED NOT NULL DEFAULT 0,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `translation_progress_type_locale_unique` (`type`, `locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- translation_missing: Tracks missing translation keys detected from live traffic
CREATE TABLE `translation_missing` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(500) NOT NULL,
    `locale` VARCHAR(10) NOT NULL DEFAULT 'en',
    `occurrences` BIGINT UNSIGNED NOT NULL DEFAULT 1,
    `first_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_key_locale` (`key`, `locale`),
    INDEX `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Table Details

**`translation_urls`** — Stores all URLs that the system will scan for translatable strings.

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Auto-increment primary key |
| `url` | TEXT | The URL to scan (indexed for search) |
| `active` | TINYINT(1) | Whether the URL is active for extraction (default: 1) |
| `is_api` | TINYINT | `0` = regular URL (will be scanned for strings), `1` = API endpoint (used to fetch URLs, never scanned directly) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**`translation_progress`** — Tracks the progress of string extraction and translation jobs.

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Auto-increment primary key |
| `type` | ENUM | `'string_extraction'` or `'translation'` |
| `locale` | VARCHAR(10) | Target locale (NULL for string extraction) |
| `total` | INT UNSIGNED | Total items to process |
| `completed` | INT UNSIGNED | Items completed |
| `failed` | INT UNSIGNED | Items failed |
| `started_at` | TIMESTAMP | When processing started |
| `updated_at` | TIMESTAMP | Last progress update |
| `completed_at` | TIMESTAMP | When processing finished |

**`translation_missing`** — Tracks missing translation keys detected automatically from live traffic via Laravel's `handleMissingKeysUsing` hook.

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Auto-increment primary key |
| `key` | VARCHAR(500) | The untranslated string key |
| `locale` | VARCHAR(10) | The locale where the translation was missing |
| `occurrences` | BIGINT UNSIGNED | How many times this key was requested (auto-increments on each hit) |
| `first_seen` | DATETIME | When this key was first detected as missing |
| `last_seen` | DATETIME | When this key was most recently requested (auto-updates) |

> **Important:** The `is_api` column distinguishes between regular URLs and API endpoints. Regular URLs (`is_api = 0`) are scanned for translatable strings. API endpoints (`is_api = 1`) are fetched to discover new URLs from their JSON response — they are **never** scanned for strings directly.

### 10. Install Required Files

If you installed via Laravel Command skip this Step.

Copy the following files to your Laravel application:

**Configuration:**
- `config/translation.php`

**Service Providers:**
- `app/Providers/Translate/TranslationServiceProvider.php`

**Helpers:**
- `app/Helpers/Translate/TranslationHelper.php` (contains translation helper functions)

**Models:**
- `app/Models/Translate/TranslationUrl.php`
- `app/Models/Translate/TranslationProgress.php`
- `app/Models/Translate/MissingTranslation.php`

**Services:**
- `app/Services/Translate/AITranslator.php`
- `app/Services/Translate/MissingKeyBufferService.php`
- `app/Services/Translate/StringExtractor.php`
- `app/Services/Translate/URLCollector.php`

**Jobs:**
- `app/Jobs/Translate/ScanUrlForStringsJob.php`
- `app/Jobs/Translate/TranslateStringBatchJob.php`
- `app/Jobs/Translate/ProcessMissingKeysJob.php`

**Middleware:**
- `app/Http/Middleware/Translate/LanguageMiddleware.php`

**Livewire Components:**
- `app/Livewire/Translate/TranslateMenu.php`
- `resources/views/livewire/translate/translate-menu.blade.php`

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
│   │       ├── ScanUrlForStringsJob.php            # Visits URLs to trigger string collection
│   │       ├── TranslateStringBatchJob.php         # Translates string batches via AI
│   │       └── ProcessMissingKeysJob.php           # Writes buffered missing keys to JSON/DB
│   │
│   ├── Livewire/
│   │   └── Translate/
│   │       └── TranslateMenu.php                   # Dashboard Livewire controller
│   │
│   ├── Models/
│   │   └── Translate/
│   │       ├── TranslationUrl.php                  # Eloquent model for URLs & API endpoints
│   │       ├── TranslationProgress.php             # Eloquent model for job progress tracking
│   │       └── MissingTranslation.php              # Eloquent model for missing key tracking
│   │
│   ├── Providers/
│   │   └── Translate/
│   │       └── TranslationServiceProvider.php      # Hooks into handleMissingKeysUsing
│   │
│   └── Services/
│       └── Translate/
│           ├── AITranslator.php                    # OpenAI API integration
│           ├── MissingKeyBufferService.php         # Request-scoped in-memory key buffer
│           ├── StringExtractor.php                 # Internal URL scanning with collectionMode flag
│           └── URLCollector.php                    # Database-backed URL collection & API fetching
│
├── config/
│   └── translation.php                             # Main configuration file
│
├── database/
│   └── migrations/
│       ├── create_translation_urls_table.php       # URLs table with indexes
│       ├── create_translation_progress_table.php   # Progress tracking table
│       └── create_translation_missing_table.php   # Missing keys tracking table
│
├── lang/
│   ├── en.json                                     # English strings (source, auto-populated)
│   ├── ar.json                                     # Arabic translations
│   └── [locale].json                               # Additional languages
│
├── resources/
│   └── views/
│       ├── lang.blade.php                          # SEO hreflang tags
│       └── livewire/
│           └── translate/
│               └── translate-menu.blade.php        # Dashboard UI (Tailwind CSS)
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

Here's how to use the helpers in a navigation header with standard Laravel `__()`:

```blade
<header>
    <nav class="{{ isRtl() ? 'flex-row-reverse' : '' }}">
        <a href="{{ langUrl('home') }}" 
           class="{{ isRoute('home') ? 'active' : '' }}">
            {{ __('Home') }}
        </a>
        
        <a href="{{ langUrl('about') }}" 
           class="{{ isRoute('about') ? 'active' : '' }}">
            {{ __('About') }}
        </a>
        
        <a href="{{ langUrl('contact') }}" 
           class="{{ isRoute('contact') ? 'active' : '' }}">
            {{ __('Contact') }}
        </a>
    </nav>
</header>
```

> **Note:** This uses standard Laravel `{{ __('...') }}` syntax. No custom directives needed — the `handleMissingKeysUsing` hook automatically detects and collects any untranslated strings at runtime.

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
        {{ __('My Profile') }}
    </a>
    
    <button wire:click="$set('locale', '{{ langCode() }}')" 
            class="{{ isRtl() ? 'mr-auto' : 'ml-auto' }}">
        {{ __('Save Changes') }}
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
    
    // Source language (strings are collected in this language first)
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
        'delay_between_requests' => 1, // seconds between URL scans
        'batch_size' => 50,
        'timeout' => 20,               // HTTP timeout for external API endpoints
        'api_scan_internal' => true,   // Use internal requests for local API endpoints (avoids artisan serve deadlock)
    ],
    
    // String extraction settings
    'extraction' => [
        'scan_internal' => true,
        'clear_cache' => false,
        'runtime_collection' => false, // true = collect from all live traffic; false = active scans only
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

> **Note:** Only `OPENAI_API_KEY` and `OPENAI_MODEL` use `.env` variables. All other settings like `log_process`, `delay_between_requests`, and `batch_size` are configured directly in this file. The `api_scan_internal` option ensures local API endpoints (127.0.0.1, localhost) are fetched via internal Laravel requests to avoid deadlock on single-threaded dev servers like `php artisan serve`. The `runtime_collection` option controls whether the missing key handler buffers keys from all live traffic (`true`) or only during active URL scans (`false`, recommended for production).

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

### Step 1: Use Standard Laravel Translation Syntax

In your Blade templates, use Laravel's built-in `__()` function — the same syntax you already know:

```blade
{{-- Include hreflang tags in your layout --}}
@include('lang')

{{-- Standard Laravel __() — the system collects these automatically --}}
<h1>{{ __('Welcome to Our Website') }}</h1>
<p>{{ __('We provide the best service in the industry') }}</p>

{{-- Works with variables too --}}
<button>{{ __('Contact Us') }}</button>

{{-- With placeholders (standard Laravel feature) --}}
<p>{{ __('Hello, :name!', ['name' => $user->name]) }}</p>
```

> **No custom directives needed.** The system hooks into Laravel's `handleMissingKeysUsing` callback — every `__()` call that can't find a translation key is automatically detected and collected. Your templates use 100% standard Laravel syntax.

### Step 2: Access the Translation Dashboard

Navigate to the dashboard:

```
http://your-app.com/translation-dashboard
```

### Step 3: Add URLs

The dashboard has a tabbed interface. In the **URLs** tab:

1. **Add Regular URLs** (one per line in the text area):
   ```
   https://your-app.com/home
   https://your-app.com/about
   https://your-app.com/contact
   ```
   Click **"Add URLs"** — duplicates are automatically skipped.

2. **Add API Endpoints** (in the separate API section):
   ```
   https://your-app.com/api/sitemaps/blog
   https://your-app.com/api/sitemaps/products
   ```
   Click **"Fetch & Import URLs"** — the system will:
   - Save each API endpoint to the database (with `is_api = 1`)
   - HTTP GET each endpoint (or internal request for local URLs)
   - Parse the JSON array response
   - Import each URL from the response as a regular URL
   - Skip any URLs that already exist

3. **Manage URLs** in the table below:
   - 🔍 **Search** URLs with the filter box
   - ✅ **Toggle active/inactive** per URL
   - 🗑️ **Delete** individual URLs or clear all
   - 🔄 **Re-fetch** all saved API endpoints to discover new URLs

> **How API endpoints work:** Your API endpoint should return a flat JSON array of URL strings. Example response:
> ```json
> [
>     "https://your-app.com/articles/my-first-post",
>     "https://your-app.com/articles/my-second-post",
>     "https://your-app.com/products/widget-pro"
> ]
> ```
> API endpoints are saved and can be re-triggered at any time. They are **never** scanned for translatable strings — only their response URLs are.

### Step 4: Collect Strings

1. Switch to the **Extract Strings** tab
2. Click **"Collect Strings"**
3. Monitor the real-time progress bar
4. That's it! String collection is fully automatic — jobs visit each URL internally, and every `__()` call in your templates triggers the `handleMissingKeysUsing` hook which saves new strings to `en.json`.

> **How it works:** When you click "Collect Strings", the system dispatches `ScanUrlForStringsJob` queue jobs — one per URL. Each job sets `StringExtractor::$collectionMode = true`, visits the page via `app()->handle()`, then resets the flag in a `finally` block. As each page renders, every `__()` call that doesn't find a matching key is buffered in `MissingKeyBufferService`. After the internal request finishes, a single `ProcessMissingKeysJob` is dispatched to write the buffered keys to `en.json` (with `flock` for safety) and upsert target-locale keys into the database. No HTML markers, no regex — just Laravel's own translator doing the work. See [How It Works](#how-it-works) for details.

> **Passive collection also works:** If `runtime_collection = true` in config, any user visiting your site will also trigger key buffering and dispatch for any untranslated strings — without clicking "Collect Strings". The active scan is useful for an initial full sweep or after major template changes, and works regardless of the `runtime_collection` setting.

### Step 5: Translate All Keys

1. Switch to the **Translate** tab
2. Verify OpenAI API key is configured
3. Click **"Translate All Keys"**
4. Monitor translation progress for each language
5. Translations are processed in batches via queue workers

### Step 6: Review & Edit Translations

1. Switch to the **Translation Status** tab
2. View completion percentage per language (color-coded progress bars)
3. Click any language to open the **inline string editor**:
   - Search/filter strings
   - Edit translations directly in the table (saves on Enter or blur)
   - Click the ✨ sparkles button to AI-translate a single string
   - Green checkmark = translated, grey dash = missing

### Step 7: Monitor Missing Keys

1. Switch to the **Missing Keys** tab
2. View untranslated strings detected from live traffic for target languages
3. Keys are **grouped by locale** with occurrence counts and first/last seen timestamps
4. **Translate individually** — click the ✨ button on any row, a job dispatches immediately and the row disappears
5. **Translate all per locale** — click "Translate All" on a locale group, rows hide and a progress bar appears
6. **Clear Resolved** — removes entries that have already been translated since they were logged
7. **Clear All** — wipes the entire missing keys table
8. A **processing banner** appears at the top of the tab when any translations are in progress, showing per-locale progress bars

> **How Missing Keys work:** When a user visits `/ar/about` and the Arabic translation for `"About Us"` doesn't exist, the `handleMissingKeysUsing` hook automatically logs it to the `translation_missing` table with locale `ar`. The occurrence counter increments on every subsequent hit. You can then translate these keys individually or in batch directly from the Missing Keys tab.

## 🔍 How It Works

### Standard Laravel `__()` Integration

The system works with Laravel's standard `__()` translation function — no custom Blade directives or proprietary syntax. This means:

- **Your templates stay clean and portable** — standard `{{ __('...') }}` everywhere
- **No learning curve** — if you know Laravel, you already know how to use this
- **Zero vendor lock-in** — remove the package and your templates still work perfectly
- **IDE support works out of the box** — `__()` is recognized by all PHP IDEs

### How String Collection Works

The system uses **two complementary approaches** for collecting translatable strings:

#### 1. Passive Collection via `handleMissingKeysUsing` (configurable)

Laravel's `Translator` class has a `handleMissingKeysUsing` method that fires a callback whenever `__()` can't find a translation key. The `TranslationServiceProvider` hooks into this, but instead of writing to disk during the request, it buffers all missing keys in memory via the `MissingKeyBufferService` singleton — then dispatches a single `ProcessMissingKeysJob` **after the response is sent**.

**Collection is gated by two flags:**

- `runtime_collection` (config) — set to `true` to collect from all live user traffic; `false` to only collect during active URL scans
- `StringExtractor::$collectionMode` (static) — automatically set to `true` by `ScanUrlForStringsJob` during dashboard scans

```php
// TranslationServiceProvider.php (simplified)
Lang::handleMissingKeysUsing(function (string $key, ...) use ($buffer) {
    $runtimeCollection = config('translation.extraction.runtime_collection', false);

    // Gate: skip if neither runtime collection nor active scan is enabled
    if (!$runtimeCollection && !StringExtractor::$collectionMode) {
        return $key;
    }

    // Filter empty keys and vendor package keys (package::group.key)
    // ...

    if ($locale === $sourceLocale) {
        $buffer->addSourceKey($key);   // → en.json via ProcessMissingKeysJob
    } else {
        $buffer->addTargetKey($key, $locale);  // → translation_missing table
    }

    return $key;
});

// After response is sent — dispatch one job with all buffered keys
$this->app->terminating(function () use ($buffer) {
    if ($buffer->hasKeys()) {
        ProcessMissingKeysJob::dispatch(
            $buffer->getSourceKeys(),
            $buffer->getTargetKeys(),
            config('translation.source_locale', 'en')
        );
        $buffer->flush();
    }
});
```

**Why buffering?**

- **Zero I/O during the request** — no file writes or DB queries while the user is waiting
- **One job per request** — not one job per missing key; far more efficient under load
- **Race condition safety** — `ProcessMissingKeysJob` uses `flock(LOCK_EX)` when writing to `en.json`, so concurrent queue workers never corrupt the file
- **Silent failure** — if the job dispatch fails, the user is completely unaffected

This means:

- **Source locale (en):** Any `__('new string')` that isn't in `en.json` gets added automatically after the request — no scanning needed
- **Target locales (ar, es, etc.):** Missing translations are upserted into the `translation_missing` table with occurrence counts, so you can see exactly what needs translating and how often
- **Zero overhead for existing translations:** The callback only fires when a key is actually missing. Translated strings go through Laravel's normal fast path
- **Minimal filtering needed:** Laravel resolves built-in keys (`auth.*`, `validation.*`, `pagination.*`, etc.) via its own lang files before the callback ever fires — only empty keys and vendor package keys (`package::group.key`) are filtered out

#### 2. Active Scanning via URL Visits (on-demand)

When you click "Collect Strings" in the dashboard:

1. Queue jobs (`ScanUrlForStringsJob`) visit each URL internally via `app()->handle()`
2. `StringExtractor::$collectionMode` is set to `true` before the page renders, and reset in a `finally` block after
3. The page renders — every `__()` call throughout your templates fires the translator
4. The missing key handler buffers any new strings via `MissingKeyBufferService`
5. After the internal request completes, the terminating callback dispatches `ProcessMissingKeysJob` which writes to `en.json` and the DB
6. No HTML markers, no regex parsing — just Laravel's own translator doing the work

```php
// StringExtractor.php (simplified)
public function extractFromUrl(string $url): void
{
    try {
        self::$collectionMode = true;

        $request = \Illuminate\Http\Request::create($path, 'GET');
        app()->handle($request); // missing key handler buffers all __() calls

    } finally {
        // Always reset — even if the page render throws
        self::$collectionMode = false;
    }
}
```

The `collectionMode` flag is `static` so it's shared across the entire process. The `finally` block guarantees it's always reset, even on exception.

This active scan is useful for:
- **Initial setup** — sweep all pages at once to populate `en.json`
- **After major template changes** — catch all new strings across the site
- **Catching strings in unvisited pages** — pages that haven't had real user traffic yet

> **`runtime_collection` vs active scan:** If `runtime_collection = false` (recommended for production), the missing key hook is a no-op during normal requests — zero overhead. It only activates during active scans triggered by `ScanUrlForStringsJob`. If `runtime_collection = true`, missing keys are buffered and dispatched on every request where a key is missing.

### Translation Process

1. Source strings loaded from `lang/en.json`
2. Existing translations loaded from target language files
3. Untranslated strings identified
4. **TranslateStringBatchJob** dispatched with batches of 20 strings
5. OpenAI API called for each string with the configured system prompt
6. Translations saved back to language files (`lang/ar.json`, etc.)
7. Progress tracking updated via the `TranslationProgress` model

### URL Collection from API Endpoints

1. User adds API endpoint URLs in the dashboard
2. `URLCollector` saves each endpoint with `is_api = 1`
3. Each endpoint is fetched (internal request for local URLs, HTTP for external)
4. JSON array response is parsed
5. Each URL from the response is saved as a regular URL (`is_api = 0`)
6. Duplicate URLs are automatically skipped
7. Saved endpoints can be re-fetched at any time to discover new content

> **Local API detection:** When `api_scan_internal` is enabled (default), URLs pointing to `127.0.0.1`, `localhost`, or `::1` are fetched via internal Laravel requests (`app()->handle()`) instead of HTTP. This avoids the deadlock that occurs when `php artisan serve` (single-threaded) tries to make an HTTP request to itself.

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

### Eloquent Models

The system uses three Eloquent models instead of raw `DB::table()` queries for all database operations:

#### TranslationUrl Model

```php
use App\Models\Translate\TranslationUrl;

// Get all active extractable URLs (is_api = 0, active = 1)
$urls = TranslationUrl::extractable()->pluck('url');

// Get all regular URLs
$urls = TranslationUrl::regularUrls()->get();

// Get all API endpoints
$endpoints = TranslationUrl::apiEndpoints()->get();

// Get only active records
$active = TranslationUrl::active()->get();
```

Available scopes:
- `active()` — where `active = true`
- `regularUrls()` — where `is_api = 0`
- `apiEndpoints()` — where `is_api = 1`
- `extractable()` — where `active = true` AND `is_api = 0`

#### TranslationProgress Model

```php
use App\Models\Translate\TranslationProgress;

// Get string extraction progress
$extraction = TranslationProgress::stringExtraction()->first();
echo $extraction->percentage; // 75.5
echo $extraction->status;     // 'running', 'completed', or 'idle'

// Get translation progress for Arabic
$progress = TranslationProgress::translation()->forLocale('ar')->first();
echo $progress->completed . ' of ' . $progress->total;
```

Available scopes:
- `stringExtraction()` — where `type = 'string_extraction'` and `locale IS NULL`
- `translation()` — where `type = 'translation'`
- `forLocale($locale)` — where `locale = $locale`

Computed attributes:
- `$progress->percentage` — calculated completion percentage (0-100)
- `$progress->status` — returns `'idle'`, `'running'`, or `'completed'`

#### MissingTranslation Model

```php
use App\Models\Translate\MissingTranslation;

// Record a missing key (upsert — increments occurrences if exists, creates if new)
MissingTranslation::record('Hello World', 'ar');

// Get all missing keys for a locale as a flat array
$keys = MissingTranslation::keysForLocale('ar');

// Query with scopes
$missing = MissingTranslation::targetLocales()->recentFirst()->get();
$missing = MissingTranslation::forLocale('ar')->mostFrequent()->get();
$missing = MissingTranslation::since(now()->subDay())->get();

// Clear operations
MissingTranslation::clearLocale('ar');   // Clear all missing keys for Arabic
MissingTranslation::clearAll();          // Clear all missing keys for all locales
```

Available scopes:
- `forLocale($locale)` — where `locale = $locale`
- `sourceLocale()` — where `locale = source_locale` (from config)
- `targetLocales()` — where `locale != source_locale`
- `recentFirst()` — order by `last_seen DESC`
- `mostFrequent()` — order by `occurrences DESC`
- `since($datetime)` — where `last_seen >= $datetime`

### API Endpoints for Dynamic URLs

If you have dynamic content (products, articles, etc.), create API endpoints that return a flat JSON array of URLs:

```php
// routes/api.php
Route::get('/sitemaps/products', function () {
    return Product::all()->map(fn($p) => url('/products/' . $p->slug))->values();
});

Route::get('/sitemaps/blog', function () {
    return Article::published()->get()->map(fn($a) => url('/articles/' . $a->slug))->values();
});
```

Then add the endpoints in the dashboard's **API Endpoints** section:

```
https://your-app.com/api/sitemaps/products
https://your-app.com/api/sitemaps/blog
```

The system will:
1. Save each endpoint to the database (with `is_api = 1`)
2. Fetch the JSON response from each endpoint
3. Import every URL from the response as a regular extractable URL
4. Skip duplicates automatically
5. Allow re-fetching at any time to pick up new content

> **Local API detection:** When `api_scan_internal` is enabled in config (default), local endpoints (127.0.0.1, localhost) are fetched via internal Laravel requests instead of HTTP. This avoids the single-threaded `php artisan serve` deadlock where the server can't respond to itself.

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

### Inline Translation Editor

The **Translation Status** tab shows completion percentage per language. Clicking a language opens an inline editor where you can:

- **Search** strings by English source text or existing translation
- **Edit translations manually** — type directly in the input field, saves on Enter or blur
- **AI-translate individual strings** — click the sparkles (✨) icon to translate a single string via OpenAI
- **See translation status** — green checkmark for translated, grey dash for missing

This is useful for reviewing AI translations, fixing specific strings, or translating a few strings without running a full batch.

### Missing Keys Dashboard

The **Missing Keys** tab provides real-time visibility into untranslated strings detected from live traffic:

- **Grouped by locale** — see which languages have the most gaps at a glance
- **Occurrence tracking** — know which strings are hit most frequently so you can prioritize
- **First/last seen timestamps** — understand when strings appeared and how recent they are
- **Individual AI translate** — click ✨ on any row, a job dispatches immediately, the row disappears instantly
- **Bulk translate per locale** — click "Translate All" on a locale group, the table rows hide and a progress bar appears with real-time updates
- **Processing banner** — a top-of-tab indicator appears when any translations are in progress, showing batch locale names and per-locale progress bars
- **Clear Resolved** — scans missing keys against the target locale JSON files and removes entries that have been translated since they were logged
- **Clear All** — wipes the entire `translation_missing` table

## 🐛 Troubleshooting

### Strings Not Being Collected

1. **Verify the service provider is registered** in `bootstrap/providers.php`:
   ```php
   App\Providers\Translate\TranslationServiceProvider::class,
   ```

2. **Test passive collection manually** (requires `runtime_collection = true` or an active scan):
   ```bash
   php artisan tinker
   >>> __('test string that does not exist yet')
   # The key is buffered in MissingKeyBufferService and dispatched via ProcessMissingKeysJob
   # after the terminating callback fires. Check en.json after the job runs:
   >>> \App\Jobs\Translate\ProcessMissingKeysJob::dispatchSync(['test string that does not exist yet'], [], 'en')
   ```

3. **Enable debug logging** in `config/translation.php`:
   ```php
   'log_process' => true,
   ```
   Then check `storage/logs/laravel.log` for detailed extraction logs.

4. **Verify routes are accessible:**
   ```bash
   curl http://127.0.0.1:8000/home
   ```

5. **Verify helper functions are autoloaded:**
   ```bash
   composer dump-autoload
   ```

6. **Verify URLs exist in the database:**
   ```bash
   php artisan tinker
   >>> \App\Models\Translate\TranslationUrl::extractable()->count()
   # Should return > 0
   ```

7. **Clear view cache:**
   ```bash
   php artisan view:clear
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

### API Endpoints Not Importing URLs

1. **Verify endpoint returns a flat JSON array:**
   ```bash
   curl https://your-app.com/api/sitemaps/blog
   # Should return: ["https://...", "https://...", ...]
   ```

2. **Check that the response is valid JSON** — the system expects a flat array of URL strings, not nested objects.

3. **Check logs for errors:**
   ```bash
   tail -f storage/logs/laravel.log | grep "URLCollector"
   ```

4. **Verify the endpoint is saved:**
   ```bash
   php artisan tinker
   >>> \App\Models\Translate\TranslationUrl::apiEndpoints()->pluck('url')
   ```

### Local API Endpoints Timing Out

If API endpoints on `127.0.0.1` or `localhost` time out with `php artisan serve`, ensure `api_scan_internal` is enabled in config:

```php
// config/translation.php
'urls' => [
    'api_scan_internal' => true,
],
```

This uses internal Laravel requests (`app()->handle()`) instead of HTTP to avoid the single-threaded server deadlock.

### Missing Keys Not Appearing

1. **Verify the hook is active** — the `TranslationServiceProvider` must be registered
2. **Check that you're visiting a target locale:** Missing keys are only logged for target locales (e.g., `/ar/about`), not for the source locale (English). Source locale keys are added directly to `en.json`.
3. **Check the table:**
   ```bash
   php artisan tinker
   >>> \App\Models\Translate\MissingTranslation::count()
   ```

## 📖 API Reference

### TranslationUrl Model

```php
use App\Models\Translate\TranslationUrl;

// Scopes
TranslationUrl::active();          // active = true
TranslationUrl::regularUrls();     // is_api = 0
TranslationUrl::apiEndpoints();    // is_api = 1
TranslationUrl::extractable();     // active = true AND is_api = 0
```

### TranslationProgress Model

```php
use App\Models\Translate\TranslationProgress;

// Scopes
TranslationProgress::stringExtraction();      // type = 'string_extraction', locale IS NULL
TranslationProgress::translation();           // type = 'translation'
TranslationProgress::forLocale('ar');         // locale = 'ar'

// Computed attributes
$record->percentage;  // float (0-100)
$record->status;      // 'idle' | 'running' | 'completed'
```

### MissingTranslation Model

```php
use App\Models\Translate\MissingTranslation;

// Record a missing key (upsert with occurrence increment)
MissingTranslation::record('About Us', 'ar');

// Get flat array of keys for a locale
$keys = MissingTranslation::keysForLocale('ar');

// Scopes
MissingTranslation::forLocale('ar');          // locale = 'ar'
MissingTranslation::sourceLocale();           // locale = source_locale
MissingTranslation::targetLocales();          // locale != source_locale
MissingTranslation::recentFirst();            // ORDER BY last_seen DESC
MissingTranslation::mostFrequent();           // ORDER BY occurrences DESC
MissingTranslation::since(now()->subDay());   // last_seen >= given datetime

// Clear operations
MissingTranslation::clearLocale('ar');
MissingTranslation::clearAll();
```

### StringExtractor Service

```php
use App\Services\Translate\StringExtractor;

$extractor = new StringExtractor();

// Visit a URL internally — triggers handleMissingKeysUsing for all __() calls
$extractor->extractFromUrl('https://your-app.com/home');
// Returns void — the hook handles saving to en.json and logging to translation_missing

// Get all keys from a language file
$allKeys = $extractor->getAllKeys('en');
// Returns: ['Hello World' => 'Hello World', 'About Us' => 'About Us', ...]
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

// Get target locales from config
$locales = $translator->getTargetLocales();

// Check if configured
if ($translator->isConfigured()) {
    // API key is set
}
```

### URLCollector Service

```php
use App\Services\Translate\URLCollector;

$collector = new URLCollector();

// Add a single URL (returns null if duplicate)
$record = $collector->addUrl('https://your-app.com/home');

// Add multiple URLs at once (returns count of newly added)
$added = $collector->addBulk([
    'https://your-app.com/home',
    'https://your-app.com/about',
]);

// Add an API endpoint (saved with is_api = 1)
$record = $collector->addApiEndpoint('https://your-app.com/api/sitemaps/blog');

// Fetch URLs from a single API endpoint (saves endpoint + imports response URLs)
$added = $collector->collectFromApiEndpoint('https://your-app.com/api/sitemaps/blog');

// Fetch URLs from multiple API endpoints
$added = $collector->collectFromApiEndpoints([
    'https://your-app.com/api/sitemaps/blog',
    'https://your-app.com/api/sitemaps/products',
]);

// Re-fetch all saved API endpoints to discover new URLs
$added = $collector->refreshAllApiEndpoints();

// Get all extractable URLs (active, non-API) as a flat array
$urls = $collector->getExtractableUrls();

// Get count of extractable URLs
$count = $collector->getExtractableCount();

// Remove a URL by ID
$collector->removeById(42);

// Toggle active/inactive
$collector->toggleActive(42);

// Clear operations
$collector->clearRegularUrls();     // Remove all regular URLs (keeps API endpoints)
$collector->clearApiEndpoints();    // Remove all API endpoints (keeps regular URLs)
$collector->clearAll();             // Remove everything
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