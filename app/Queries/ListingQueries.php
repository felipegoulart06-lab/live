<?php

declare(strict_types=1);

namespace App\Queries;

use App\Core\Db;

final class ListingQueries
{
    /** Columns every listing card needs, resolved in one JOIN (no per-card queries). */
    public const CARD_SELECT = 'l.id, l.uuid, l.slug, l.title, l.short_description, l.cover_path, l.starting_price_cents, l.min_delivery_days,
        l.rating_avg, l.rating_count, l.contracts_count, l.published_at, l.status,
        p.display_name, p.slug AS creator_slug, p.avatar_path, p.city, p.state, cr.is_verified,
        c.name AS category_name, c.slug AS category_slug,
        (SELECT MIN(lp.hours) FROM listing_packages lp WHERE lp.listing_id = l.id) AS min_hours';

    public const PUBLIC_FROM = "FROM listings l
        JOIN users u ON u.id = l.creator_id AND u.status = 'active' AND u.deleted_at IS NULL
        JOIN profiles p ON p.user_id = l.creator_id
        JOIN creators cr ON cr.user_id = l.creator_id
        LEFT JOIN categories c ON c.id = l.category_id";

    public const SORTS = [
        'relevancia' => 'Mais relevantes',
        'novos' => 'Mais novos',
        'menor-preco' => 'Menor preço',
        'maior-preco' => 'Maior preço',
        'avaliacao' => 'Melhor avaliados',
        'contratados' => 'Mais contratados',
    ];

    /**
     * @param array<string, mixed> $filters q, categoria, preco_min, preco_max, horas, prazo, nota, verificado, cidade, ordem
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function search(array $filters, int $page, int $perPage = 20): array
    {
        $where = ["l.status = 'active'", 'l.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(LOWER(l.title) LIKE :q' . Db::ESCAPE . ' OR LOWER(l.short_description) LIKE :q2' . Db::ESCAPE . ' OR LOWER(p.display_name) LIKE :q3' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = $params['q3'] = Db::like($q);
        }
        if (!empty($filters['categoria'])) {
            $where[] = 'c.slug = :cat';
            $params['cat'] = (string) $filters['categoria'];
        }
        if (($min = parse_money($filters['preco_min'] ?? '')) !== null) {
            $where[] = 'l.starting_price_cents >= :pmin';
            $params['pmin'] = $min;
        }
        if (($max = parse_money($filters['preco_max'] ?? '')) !== null && $max > 0) {
            $where[] = 'l.starting_price_cents <= :pmax';
            $params['pmax'] = $max;
        }
        if (!empty($filters['horas']) && (int) $filters['horas'] > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM listing_packages hp WHERE hp.listing_id = l.id AND hp.hours = :hours)';
            $params['hours'] = (int) $filters['horas'];
        }
        if (!empty($filters['prazo']) && (int) $filters['prazo'] > 0) {
            $where[] = 'l.min_delivery_days <= :prazo';
            $params['prazo'] = (int) $filters['prazo'];
        }
        if (!empty($filters['nota']) && (int) $filters['nota'] > 0) {
            $where[] = 'l.rating_avg >= :nota';
            $params['nota'] = (int) $filters['nota'];
        }
        if (!empty($filters['verificado'])) {
            $where[] = 'cr.is_verified = 1';
        }
        if (!empty($filters['cidade'])) {
            $where[] = 'LOWER(p.city) LIKE :city' . Db::ESCAPE;
            $params['city'] = Db::like((string) $filters['cidade']);
        }
        if (!empty($filters['criador_id'])) {
            $where[] = 'l.creator_id = :creator';
            $params['creator'] = (int) $filters['criador_id'];
        }

        $order = match ($filters['ordem'] ?? 'relevancia') {
            'novos' => 'l.published_at DESC, l.id DESC',
            'menor-preco' => 'l.starting_price_cents ASC, l.id DESC',
            'maior-preco' => 'l.starting_price_cents DESC, l.id DESC',
            'avaliacao' => 'l.rating_avg DESC, l.rating_count DESC, l.id DESC',
            'contratados' => 'l.contracts_count DESC, l.id DESC',
            default => 'cr.is_verified DESC, l.rating_avg * l.rating_count DESC, l.contracts_count DESC, l.published_at DESC, l.id DESC',
        };

        return Db::paginate(self::CARD_SELECT, self::PUBLIC_FROM . ' WHERE ' . implode(' AND ', $where), $params, $page, $perPage, $order);
    }

    /** @return array<int, array<string, mixed>> */
    public static function cards(string $order, int $limit, string $extraWhere = '', array $params = []): array
    {
        return Db::all(
            'SELECT ' . self::CARD_SELECT . ' ' . self::PUBLIC_FROM . " WHERE l.status = 'active' AND l.deleted_at IS NULL {$extraWhere} ORDER BY {$order} LIMIT " . max(1, $limit),
            $params
        );
    }

    /** Highlighted listings for a home section, in the admin-defined order. @return array<int, array<string, mixed>> */
    public static function highlighted(string $section, int $limit): array
    {
        return Db::all(
            'SELECT ' . self::CARD_SELECT . ' ' . self::PUBLIC_FROM . "
             JOIN home_highlights h ON h.item_type = 'listing' AND h.item_id = l.id AND h.section = :s
             WHERE l.status = 'active' AND l.deleted_at IS NULL
             ORDER BY h.sort_order, h.id LIMIT " . max(1, $limit),
            ['s' => $section]
        );
    }

    /** @return array<string, mixed>|null */
    public static function publicBySlug(string $slug): ?array
    {
        return Db::first(
            'SELECT l.*, p.display_name, p.slug AS creator_slug, p.avatar_path, p.city, p.state, p.headline, p.bio, p.experience_years, p.specialties,
                    cr.is_verified, cr.rating_avg AS creator_rating_avg, cr.rating_count AS creator_rating_count, cr.contracts_count AS creator_contracts,
                    cr.min_notice_hours, cr.unavailable_from, cr.unavailable_until, cr.unavailable_note,
                    u.created_at AS member_since, u.last_seen_at,
                    c.name AS category_name, c.slug AS category_slug
             ' . self::PUBLIC_FROM . "
             WHERE l.slug = :slug AND l.status = 'active' AND l.deleted_at IS NULL",
            ['slug' => $slug]
        );
    }

    /** Same shape as publicBySlug() but for any status; callers must already have checked ownership. @return array<string, mixed>|null */
    public static function anyById(int $id): ?array
    {
        return Db::first(
            'SELECT l.*, p.display_name, p.slug AS creator_slug, p.avatar_path, p.city, p.state, p.headline, p.bio, p.experience_years, p.specialties,
                    cr.is_verified, cr.rating_avg AS creator_rating_avg, cr.rating_count AS creator_rating_count, cr.contracts_count AS creator_contracts,
                    cr.min_notice_hours, cr.unavailable_from, cr.unavailable_until, cr.unavailable_note,
                    u.created_at AS member_since, u.last_seen_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM listings l
             JOIN users u ON u.id = l.creator_id
             JOIN profiles p ON p.user_id = l.creator_id
             JOIN creators cr ON cr.user_id = l.creator_id
             LEFT JOIN categories c ON c.id = l.category_id
             WHERE l.id = :id',
            ['id' => $id]
        );
    }

    /** Everything the detail page and the preview render, for any status. @return array<string, mixed> */
    public static function details(int $listingId, int $creatorId): array
    {
        return [
            'packages' => Db::all('SELECT * FROM listing_packages WHERE listing_id = :id ORDER BY hours, sort_order, id', ['id' => $listingId]),
            'addons' => Db::all('SELECT * FROM listing_addons WHERE listing_id = :id ORDER BY sort_order, id', ['id' => $listingId]),
            'images' => Db::all('SELECT * FROM listing_images WHERE listing_id = :id ORDER BY is_primary DESC, sort_order, id', ['id' => $listingId]),
            'faqs' => Db::all('SELECT * FROM listing_faqs WHERE listing_id = :id ORDER BY sort_order, id', ['id' => $listingId]),
            'availability' => Db::all('SELECT weekday, start_time, end_time FROM availability WHERE creator_id = :c ORDER BY weekday', ['c' => $creatorId]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function reviews(string $column, int $id, int $limit = 10): array
    {
        $column = $column === 'creator_id' ? 'r.creator_id' : 'r.listing_id';

        return Db::all(
            "SELECT r.rating, r.comment, r.created_at, co.company_name, p.display_name AS author_name, l.title AS listing_title
             FROM reviews r
             JOIN companies co ON co.user_id = r.company_id
             JOIN profiles p ON p.user_id = r.company_id
             JOIN listings l ON l.id = r.listing_id
             WHERE {$column} = :id AND r.status = 'visible'
             ORDER BY r.created_at DESC LIMIT " . max(1, $limit),
            ['id' => $id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function visibleCategories(): array
    {
        return Db::all(
            "SELECT c.id, c.name, c.slug, c.description, c.image_path,
                    (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id AND l.status = 'active' AND l.deleted_at IS NULL) AS listings_count
             FROM categories c WHERE c.status = 'visible' ORDER BY c.sort_order, c.name"
        );
    }
}
