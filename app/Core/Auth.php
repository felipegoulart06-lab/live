<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?User $user = null;
    private static bool $resolved = false;

    public static function id(): ?int
    {
        return self::user()?->id;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?User
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = (int) Session::get('user_id', 0);
        if ($id <= 0) {
            return null;
        }

        $row = Db::first(
            "SELECT u.id, u.uuid, u.email, u.role, u.admin_level, u.status, u.last_seen_at,
                    p.display_name, p.slug, p.avatar_path
             FROM users u LEFT JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id AND u.deleted_at IS NULL AND u.status = 'active'",
            ['id' => $id]
        );
        if (!$row) {
            Session::forget('user_id');

            return null;
        }

        if (!$row['last_seen_at'] || strtotime((string) $row['last_seen_at']) < time() - 120) {
            Db::run('UPDATE users SET last_seen_at = :now WHERE id = :id', ['now' => now(), 'id' => $id]);
        }

        return self::$user = User::fromArray($row);
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$user = null;
        self::$resolved = false;
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::regenerate();
        self::$user = null;
        self::$resolved = true;
    }
}
