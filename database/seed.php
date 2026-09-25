<?php

declare(strict_types=1);

/**
 * php database/seed.php          seeds an empty database (SQLite or the Postgres in DATABASE_URL)
 * php database/seed.php --fresh  SQLite only: deletes the local file first
 */

$fresh = in_array('--fresh', $argv ?? [], true);

if ($fresh) {
    define('BASE_PATH', dirname(__DIR__));
    require BASE_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    require BASE_PATH . '/app/Helpers/functions.php';
    \App\Core\Env::load(BASE_PATH . '/.env');
    if ((string) env('DATABASE_URL', '') !== '') {
        fwrite(STDERR, "--fresh só funciona com SQLite local. Para o Postgres, limpe as tabelas manualmente.\n");
        exit(1);
    }
    $file = BASE_PATH . '/' . env('DB_PATH', 'storage/cinquentaconto.sqlite');
    foreach ([$file, $file . '-wal', $file . '-shm'] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

require __DIR__ . '/bootstrap.php';

if ((int) \App\Core\Db::value('SELECT COUNT(*) FROM users') > 0) {
    fwrite(STDERR, "O banco já tem usuários. Nada foi alterado.\n");
    exit(1);
}

$started = microtime(true);
(new \App\Seed\DemoSeeder())->run();
printf("Seed concluído em %.1fs (%s).\n", microtime(true) - $started, \App\Core\Database::driver());
