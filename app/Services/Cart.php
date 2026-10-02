<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\Session;

/** Session cart. Anyone can fill it; only a company account can pay. */
final class Cart
{
    public const KEY = 'cart';
    public const MAX = 8;

    /** @return array<int, array<string, mixed>> */
    public static function raw(): array
    {
        $items = Session::get(self::KEY, []);

        return is_array($items) ? array_values($items) : [];
    }

    public static function count(): int
    {
        return count(self::raw());
    }

    /** @param array<int, int> $addonIds */
    public static function add(array $listing, int $packageId, array $addonIds, array $meta = []): void
    {
        $items = self::raw();
        $listingId = (int) $listing['id'];
        $addonIds = array_values(array_unique(array_filter($addonIds, static fn (int $id): bool => $id > 0)));
        $line = [
            'id' => uuid4(),
            'listing_id' => $listingId,
            'listing_slug' => (string) $listing['slug'],
            'package_id' => $packageId,
            'addon_ids' => $addonIds,
            'theme' => mb_substr(trim((string) ($meta['theme'] ?? '')), 0, 300),
            'briefing' => mb_substr(trim((string) ($meta['briefing'] ?? '')), 0, 4000),
            'desired_date' => (string) ($meta['desired_date'] ?? ''),
            'desired_time' => (string) ($meta['desired_time'] ?? ''),
        ];

        $replaced = false;
        foreach ($items as $i => $item) {
            if ((int) ($item['listing_id'] ?? 0) === $listingId) {
                $items[$i] = $line;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            if (count($items) >= self::MAX) {
                array_shift($items);
            }
            $items[] = $line;
        }

        Session::set(self::KEY, array_values($items));
    }

    public static function remove(string $id): void
    {
        $items = array_values(array_filter(self::raw(), static fn (array $item): bool => (string) ($item['id'] ?? '') !== $id));
        Session::set(self::KEY, $items);
    }

    public static function takeFirst(): ?array
    {
        $items = self::raw();
        if ($items === []) {
            return null;
        }
        $first = array_shift($items);
        Session::set(self::KEY, array_values($items));

        return $first;
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    /**
     * Rebuilds lines with live prices. Drops listings that are no longer for sale.
     * @return array{lines: array<int, array<string, mixed>>, total_cents: int, days: int}
     */
    public static function snapshot(): array
    {
        $lines = [];
        $total = 0;
        $days = 0;
        $kept = [];

        foreach (self::raw() as $item) {
            $listingId = (int) ($item['listing_id'] ?? 0);
            $packageId = (int) ($item['package_id'] ?? 0);
            $listing = Db::first(
                "SELECT l.id, l.uuid, l.slug, l.title, l.cover_path, l.status, l.deleted_at, p.display_name AS creator_name
                 FROM listings l JOIN profiles p ON p.user_id = l.creator_id
                 WHERE l.id = :id AND l.status = 'active' AND l.deleted_at IS NULL",
                ['id' => $listingId]
            );
            $package = $listing ? Db::first(
                'SELECT id, hours, price_cents, delivery_days FROM listing_packages WHERE id = :id AND listing_id = :l',
                ['id' => $packageId, 'l' => $listingId]
            ) : null;
            if (!$listing || !$package) {
                continue;
            }

            $addonIds = array_values(array_filter(array_map('intval', (array) ($item['addon_ids'] ?? [])), static fn (int $id): bool => $id > 0));
            $addons = [];
            $addonCents = 0;
            $extraDays = 0;
            if ($addonIds !== []) {
                $placeholders = [];
                $params = ['l' => $listingId];
                foreach ($addonIds as $i => $id) {
                    $placeholders[] = ':a' . $i;
                    $params['a' . $i] = $id;
                }
                $addons = Db::all(
                    'SELECT id, name, price_cents, extra_days FROM listing_addons WHERE listing_id = :l AND id IN (' . implode(',', $placeholders) . ') ORDER BY sort_order, id',
                    $params
                );
                foreach ($addons as $addon) {
                    $addonCents += (int) $addon['price_cents'];
                    $extraDays += (int) $addon['extra_days'];
                }
            }

            $lineTotal = (int) $package['price_cents'] + $addonCents;
            $lineDays = (int) $package['delivery_days'] + $extraDays;
            $total += $lineTotal;
            $days = max($days, $lineDays);
            $line = [
                'id' => (string) $item['id'],
                'listing_id' => $listingId,
                'listing_slug' => (string) $listing['slug'],
                'listing_title' => (string) $listing['title'],
                'listing_cover' => (string) ($listing['cover_path'] ?? ''),
                'creator_name' => (string) $listing['creator_name'],
                'package_id' => (int) $package['id'],
                'hours' => (int) $package['hours'],
                'package_price_cents' => (int) $package['price_cents'],
                'delivery_days' => $lineDays,
                'addons' => $addons,
                'total_cents' => $lineTotal,
                'theme' => (string) ($item['theme'] ?? ''),
                'briefing' => (string) ($item['briefing'] ?? ''),
                'desired_date' => (string) ($item['desired_date'] ?? ''),
                'desired_time' => (string) ($item['desired_time'] ?? ''),
                'addon_ids' => $addonIds,
            ];
            $lines[] = $line;
            $kept[] = [
                'id' => $line['id'],
                'listing_id' => $listingId,
                'listing_slug' => $line['listing_slug'],
                'package_id' => $line['package_id'],
                'addon_ids' => $addonIds,
                'theme' => $line['theme'],
                'briefing' => $line['briefing'],
                'desired_date' => $line['desired_date'],
                'desired_time' => $line['desired_time'],
            ];
        }

        Session::set(self::KEY, $kept);

        return ['lines' => $lines, 'total_cents' => $total, 'days' => $days];
    }
}
