<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class Accounts
{
    /**
     * Creates the user, its profile and the role row (creators/companies) in one transaction.
     * @param array<string, mixed> $data display_name, email, password; companies also company_name, responsible_name
     */
    public static function create(string $role, array $data, ?string $adminLevel = null): int
    {
        return Db::transaction(static function () use ($role, $data, $adminLevel): int {
            $now = now();
            $userId = Db::insert('users', [
                'uuid' => uuid4(),
                'email' => mb_strtolower(trim((string) $data['email'])),
                'password_hash' => $data['password_hash'] ?? password_hash((string) $data['password'], PASSWORD_DEFAULT),
                'role' => $role,
                'admin_level' => $role === 'admin' ? ($adminLevel ?? 'staff') : null,
                'status' => 'active',
                'email_verified_at' => $data['email_verified_at'] ?? null,
                'created_at' => $data['created_at'] ?? $now,
                'updated_at' => $now,
            ]);

            $displayName = trim((string) $data['display_name']);
            Db::insert('profiles', [
                'user_id' => $userId,
                'display_name' => $displayName,
                'slug' => self::uniqueSlug($displayName),
                'avatar_path' => $data['avatar_path'] ?? null,
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'headline' => $data['headline'] ?? null,
                'bio' => $data['bio'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'specialties' => $data['specialties'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($role === 'creator') {
                Db::insert('creators', [
                    'user_id' => $userId,
                    'is_verified' => (int) ($data['is_verified'] ?? 0),
                    'verified_at' => !empty($data['is_verified']) ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            if ($role === 'company') {
                Db::insert('companies', [
                    'user_id' => $userId,
                    'company_name' => trim((string) $data['company_name']),
                    'legal_name' => $data['legal_name'] ?? null,
                    'document' => $data['document'] ?? null,
                    'responsible_name' => trim((string) ($data['responsible_name'] ?? $displayName)),
                    'phone' => $data['phone'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'website' => $data['website'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $userId;
        });
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) Db::value(
            'SELECT 1 FROM users WHERE email = :e' . ($exceptId ? ' AND id <> :id' : ''),
            array_filter(['e' => mb_strtolower(trim($email)), 'id' => $exceptId])
        );
    }

    public static function uniqueSlug(string $name, ?int $exceptUserId = null): string
    {
        $base = slugify($name) ?: 'perfil';
        $slug = $base;
        $i = 2;
        while (Db::value(
            'SELECT 1 FROM profiles WHERE slug = :s' . ($exceptUserId ? ' AND user_id <> :u' : ''),
            array_filter(['s' => $slug, 'u' => $exceptUserId])
        )) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public static function createToken(int $userId, string $type, int $minutes): string
    {
        $plain = bin2hex(random_bytes(32));
        Db::run('DELETE FROM security_tokens WHERE user_id = :u AND type = :t', ['u' => $userId, 't' => $type]);
        Db::insert('security_tokens', [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'type' => $type,
            'expires_at' => date('Y-m-d H:i:s', time() + $minutes * 60),
            'created_at' => now(),
        ]);

        return $plain;
    }

    /** @return array<string, mixed>|null */
    public static function findToken(string $plain, string $type): ?array
    {
        $row = Db::first(
            'SELECT id, user_id, expires_at, used_at FROM security_tokens WHERE token_hash = :h AND type = :t',
            ['h' => hash('sha256', $plain), 't' => $type]
        );
        if (!$row || $row['used_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }
}
