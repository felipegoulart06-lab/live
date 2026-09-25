<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Autoloader;
use App\Core\Env;

define('BASE_PATH', dirname(__DIR__));

$staticPath = (string) ($_GET['__path'] ?? '');
if ($staticPath === '') {
    $staticPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
}
$staticPath = '/' . ltrim(rawurldecode(str_replace(['\\', '%2F', '%2f'], '/', $staticPath)), '/');
if (str_contains($staticPath, '..')) {
    $staticPath = '/';
}

$staticAliases = [
    '/favicon.ico' => BASE_PATH . '/public/assets/images/favicon.ico',
    '/apple-touch-icon.png' => BASE_PATH . '/public/assets/images/apple-touch-icon.png',
    '/apple-touch-icon-precomposed.png' => BASE_PATH . '/public/assets/images/apple-touch-icon.png',
];
$staticFile = $staticAliases[$staticPath] ?? null;
if ($staticFile === null && str_starts_with($staticPath, '/assets/')) {
    $staticFile = BASE_PATH . '/public' . $staticPath;
}
if (is_string($staticFile) && is_file($staticFile)) {
    $types = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
    ];
    $ext = strtolower((string) pathinfo($staticFile, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=604800, immutable');
    header('Content-Length: ' . (string) filesize($staticFile));
    readfile($staticFile);
    return;
}

$vendor = BASE_PATH . '/vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
} else {
    require BASE_PATH . '/app/Core/Autoloader.php';
    Autoloader::register();
    require BASE_PATH . '/app/Helpers/functions.php';
}

Env::load(BASE_PATH . '/.env');

try {
    $app = Application::boot();
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine();
        return;
    }
    echo 'Serviço temporariamente indisponível.';
}
