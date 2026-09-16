<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class UserRepository extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'uuid',
        'email',
        'password',
        'account_type',
        'status',
        'email_verified_at',
        'last_login_at',
        'last_seen_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_secret',
        'created_at',
        'updated_at',
    ];

    public function findActive(int $id): ?array
    {
        $row = $this->query(
            'SELECT id, uuid, email, email_verified_at, account_type, status, last_seen_at, two_factor_enabled
             FROM users
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1',
            ['id' => $id]
        )->fetch();

        if (!$row) {
            return null;
        }

        $row['_roles'] = $this->rolesFor((int) $row['id']);
        $row['_permissions'] = $this->permissionsFor((int) $row['id']);
        $row['_profile'] = $this->profileFor((int) $row['id']);

        return $row;
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->query(
            'SELECT id, uuid, email, password, email_verified_at, account_type, status, last_seen_at, two_factor_enabled, deleted_at
             FROM users
             WHERE email = :email
             LIMIT 1',
            ['email' => $email]
        )->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        $row = $this->query(
            'SELECT id FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        )->fetch();

        return (bool) $row;
    }

    public function attachRole(int $userId, int $roleId): void
    {
        $stmt = $this->db()->prepare(
            'INSERT OR IGNORE INTO user_roles (user_id, role_id, created_at) VALUES (:user_id, :role_id, :created_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
        ]);
    }

    public function roleIdBySlug(string $slug): ?int
    {
        $row = $this->query(
            'SELECT id FROM roles WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        )->fetch();

        return $row ? (int) $row['id'] : null;
    }

    /** @return array<int, string> */
    public function rolesFor(int $userId): array
    {
        $rows = $this->query(
            'SELECT r.slug
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :id',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['slug'], $rows);
    }

    /** @return array<int, string> */
    public function permissionsFor(int $userId): array
    {
        $rows = $this->query(
            'SELECT DISTINCT p.slug
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :id',
            ['id' => $userId]
        )->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['slug'], $rows);
    }

    public function profileFor(int $userId): ?array
    {
        $row = $this->query(
            'SELECT id, user_id, display_name, professional_name, slug, headline, bio, avatar_path, cover_path,
                    city, state, country, is_verified, rating_avg, rating_count, orders_completed
             FROM profiles
             WHERE user_id = :id
             LIMIT 1',
            ['id' => $userId]
        )->fetch();

        return $row ?: null;
    }

    public function touchLastSeen(int $userId): void
    {
        $this->query(
            'UPDATE users SET last_seen_at = :seen_at, updated_at = :updated_at WHERE id = :id',
            ['seen_at' => now(), 'updated_at' => now(), 'id' => $userId]
        );
    }

    public function markLogin(int $userId, string $ip): void
    {
        $this->query(
            'UPDATE users SET last_login_at = :login_at, last_login_ip = :ip, last_seen_at = :seen_at, updated_at = :updated_at WHERE id = :id',
            ['login_at' => now(), 'ip' => $ip, 'seen_at' => now(), 'updated_at' => now(), 'id' => $userId]
        );
    }

    public function findOAuth(string $provider, string $providerUserId): ?array
    {
        $row = $this->query(
            'SELECT user_id FROM oauth_accounts WHERE provider = :provider AND provider_user_id = :pid LIMIT 1',
            ['provider' => $provider, 'pid' => $providerUserId]
        )->fetch();

        if (!$row) {
            return null;
        }

        return $this->findActive((int) $row['user_id']);
    }

    public function linkOAuth(int $userId, string $provider, string $providerUserId, string $email): void
    {
        $stmt = $this->db()->prepare(
            'INSERT OR IGNORE INTO oauth_accounts (user_id, provider, provider_user_id, email, created_at)
             VALUES (:user_id, :provider, :pid, :email, :created_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
            'pid' => $providerUserId,
            'email' => $email,
            'created_at' => now(),
        ]);
    }
}
