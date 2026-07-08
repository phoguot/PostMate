<?php
/**
 * Router cho `php -S` khi preview local (thay thế public/.htaccess — built-in server
 * không đọc .htaccess). Dùng cho launch.json config "backend-preview".
 */
$root = dirname(__DIR__) . '/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/api/#', $uri)) {
    chdir($root);
    require $root . '/index.php';
    return true;
}

$file = $root . $uri;
if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false;
}

chdir($root);
require $root . '/index.html';
return true;
