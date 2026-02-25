<?php

namespace App\Providers\Translate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Translation collection mode - Blade directive
        Blade::directive('__t', function ($expression) {
            return "<?php if(isCollectionMode()): ?>" .
                "<?php echo '<!--T_START:' . htmlspecialchars(__({$expression}), ENT_QUOTES, 'UTF-8') . ':T_END-->' . __({$expression}); ?>" .
                "<?php else: ?>" .
                "<?php echo __({$expression}); ?>" .
                "<?php endif; ?>";
        });
    }
}