<?php

declare(strict_types=1);

namespace App\Queries;

use App\Core\Db;

final class CreatorQueries
{
    public const CARD_SELECT = 'u.id, p.display_name, p.slug, p.avatar_path, p.city, p.state, p.headline,
        cr.is_verified, cr.rating_avg, cr.rating_count, cr.contracts_count, cr.hours_sold,
        (SELECT MIN(l.starting_price_cents) FROM listings l WHERE l.creator_id = u.id AND l.status = \'active\' AND l.deleted_at IS NULL) AS starting_price_cents,
        (SELECT COUNT(*) FROM listings l WHERE l.creator_id = u.id AND l.status = \'active\' AND l.deleted_at IS NULL) AS listings_count';

    public const PUBLIC_FROM = "FROM users u
        JOIN profiles p ON p.user_id = u.id
        JOIN creators cr ON cr.user_id = u.id
        WHERE u.role = 'creator' AND u.status = 'active' AND u.deleted_at IS NULL
          AND EXISTS (SELECT 1 FROM listings la WHERE la.creator_id = u.id AND la.status = 'active' AND la.deleted_at IS NULL)";

    /** @param array<string, mixed> $filters @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int} */
    public static function search(array $filters, int $page): array
    {
        $where = '';
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (LOWER(p.display_name) LIKE :q' . Db::ESCAPE . ' OR LOWER(p.headline) LIKE :q2' . Db::ESCAPE . ' OR LOWER(p.specialties) LIKE :q3' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = $params['q3'] = Db::like($q);
        }
        if (!empty($filters['cidade'])) {
            $where .= ' AND LOWER(p.city) LIKE :city' . Db::ESCAPE;
            $params['city'] = Db::like((string) $filters['cidade']);
        }
        if (!empty($filters['verificado'])) {
            $where .= ' AND cr.is_verified = 1';
        }
        $order = match ($filters['ordem'] ?? '') {
            'avaliacao' => 'cr.rating_avg DESC, cr.rating_count DESC, u.id',
            'contratados' => 'cr.contracts_count DESC, u.id',
            'novos' => 'u.created_at DESC, u.id',
            default => 'cr.is_verified DESC, cr.rating_avg * cr.rating_count DESC, u.id',
        };

        return Db::paginate(self::CARD_SELECT, self::PUBLIC_FROM . $where, $params, $page, 24, $order);
    }

    /** @return array<int, array<string, mixed>> */
    public static function highlighted(string $section, int $limit): array
    {
        $rows = Db::all(
            'SELECT ' . self::CARD_SELECT . ' ' . self::PUBLIC_FROM . "
               AND EXISTS (SELECT 1 FROM home_highlights h WHERE h.section = :s AND h.item_type = 'creator' AND h.item_id = u.id)
             ORDER BY (SELECT MIN(h2.sort_order) FROM home_highlights h2 WHERE h2.section = :s2 AND h2.item_type = 'creator' AND h2.item_id = u.id), u.id
             LIMIT " . max(1, $limit),
            ['s' => $section, 's2' => $section]
        );
        if ($rows !== []) {
            return $rows;
        }

        return Db::all('SELECT ' . self::CARD_SELECT . ' ' . self::PUBLIC_FROM . ' ORDER BY cr.is_verified DESC, cr.rating_avg DESC, u.id LIMIT ' . max(1, $limit));
    }

    /** @return array<string, mixed>|null */
    public static function publicBySlug(string $slug): ?array
    {
        return Db::first(
            "SELECT u.id, u.created_at AS member_since, u.last_seen_at, p.display_name, p.slug, p.avatar_path, p.city, p.state, p.headline, p.bio,
                    p.experience_years, p.specialties, cr.is_verified, cr.rating_avg, cr.rating_count, cr.contracts_count, cr.hours_sold,
                    cr.min_notice_hours, cr.unavailable_from, cr.unavailable_until, cr.unavailable_note
             FROM users u JOIN profiles p ON p.user_id = u.id JOIN creators cr ON cr.user_id = u.id
             WHERE p.slug = :slug AND u.role = 'creator' AND u.status = 'active' AND u.deleted_at IS NULL",
            ['slug' => $slug]
        );
    }
}
