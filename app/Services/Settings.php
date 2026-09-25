<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class Settings
{
    public const DEFAULTS = [
        'platform_name' => ['CinquentaConto', 'general'],
        'platform_tagline' => ['Horas de vídeo para a sua empresa. O tema é definido por quem paga.', 'general'],
        'support_email' => ['suporte@cinquentaconto.com.br', 'general'],
        'meta_description' => ['Contrate horas de vídeo com criadores verificados. A empresa define o tema; o contato fica dentro da plataforma.', 'seo'],
        'platform_fee_percent' => ['15', 'business'],
        'request_expiry_hours' => ['72', 'business'],
        'min_package_hours' => ['1', 'business'],
        'max_package_hours' => ['12', 'business'],
        'max_packages_per_listing' => ['6', 'business'],
        'max_images_per_listing' => ['8', 'business'],
        'require_listing_approval' => ['1', 'business'],
        'maintenance_mode' => ['0', 'general'],
        'home_hero_title' => ['Encontre quem grava o vídeo da sua empresa', 'content'],
        'home_hero_subtitle' => ['A empresa contrata horas de vídeo com criadores da plataforma. O tema é definido por quem paga, e telefone e WhatsApp do criador não aparecem no anúncio.', 'content'],
        'home_hero_image' => ['images/hero-studio.jpg', 'content'],
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Db::all('SELECT setting_key, setting_value FROM settings') as $row) {
                self::$cache[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
        }

        return self::$cache[$key] ?? self::DEFAULTS[$key][0] ?? $default;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key, 0);
    }

    /** @param array<string, string> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Db::run(
                'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t)
                 ON CONFLICT (setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at',
                ['k' => $key, 'v' => $value, 'g' => self::DEFAULTS[$key][1] ?? 'general', 't' => now()]
            );
        }
        self::$cache = null;
    }
}
