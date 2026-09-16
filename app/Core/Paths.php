<?php

declare(strict_types=1);

namespace App\Core;

final class Paths
{
    public static function serverless(): bool
    {
        return (string) getenv('VERCEL') === '1' || (string) ($_ENV['VERCEL'] ?? '') === '1';
    }

    public static function storage(): string
    {
        if (self::serverless()) {
            $root = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'nexo';
            foreach ([$root, $root . '/cache', $root . '/sessions', $root . '/logs'] as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            return $root;
        }

        return BASE_PATH . DIRECTORY_SEPARATOR . 'storage';
    }
}
