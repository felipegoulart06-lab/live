<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Repositories\SettingRepository;

final class SettingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $map = (new SettingRepository())->allAsMap();

        return $map[$key] ?? $default;
    }

    public static function forgetCache(): void
    {
        Cache::forget('settings.map');
    }

    /** @param array<string, array{0:string,1:string}> $pairs key => [value, group] */
    public static function putMany(array $pairs): void
    {
        $repo = new SettingRepository();
        foreach ($pairs as $key => [$value, $group]) {
            $repo->upsert($key, $value, $group);
        }
        self::forgetCache();
    }

    public static function googleEnabled(): bool
    {
        $flag = self::get('google_oauth_enabled', env('GOOGLE_OAUTH_ENABLED', '0'));
        $id = self::googleClientId();
        $secret = self::googleClientSecret();

        return ($flag === '1' || $flag === 1 || $flag === true)
            && $id !== ''
            && $secret !== '';
    }

    public static function googleClientId(): string
    {
        $fromDb = trim((string) self::get('google_client_id', ''));

        return $fromDb !== '' ? $fromDb : (string) env('GOOGLE_CLIENT_ID', '');
    }

    public static function googleClientSecret(): string
    {
        $fromDb = trim((string) self::get('google_client_secret', ''));

        return $fromDb !== '' ? $fromDb : (string) env('GOOGLE_CLIENT_SECRET', '');
    }

    public static function ensureDefaults(): void
    {
        $defaults = [
            'google_oauth_enabled' => ['0', 'auth'],
            'google_client_id' => ['', 'auth'],
            'google_client_secret' => ['', 'auth'],
        ];
        $map = (new SettingRepository())->allAsMap();
        $missing = [];
        foreach ($defaults as $key => $pair) {
            if (!array_key_exists($key, $map)) {
                $missing[$key] = $pair;
            }
        }
        if ($missing !== []) {
            self::putMany($missing);
        }
    }
}
