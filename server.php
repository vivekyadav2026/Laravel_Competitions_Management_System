<?php

/**
 * Fsaludoclub — Laravel Server Router
 *
 * This file allows emulating Apache's "mod_rewrite" functionality from the
 * built-in PHP web server and acts as the root front-controller fallback
 * when deploying on shared/cPanel or local XAMPP environments.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
