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
        // Force HTTPS
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

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