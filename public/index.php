<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Autoloader;
use App\Core\Env;

define('BASE_PATH', dirname(__DIR__));

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
