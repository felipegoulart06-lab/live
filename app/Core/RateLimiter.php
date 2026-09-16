<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $max, int $windowSeconds): bool
    {
        $data = Cache::get('rate:' . $key, ['count' => 0, 'start' => time()]);
        if (!is_array($data)) {
            $data = ['count' => 0, 'start' => time()];
        }

        if (time() - (int) $data['start'] > $windowSeconds) {
            $data = ['count' => 0, 'start' => time()];
        }

        $data['count'] = (int) $data['count'] + 1;
        Cache::put('rate:' . $key, $data, $windowSeconds);

        return $data['count'] > $max;
    }
}
