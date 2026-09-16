<?php

declare(strict_types=1);

namespace App\Core;

final class Cache
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return $default;
        }

        $payload = json_decode((string) file_get_contents($file), true);
        if (!is_array($payload) || ($payload['expires'] ?? 0) < time()) {
            @unlink($file);

            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public static function put(string $key, mixed $value, int $seconds = 300): void
    {
        $dir = Paths::storage() . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::path($key), json_encode([
            'expires' => time() + $seconds,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public static function remember(string $key, int $seconds, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::put($key, $value, $seconds);

        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function path(string $key): string
    {
        return Paths::storage() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}
