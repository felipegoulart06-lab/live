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

        return Db::transaction(static function () use ($key, $max, $windowSeconds, $now): bool {
            $row = Db::first('SELECT hits, window_start FROM rate_limits WHERE rate_key = :k', ['k' => $key]);
            if (!$row || (int) $row['window_start'] < $now - $windowSeconds) {
                Db::run(
                    'INSERT INTO rate_limits (rate_key, hits, window_start) VALUES (:k, 1, :now)
                     ON CONFLICT (rate_key) DO UPDATE SET hits = 1, window_start = excluded.window_start',
                    ['k' => $key, 'now' => $now]
                );

                return false;
            }

            Db::run('UPDATE rate_limits SET hits = hits + 1 WHERE rate_key = :k', ['k' => $key]);

            return (int) $row['hits'] + 1 > $max;
        });
    }

    public static function clear(string $key): void
    {
        Db::run('DELETE FROM rate_limits WHERE rate_key = :k', ['k' => substr(hash('sha256', $key), 0, 48)]);
    }
}
