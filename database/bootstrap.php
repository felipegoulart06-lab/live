<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;
use App\Core\SqliteSchema;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!class_exists(Autoloader::class) && !class_exists(\Composer\Autoload\ClassLoader::class)) {
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
Database::connect(['path' => env('DB_PATH', 'storage/nexo.sqlite')]);
if (!SqliteSchema::isInstalled(Database::pdo())) {
    SqliteSchema::install(Database::pdo());
}
