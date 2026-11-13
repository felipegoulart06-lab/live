<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    /** Counts one hit and reports whether the key went over `$max` within the window. */
    public static function tooManyAttempts(string $key, int $max, int $windowSeconds): bool
    {
        $key = substr(hash('sha256', $key), 0, 48);
        $now = time();

        $cutoff = $now - $windowSeconds;
        $hits = Db::transaction(static function () use ($key, $now, $cutoff): int {
            Db::run(
                'INSERT INTO rate_limits (rate_key, hits, window_start) VALUES (:k, 1, :now)
                 ON CONFLICT (rate_key) DO UPDATE SET
                    hits = CASE WHEN rate_limits.window_start < :cutoff THEN 1 ELSE rate_limits.hits + 1 END,
                    window_start = CASE WHEN rate_limits.window_start < :cutoff THEN :now2 ELSE rate_limits.window_start END',
                ['k' => $key, 'now' => $now, 'now2' => $now, 'cutoff' => $cutoff]
            );

            return (int) Db::value('SELECT hits FROM rate_limits WHERE rate_key = :k', ['k' => $key]);
        });

        return $hits > $max;
    }

    public static function clear(string $key): void
    {
        Db::run('DELETE FROM rate_limits WHERE rate_key = :k', ['k' => substr(hash('sha256', $key), 0, 48)]);
    }
}
