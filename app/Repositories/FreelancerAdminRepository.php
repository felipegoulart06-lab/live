<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class FreelancerAdminRepository extends Model
{
    protected string $table = 'users';

    /** @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function paginate(string $q, string $status, string $verified, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [
            'u.deleted_at IS NULL',
            "EXISTS (
                SELECT 1 FROM user_roles ur
                INNER JOIN roles r ON r.id = ur.role_id AND r.slug = 'freelancer'
                WHERE ur.user_id = u.id
            )",
        ];
        $params = [];

        if ($q !== '') {
            $where[] = '(p.display_name LIKE :q_name OR p.professional_name LIKE :q_pro OR p.slug LIKE :q_slug OR u.email LIKE :q_email)';
            $term = '%' . $q . '%';
            $params['q_name'] = $term;
            $params['q_pro'] = $term;
            $params['q_slug'] = $term;
            $params['q_email'] = $term;
        }

        if (in_array($status, ['pending', 'active', 'suspended', 'banned'], true)) {
            $where[] = 'u.status = :status';
            $params['status'] = $status;
        }

        if ($verified === '1') {
            $where[] = 'p.is_verified = 1';
        } elseif ($verified === '0') {
            $where[] = 'p.is_verified = 0';
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) ($this->query(
            "SELECT COUNT(*) AS total
             FROM users u
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}",
            $params
        )->fetch()['total'] ?? 0);

        $rows = $this->query(
            "SELECT u.id, u.email, u.status, u.account_type, u.created_at, u.last_seen_at,
                    p.display_name, p.professional_name, p.slug, p.city, p.state, p.is_verified,
                    p.orders_completed, p.rating_avg, p.avatar_path,
                    (SELECT COUNT(*) FROM services s WHERE s.user_id = u.id) AS services_count
             FROM users u
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}
             ORDER BY u.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function findForAdmin(int $id): ?array
    {
        $row = $this->query(
            "SELECT u.id, u.email, u.status, u.account_type, u.email_verified_at, u.created_at, u.last_seen_at,
                    p.id AS profile_id, p.display_name, p.professional_name, p.slug, p.headline, p.bio, p.phone,
                    p.city, p.state, p.country, p.avatar_path, p.cover_path, p.experience_years,
                    p.is_verified, p.response_rate, p.avg_response_minutes, p.orders_completed, p.rating_avg, p.rating_count
             FROM users u
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1",
            ['id' => $id]
        )->fetch();

        if (!$row) {
            return null;
        }

        $row['is_freelancer'] = (bool) $this->query(
            "SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id AND r.slug = 'freelancer' WHERE ur.user_id = :id LIMIT 1",
            ['id' => $id]
        )->fetch();

        return $row;
    }

    /** @return array<int, string> */
    public function skillNames(int $userId): array
    {
        $rows = $this->query(
            'SELECT s.name FROM user_skills us INNER JOIN skills s ON s.id = us.skill_id WHERE us.user_id = :id ORDER BY s.name',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $r): string => (string) $r['name'], $rows);
    }

    /** @return array<int, int> */
    public function badgeIds(int $userId): array
    {
        $rows = $this->query(
            'SELECT badge_id FROM user_badges WHERE user_id = :id',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $r): int => (int) $r['badge_id'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public function allBadges(): array
    {
        return $this->query('SELECT id, name, slug FROM badges ORDER BY name')->fetchAll();
    }

    public function commissionPercent(int $userId): ?string
    {
        $row = $this->query(
            "SELECT percent FROM commission_rules
             WHERE scope = 'user' AND user_id = :id AND is_active = 1
             ORDER BY id DESC LIMIT 1",
            ['id' => $userId]
        )->fetch();

        return $row ? (string) $row['percent'] : null;
    }

    public function emailTaken(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        return (bool) $this->query($sql, $params)->fetch();
    }

    public function slugTaken(string $slug, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT id FROM profiles WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptUserId) {
            $sql .= ' AND user_id <> :id';
            $params['id'] = $exceptUserId;
        }
        $sql .= ' LIMIT 1';

        return (bool) $this->query($sql, $params)->fetch();
    }
}
