<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    /** @param array<string, mixed> $config */
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli' && !isset($_SERVER['REQUEST_METHOD'])) {
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
            self::ensureCsrf();

            return;
        }

        $lifetime = (int) ($config['lifetime'] ?? 120) * 60;
        $secure = (bool) ($config['secure'] ?? false) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || Paths::serverless();

        session_name((string) ($config['name'] ?? 'cc_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '200');

        session_set_save_handler(new DatabaseSessionHandler($lifetime), true);
        session_start();

        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        }
        if (time() - (int) $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }

        self::ensureCsrf();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['_created'] = time();
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function csrfToken(): string
    {
        self::ensureCsrf();

        return (string) $_SESSION['_csrf'];
    }

    private static function ensureCsrf(): void
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }
}
