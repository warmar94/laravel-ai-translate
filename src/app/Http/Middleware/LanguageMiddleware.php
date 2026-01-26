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