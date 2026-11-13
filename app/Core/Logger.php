<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::write('SECURITY', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        try {
            $context = self::redact($context);
            $dir = Paths::storage() . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $line = sprintf(
                "[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                $level,
                $message,
                $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
            );
            file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
        }
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private static function redact(array $context): array
    {
        $hidden = ['password', 'password_hash', 'current_password', 'token', 'access_token', 'authorization', 'secret', 'document', 'cvv', 'card_number', 'gateway_payload', '_csrf'];
        $clean = [];
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $hidden, true)) {
                $clean[$key] = '[oculto]';
                continue;
            }
            $clean[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $clean;
    }
}
