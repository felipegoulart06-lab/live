<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';

    /** @param array<string, mixed> $config */
    public static function connect(array $config = []): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $url = (string) env('DATABASE_URL', '');
        try {
            self::$pdo = $url !== '' ? self::connectPostgres($url) : self::connectSqlite($config);
        } catch (PDOException $e) {
            Logger::error('Falha de conexão com o banco', ['driver' => self::$driver, 'code' => $e->getCode()]);
            throw new RuntimeException('Falha ao conectar ao banco de dados.');
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

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function isPostgres(): bool
    {
        return self::$driver === 'pgsql';
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    private static function connectPostgres(string $url): PDO
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL inválida.');
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $parts['host'],
            (int) ($parts['port'] ?? 5432),
            ltrim((string) ($parts['path'] ?? '/postgres'), '/') ?: 'postgres',
            (string) ($query['sslmode'] ?? 'require')
        );

        self::$driver = 'pgsql';
        $pdo = new PDO($dsn, rawurldecode((string) ($parts['user'] ?? '')), rawurldecode((string) ($parts['pass'] ?? '')), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Supabase's transaction pooler (port 6543) cannot hold server-side prepared statements.
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 8,
        ]);
        $pdo->exec("SET TIME ZONE '" . str_replace("'", '', (string) env('APP_TIMEZONE', 'America/Sao_Paulo')) . "'");

        return $pdo;
    }

    /** @param array<string, mixed> $config */
    private static function connectSqlite(array $config): PDO
    {
        self::$driver = 'sqlite';
        if (Paths::serverless()) {
            $path = Paths::storage() . DIRECTORY_SEPARATOR . 'cinquentaconto.sqlite';
        } else {
            $relative = (string) ($config['path'] ?? env('DB_PATH', 'storage/cinquentaconto.sqlite'));
            $path = $relative;
            if (!preg_match('#^[A-Za-z]:[\\\\/]#', $relative) && !str_starts_with($relative, '/')) {
                $path = BASE_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
            }
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec(Paths::serverless() ? 'PRAGMA journal_mode = DELETE' : 'PRAGMA journal_mode = WAL');

        return $pdo;
    }
}
