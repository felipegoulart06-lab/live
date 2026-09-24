<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class ServiceRepository extends Model
{
    protected string $table = 'services';

    /** @return array<int, array<string, mixed>> */
    public function homeList(string $scope, int $limit = 8): array
    {
        $order = match ($scope) {
            'featured' => 's.is_featured DESC, s.published_at DESC',
            'best_selling' => 's.orders_count DESC, s.rating_avg DESC',
            'recent' => 's.published_at DESC',
            'today' => 's.published_at DESC',
            'fast' => 's.min_delivery_days ASC, s.published_at DESC',
            'offers' => 's.has_discount DESC, s.published_at DESC',
            default => 's.rating_avg DESC, s.orders_count DESC',
        };

        $whereToday = $scope === 'today'
            ? " AND date(s.published_at) = date('now', 'localtime')"
            : '';

        $sql = "SELECT s.id, s.title, s.slug, s.short_description, s.starting_price_cents, s.min_delivery_days,
                       s.rating_avg, s.rating_count, s.orders_count, s.cover_path, s.is_featured,
                       c.slug AS category_slug, c.name AS category_name,
                       p.display_name, p.slug AS freelancer_slug, p.avatar_path, p.is_verified
                FROM services s
                INNER JOIN categories c ON c.id = s.category_id
                INNER JOIN users u ON u.id = s.user_id
                INNER JOIN profiles p ON p.user_id = u.id
                WHERE s.status = 'published' AND u.status = 'active' AND u.deleted_at IS NULL{$whereToday}
                ORDER BY {$order}
                LIMIT " . (int) $limit;

        return $this->query($sql)->fetchAll();
    }

    public function findPublished(string $categorySlug, string $slug): ?array
    {
        $row = $this->query(
            "SELECT s.id, s.user_id, s.title, s.slug, s.short_description, s.description, s.cover_path, s.video_url,
                    s.starting_price_cents, s.min_delivery_days, s.orders_count, s.views_count, s.favorites_count,
                    s.rating_avg, s.rating_count, s.published_at, s.is_featured, s.has_discount,
                    c.id AS category_id, c.name AS category_name, c.slug AS category_slug,
                    sc.name AS subcategory_name, sc.slug AS subcategory_slug,
                    p.display_name, p.professional_name, p.slug AS freelancer_slug, p.headline, p.bio,
                    p.city, p.state, p.country, p.avatar_path, p.experience_years, p.response_rate,
                    p.avg_response_minutes, p.orders_completed, p.rating_avg AS seller_rating_avg,
                    p.rating_count AS seller_rating_count, p.is_verified,
                    u.last_seen_at, u.created_at AS member_since
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             LEFT JOIN subcategories sc ON sc.id = s.subcategory_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE s.slug = :slug AND c.slug = :category AND s.status = 'published'
               AND u.status = 'active' AND u.deleted_at IS NULL
             LIMIT 1",
            ['slug' => $slug, 'category' => $categorySlug]
        )->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function packages(int $serviceId): array
    {
        $rows = $this->query(
            "SELECT id, tier, name, description, price_cents, delivery_days, revisions, quantity, benefits
             FROM service_packages
             WHERE service_id = :id AND is_active = 1
             ORDER BY quantity ASC, price_cents ASC",
            ['id' => $serviceId]
        )->fetchAll();

        foreach ($rows as &$row) {
            $benefits = $row['benefits'] ?? '[]';
            $decoded = is_string($benefits) ? json_decode($benefits, true) : $benefits;
            $row['benefits'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function extras(int $serviceId): array
    {
        return $this->query(
            'SELECT id, name, description, price_cents, extra_days
             FROM service_extras
             WHERE service_id = :id AND is_active = 1
             ORDER BY id ASC',
            ['id' => $serviceId]
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function images(int $serviceId, ?string $coverPath): array
    {
        $rows = $this->query(
            'SELECT path, alt_text FROM service_images WHERE service_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $serviceId]
        )->fetchAll();

        $gallery = [];
        if ($coverPath) {
            $gallery[] = ['path' => $coverPath, 'alt_text' => ''];
        }
        foreach ($rows as $row) {
            if ($coverPath && $row['path'] === $coverPath) {
                continue;
            }
            $gallery[] = $row;
        }

        return $gallery;
    }

    /** @return array<int, array<string, mixed>> */
    public function faqs(int $serviceId): array
    {
        return $this->query(
            'SELECT question, answer FROM service_faqs WHERE service_id = :id ORDER BY sort_order ASC',
            ['id' => $serviceId]
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function reviews(int $serviceId): array
    {
        return $this->query(
            'SELECT author_name, company_name, rating, body, created_at
             FROM service_reviews
             WHERE service_id = :id
             ORDER BY created_at DESC, id DESC',
            ['id' => $serviceId]
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function otherBySeller(int $userId, int $exceptId, int $limit = 4): array
    {
        return $this->query(
            "SELECT s.id, s.title, s.slug, s.short_description, s.starting_price_cents, s.min_delivery_days,
                    s.rating_avg, s.rating_count, s.orders_count, s.cover_path,
                    c.slug AS category_slug, c.name AS category_name,
                    p.display_name, p.slug AS freelancer_slug, p.avatar_path, p.is_verified
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             INNER JOIN profiles p ON p.user_id = s.user_id
             WHERE s.user_id = :user_id AND s.id <> :except_id AND s.status = 'published'
             ORDER BY s.orders_count DESC
             LIMIT " . (int) $limit,
            ['user_id' => $userId, 'except_id' => $exceptId]
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function sellerLanguages(int $userId): array
    {
        return $this->query(
            'SELECT l.name, ul.level
             FROM user_languages ul
             INNER JOIN languages l ON l.id = ul.language_id
             WHERE ul.user_id = :id
             ORDER BY l.name',
            ['id' => $userId]
        )->fetchAll();
    }

    /** @return array<int, string> */
    public function sellerSkills(int $userId): array
    {
        $rows = $this->query(
            'SELECT s.name
             FROM user_skills us
             INNER JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = :id
             ORDER BY s.name',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['name'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public function sellerBadges(int $userId): array
    {
        return $this->query(
            'SELECT b.name, b.slug
             FROM user_badges ub
             INNER JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = :id
             ORDER BY ub.awarded_at DESC',
            ['id' => $userId]
        )->fetchAll();
    }

    public function incrementViews(int $serviceId): void
    {
        $this->query(
            'UPDATE services SET views_count = views_count + 1, updated_at = :updated_at WHERE id = :id',
            ['updated_at' => now(), 'id' => $serviceId]
        );
    }

    public function isFavorited(int $userId, int $serviceId): bool
    {
        $row = $this->query(
            "SELECT id FROM favorites
             WHERE user_id = :user_id AND favoritable_type = 'service' AND favoritable_id = :id
             LIMIT 1",
            ['user_id' => $userId, 'id' => $serviceId]
        )->fetch();

        return (bool) $row;
    }

    public function toggleFavorite(int $userId, int $serviceId): bool
    {
        if ($this->isFavorited($userId, $serviceId)) {
            $this->query(
                "DELETE FROM favorites
                 WHERE user_id = :user_id AND favoritable_type = 'service' AND favoritable_id = :id",
                ['user_id' => $userId, 'id' => $serviceId]
            );
            $this->query(
                'UPDATE services SET favorites_count = CASE WHEN favorites_count > 0 THEN favorites_count - 1 ELSE 0 END WHERE id = :id',
                ['id' => $serviceId]
            );

            return false;
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO favorites (user_id, favoritable_type, favoritable_id, created_at)
             VALUES (:user_id, 'service', :id, :created_at)"
        );
        $stmt->execute(['user_id' => $userId, 'id' => $serviceId, 'created_at' => now()]);
        $this->query('UPDATE services SET favorites_count = favorites_count + 1 WHERE id = :id', ['id' => $serviceId]);

        return true;
    }

    /** @param array<string, mixed> $service */
    public function saveHireIntent(int $buyerId, array $service, ?array $package, string $theme, string $company, string $notes, array $extraIds): string
    {
        $hours = $package ? package_hours($package) : 2;
        $total = (int) ($package['price_cents'] ?? $service['starting_price_cents'] ?? 0);
        $code = 'HR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $stmt = $this->db()->prepare(
            'INSERT INTO hire_intents (public_code, buyer_id, seller_id, service_id, package_id, hours, theme, company_name, notes, extras_json, total_cents, status, created_at)
             VALUES (:public_code, :buyer_id, :seller_id, :service_id, :package_id, :hours, :theme, :company_name, :notes, :extras_json, :total_cents, :status, :created_at)'
        );
        $stmt->execute([
            'public_code' => $code,
            'buyer_id' => $buyerId,
            'seller_id' => (int) $service['user_id'],
            'service_id' => (int) $service['id'],
            'package_id' => $package ? (int) $package['id'] : null,
            'hours' => $hours,
            'theme' => $theme,
            'company_name' => $company !== '' ? $company : null,
            'notes' => $notes !== '' ? $notes : null,
            'extras_json' => json_encode(array_values($extraIds), JSON_UNESCAPED_UNICODE),
            'total_cents' => $total,
            'status' => 'received',
            'created_at' => now(),
        ]);

        return $code;
    }
}
