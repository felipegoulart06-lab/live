<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Model;

final class SettingRepository extends Model
{
    protected string $table = 'settings';

    /** @return array<string, string> */
    public function allAsMap(): array
    {
        return Cache::remember('settings.map', 60, function (): array {
            try {
                $rows = $this->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            } catch (\Throwable) {
                return [];
            }

            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row['setting_key']] = (string) $row['setting_value'];
            }

            return $map;
        });
    }

    public function upsert(string $key, string $value, string $group = 'general'): void
    {
        $now = now();
        $existing = $this->query(
            'SELECT id FROM settings WHERE setting_key = :key LIMIT 1',
            ['key' => $key]
        )->fetch();

        if ($existing) {
            $this->query(
                'UPDATE settings SET setting_value = :value, setting_group = :group_name, updated_at = :updated_at WHERE setting_key = :key',
                [
                    'value' => $value,
                    'group_name' => $group,
                    'updated_at' => $now,
                    'key' => $key,
                ]
            );
        } else {
            $this->query(
                'INSERT INTO settings (setting_key, setting_value, setting_group, created_at, updated_at)
                 VALUES (:key, :value, :group_name, :created_at, :updated_at)',
                [
                    'key' => $key,
                    'value' => $value,
                    'group_name' => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function grouped(): array
    {
        return $this->query(
            'SELECT setting_key, setting_value, setting_group FROM settings ORDER BY setting_group ASC, setting_key ASC'
        )->fetchAll();
    }
}
