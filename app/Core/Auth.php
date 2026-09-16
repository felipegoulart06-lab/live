<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Repositories\UserRepository;

final class Auth
{
    private static ?User $user = null;
    private static bool $resolved = false;

    public static function id(): ?int
    {
        $id = Session::get('user_id');

        return $id ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): ?User
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;
        $id = self::id();
        if (!$id) {
            return null;
        }

        $row = (new UserRepository())->findActive($id);
        self::$user = $row ? User::fromArray($row) : null;

        return self::$user;
    }

    public static function login(User $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user->id);
        self::$user = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        self::$user = null;
        self::$resolved = true;
        Session::forget('user_id');
        Session::regenerate();
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();

        return $user ? $user->hasRole($role) : false;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();

        return $user ? $user->can($permission) : false;
    }
}
