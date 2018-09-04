<?php

namespace App\Http\Middleware;

use Closure;

class TranslationMiddleware
{

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
        $fallbackLocale = config('app.fallback_locale');
        
        // Get locale from param
        $locale = $request->input('locale');

        if (!$locale) {
            // Get locale from headers
            $locale = $request->headers->get('Accept-Language', $fallbackLocale);
            
        }

        app('translator')->setLocale($locale);
        return $next($request);
    }
}
