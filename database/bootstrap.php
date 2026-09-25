<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;
use App\Core\Schema;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!class_exists(Autoloader::class, false)) {
    $vendor = BASE_PATH . '/vendor/autoload.php';
    if (is_file($vendor)) {
        require $vendor;
    } else {
        require BASE_PATH . '/app/Core/Autoloader.php';
        Autoloader::register();
        require BASE_PATH . '/app/Helpers/functions.php';
    }
}

Env::load(BASE_PATH . '/.env');
date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Sao_Paulo'));
Database::connect(['path' => env('DB_PATH', 'storage/cinquentaconto.sqlite')]);
Schema::install();
