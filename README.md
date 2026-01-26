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
- ✅ Global view helpers for easy integration

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
- 🎨 **View Helpers**: Global `$langUrl()`, `$isRtl`, `$isRoute()`, and `$langCode` variables

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

- PHP 8.1+
- Laravel 11+
- OpenAI API Key
- Queue worker (Redis recommended, Database queue supported)
- Livewire 3.x


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

### 4. Register Language Middleware

Add the language middleware to `boostrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

//Declare the middleware
use App\Http\Middleware\LanguageMiddleware;

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

### 5. Configure Environment

Add your OpenAI API key and settings to `.env`:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-4o-mini

# Translation Settings
TRANSLATION_COLLECTION_MODE=false
TRANSLATION_LOG_PROCESS=false
TRANSLATION_URL_DELAY=1

# Queue Configuration (recommended: database)
QUEUE_CONNECTION=database
```

### 6. Edit AppServiceProvider's boot() 
Add our custom code into the Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Declare this on top
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Translation collection mode - Blade directive
        Blade::directive('__t', function ($expression) {
            if (config('translation.translation_collection_mode', false)) {
                return "<?php echo '<!--T_START:' . htmlspecialchars(__({$expression}), ENT_QUOTES, 'UTF-8') . ':T_END-->' . __({$expression}); ?>";
            }
            
            return "<?php echo __({$expression}); ?>";
        });

        // ---------------------------
        // Frontend global language variables
        // ---------------------------
        View::share([
            // Current language code
            'langCode' => app()->getLocale(),
            
            // RTL detection
            'isRtl' => in_array(app()->getLocale(), config('translation.rtl_languages', [])),
            
            // Simple langUrl helper for Blade
            'langUrl' => function ($routeName, $params = []) {
                $lang = app()->getLocale();
                if ($lang === 'en') {
                    return route($routeName, $params);
                }
                return route($lang . '.' . $routeName, $params);
            },
            
            // Helper to check if current route matches (works with language prefixes)
            'isRoute' => function ($routeName) {
                $currentRouteName = Route::currentRouteName();
                
                // Check if current route matches exactly
                if ($currentRouteName === $routeName) {
                    return true;
                }
                
                // Check if current route matches with language prefix (ar.home, en.home, etc)
                $allowedLangs = array_keys(config('translation.languages'));
                foreach ($allowedLangs as $lang) {
                    if ($currentRouteName === $lang . '.' . $routeName) {
                        return true;
                    }
                }
                
                return false;
            },
        ]);

    }
}
```

### 7. Configure Queue Workers

**Development Environment:**

If using `composer run dev`, queue workers are typically already running.

**Production/Dedicated Server:**

Start queue workers manually:

```bash
php artisan queue:work
```

(For production, use a process manager like Supervisor)

### 8. Create Required Database Tables
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

### 9. Install Required Files
If you installed via Laravel Command skip this Step.
Copy the following files to your Laravel application:

**Configuration:**
- `config/translation.php`

**Service Providers:**
- `app/Providers/AppServiceProvider.php` (merge with existing)

**Services:**
- `app/Services/Translation/StringExtractor.php`
- `app/Services/Translation/URLCollector.php`
- `app/Services/Translation/AITranslator.php`

**Jobs:**
- `app/Jobs/ScanUrlForStringsJob.php`
- `app/Jobs/TranslateStringBatchJob.php`

**Middleware:**
- `app/Http/Middleware/LanguageMiddleware.php`

**Livewire Components:**
- `app/Livewire/Translation/TranslateMenu.php`
- `resources/views/livewire/translation/translate-menu.blade.php`

**Blade Views:**
- `resources/views/lang.blade.php`


### 10. Clear Cache

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
│   ├── Http/
│   │   └── Middleware/
│   │       └── LanguageMiddleware.php          # Handles language detection and routing
│   │
│   ├── Jobs/
│   │   ├── ScanUrlForStringsJob.php            # Extracts strings from URLs
│   │   └── TranslateStringBatchJob.php         # Translates string batches
│   │
│   ├── Livewire/
│   │   └── Translation/
│   │       └── TranslateMenu.php               # Dashboard controller
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php              # Registers @__t() directive
│   │
│   └── Services/
│       └── Translation/
│           ├── AITranslator.php                # OpenAI API integration
│           ├── StringExtractor.php             # String extraction logic
│           └── URLCollector.php                # URL collection logic
│
├── config/
│   ├── translation.php                         # Main configuration file
│   └── urls.json                              # Generated URL list (auto-created)
│
├── lang/
│   ├── en.json                                # English strings (source)
│   ├── ar.json                                # Arabic translations
│   └── [locale].json                          # Additional languages
│
├── resources/
│   └── views/
│       ├── lang.blade.php                     # SEO hreflang tags
│       └── livewire/
│           └── translation/
│               └── translate-menu.blade.php    # Dashboard UI
│
└── routes/
    └── web.php                                # Route definitions with langRoute()
```

## 🛣️ Routing System

### The `langRoute()` Helper

The system includes a powerful `langRoute()` helper that automatically creates routes for all configured languages. Add this to your `routes/web.php`:

```php
/**
 * Language Route Helper
 * Creates both non-prefixed (English) and language-prefixed routes
 * Example: /about and /ar/about
 */
function langRoute($method, $path, $action, $name = null, $where = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    
    // 1. Main route (no language prefix - English)
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
// Simple routes
langRoute('get', '/', HomePage::class, 'home');
langRoute('get', '/contact', Contact::class, 'contact');
langRoute('get', '/about', About::class, 'about');

// Routes with parameters
langRoute('get', '/products/{slug}', ProductShow::class, 'products.show');
langRoute('get', '/blog/{category}/{slug}', BlogPost::class, 'blog.post');

// Routes with middleware
langRoute('get', '/dashboard', Dashboard::class, 'dashboard')
    ->middleware(['auth', 'verified']);

// Routes with where constraints
langRoute('get', '/user/{id}', UserProfile::class, 'user.profile', ['id' => '[0-9]+']);

// POST routes work too
langRoute('post', '/contact', ContactSubmit::class, 'contact.submit');
```

### Generating Language-Specific URLs

Use the `$langUrl()` helper in your views:

```blade
{{-- Generates URL for current language --}}
<a href="{{ $langUrl('about') }}">About Us</a>

{{-- With parameters --}}
<a href="{{ $langUrl('products.show', ['slug' => $product->slug]) }}">
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
namespace App\Http\Middleware;

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

Add to `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... other middleware
    'language' => \App\Http\Middleware\LanguageMiddleware::class,
];
```

Or add to web middleware group for automatic application:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\LanguageMiddleware::class,
    ],
];
```

## 🎨 View Helpers

The system provides several global view helpers via `AppServiceProvider`:

### Available Helpers

```blade
{{-- Current language code --}}
{{ $langCode }} {{-- Output: 'en', 'ar', 'es', etc. --}}

{{-- RTL detection --}}
@if($isRtl)
    <div dir="rtl" class="text-right">
        Arabic or Hebrew content
    </div>
@endif

{{-- Generate language-specific URLs --}}
<a href="{{ $langUrl('about') }}">About</a>
<a href="{{ $langUrl('products.show', ['id' => 5]) }}">Product</a>

{{-- Check current route --}}
@if($isRoute('about'))
    <li class="active">About</li>
@endif

{{-- Works with language prefixes automatically --}}
@if($isRoute('products.show'))
    <span class="badge">Current</span>
@endif
```

### Real-World Example: Header Navigation

Here's how to use the helpers in a navigation header:

```blade
@php
    $isRtl = in_array(app()->getLocale(), config('translation.rtl_languages', []));
@endphp

<header>
    <nav class="{{ $isRtl ? 'flex-row-reverse' : '' }}">
        <a href="{{ $langUrl('home') }}" 
           class="{{ $isRoute('home') ? 'active' : '' }}">
            @__t('Home')
        </a>
        
        <a href="{{ $langUrl('about') }}" 
           class="{{ $isRoute('about') ? 'active' : '' }}">
            @__t('About')
        </a>
        
        <a href="{{ $langUrl('contact') }}" 
           class="{{ $isRoute('contact') ? 'active' : '' }}">
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
            {{ config('translation.language_names.' . $langCode . '.' . $locale) }}
        </a>
    @endforeach
</div>
```

### Using in Livewire Components

The helpers work seamlessly with Livewire:

```blade
<div>
    <a href="{{ $langUrl('profile') }}" wire:navigate>
        @__t('My Profile')
    </a>
    
    <button wire:click="$set('locale', '{{ $langCode }}')" 
            class="{{ $isRtl ? 'mr-auto' : 'ml-auto' }}">
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
    'log_process' => env('TRANSLATION_LOG_PROCESS', false),
    
    // Enable collection mode (wraps strings with HTML comments)
    'translation_collection_mode' => env('TRANSLATION_COLLECTION_MODE', false),
    
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
    'rtl_languages' => ['ar'],
    
    // Language file paths
    'language_files' => [
        'en' => lang_path('en.json'),
        'ar' => lang_path('ar.json'),
    ],
    
    // URL scanning settings
    'urls' => [
        'delay_between_requests' => env('TRANSLATION_URL_DELAY', 1),
        'batch_size' => 50,
        'timeout' => 20,
    ],
    
    // AI translation settings
    'translation' => [
        'ai_provider' => 'openai',
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_key' => env('OPENAI_API_KEY'),
        'batch_size' => 20,
        'rate_limit_per_minute' => 300,
        'max_retries' => 3,
        'system_prompt' => 'You are a professional translator. Translate the following text to {language}. Return ONLY the translated text with no explanations, greetings, or additional commentary.',
    ],
];
```

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
'rtl_languages' => ['ar', 'ur', 'he'],
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

/**
 * Language Route Helper
 * Copy this function into your routes/web.php file
 */
function langRoute($method, $path, $action, $name = null, $where = []) {
    $allowedLangs = array_keys(config('translation.languages'));
    
    // Main route (no prefix - English)
    $mainRoute = Route::$method($path, $action)->middleware('language');
    if ($name) $mainRoute->name($name);
    if (!empty($where)) $mainRoute->where($where);
    
    // Language-prefixed routes
    foreach ($allowedLangs as $lang) {
        $langRoute = Route::$method('/' . $lang . $path, $action)->middleware('language');
        if ($name) $langRoute->name($lang . '.' . $name);
        if (!empty($where)) $langRoute->where($where);
    }
    
    return $mainRoute;
}

/*
|--------------------------------------------------------------------------
| Your Application Routes
|--------------------------------------------------------------------------
*/

// Basic pages
langRoute('get', '/', Home::class, 'home');
langRoute('get', '/about', About::class, 'about');
langRoute('get', '/contact', Contact::class, 'contact');

// Products with parameter
langRoute('get', '/products', ProductIndex::class, 'products.index');
langRoute('get', '/products/{slug}', ProductShow::class, 'products.show');

// Authenticated routes
langRoute('get', '/dashboard', Dashboard::class, 'dashboard')
    ->middleware(['auth']);

langRoute('get', '/profile', Profile::class, 'profile')
    ->middleware(['auth', 'verified']);

// POST routes
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

1. **Enable Collection Mode** in `.env`:
   ```env
   TRANSLATION_COLLECTION_MODE=true
   ```

2. **Clear cache:**
   ```bash
   php artisan view:clear
   php artisan config:clear
   ```

3. Click **"Collect Strings"** in the dashboard

4. Monitor the real-time progress bar

5. **Disable Collection Mode** once complete:
   ```env
   TRANSLATION_COLLECTION_MODE=false
   ```

### Step 5: Translate All Keys

1. Verify OpenAI API key is configured

2. Click **"Translate All Keys"**

3. Monitor translation progress for each language

4. Translations are processed in batches via queue workers

## 🔍 How It Works

### The @__t() Directive

The system uses a custom Blade directive that operates in two modes:

#### Collection Mode (TRANSLATION_COLLECTION_MODE=true)

```php
@__t('Hello World')
```

Outputs:
```html
<!--T_START:Hello World:T_END-->Hello World
```

The HTML comments act as markers that the scanner can detect and extract.

#### Translation Mode (TRANSLATION_COLLECTION_MODE=false)

```php
@__t('Hello World')
```

Outputs (if locale is 'ar'):
```
مرحبا بالعالم
```

Simply calls Laravel's `__()` helper to fetch the translated string.

### String Collection Process

1. **ScanUrlForStringsJob** dispatched for each URL
2. Job makes an internal Laravel request to the URL
3. HTML response is scanned for `<!--T_START:...:T_END-->` markers
4. Unique strings are extracted and saved to `lang/en.json`
5. Progress tracking is updated in the database

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

### View Helpers

The `AppServiceProvider` provides global view variables:

```blade
{{-- Current language code --}}
{{ $langCode }} {{-- 'en', 'ar', etc. --}}

{{-- RTL detection --}}
@if($isRtl)
    <div dir="rtl">...</div>
@endif

{{-- Generate language-specific URLs --}}
<a href="{{ $langUrl('about') }}">About</a>

{{-- Check current route --}}
@if($isRoute('about'))
    <li class="active">About</li>
@endif
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

1. **Verify collection mode is enabled:**
   ```bash
   php artisan config:clear
   ```
   Check `.env`:
   ```env
   TRANSLATION_COLLECTION_MODE=true
   ```

2. **Clear view cache:**
   ```bash
   php artisan view:clear
   ```

3. **Enable debug logging:**
   ```env
   TRANSLATION_LOG_PROCESS=true
   ```
   Check `storage/logs/laravel.log`

4. **Verify routes are accessible:**
   ```bash
   curl http://127.0.0.1:8000/home
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

## 📖 API Reference

### StringExtractor Service

```php
use App\Services\Translation\StringExtractor;

$extractor = new StringExtractor();

// Extract strings from a URL
$keys = $extractor->extractFromUrl('https://your-app.com/home');

// Save keys to language file
$newCount = $extractor->saveToLanguageFile($keys, 'en');

// Get all keys from a language file
$allKeys = $extractor->getAllKeys('en');
```

### AITranslator Service

```php
use App\Services\Translation\AITranslator;

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
use App\Services\Translation\URLCollector;

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


## 👨‍💻 Developer: 
https://warmardev.com/

## Docs
https://warmardev.com/docs/laravel-translate.html

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
- Open an issue on GitHub

**Note:** Remember to never commit your `.env` file or expose your OpenAI API key publicly.