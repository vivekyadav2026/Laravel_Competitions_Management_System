<?php

namespace App\Helpers {

    class CacheBuster
    {
        /**
         * Generate an asset URL with an automatic file-modification-time query string (?v=timestamp)
         * to guarantee that browsers immediately fetch updated CSS, JS, and image assets.
         */
        public static function asset(string $path, ?bool $secure = null): string
        {
            $cleanPath = ltrim($path, '/\\');
            $fullPath = public_path($cleanPath);

            $version = file_exists($fullPath) ? filemtime($fullPath) : time();

            return asset($cleanPath, $secure) . '?v=' . $version;
        }
    }

}

namespace {

    if (!function_exists('versioned_asset')) {
        /**
         * Global helper to return asset URL with automatic cache-busting timestamp.
         */
        function versioned_asset(string $path, ?bool $secure = null): string
        {
            return \App\Helpers\CacheBuster::asset($path, $secure);
        }
    }

}
