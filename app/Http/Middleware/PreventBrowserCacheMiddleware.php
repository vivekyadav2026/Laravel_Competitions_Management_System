<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBrowserCacheMiddleware
{
    /**
     * Handle an incoming request and ensure browsers never cache dynamic application pages or JSON responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Apply strict no-cache headers to dynamic responses
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
