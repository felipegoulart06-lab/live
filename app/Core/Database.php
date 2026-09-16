<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    /** @param array<string, mixed> $config */
    public static function connect(array $config = []): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $relative = (string) ($config['path'] ?? env('DB_PATH', 'storage/nexo.sqlite'));
        $path = $relative;
        if (!preg_match('#^[A-Za-z]:[\\\\/]#', $relative) && !str_starts_with($relative, '/')) {
            $path = BASE_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
        } catch (PDOException $e) {
            throw new RuntimeException('Falha ao abrir o arquivo de dados.');
        }

        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new RuntimeException('Banco de dados não inicializado.');
        }

        return self::$pdo;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
