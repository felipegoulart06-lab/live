<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    /** @param array<int, string> $roles @param array<int, string> $permissions */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $email,
        public readonly ?string $emailVerifiedAt,
        public readonly string $accountType,
        public readonly string $status,
        public readonly ?string $lastSeenAt,
        public readonly bool $twoFactorEnabled,
        public readonly array $roles = [],
        public readonly array $permissions = [],
        public readonly ?array $profile = null
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            uuid: (string) $row['uuid'],
            email: (string) $row['email'],
            emailVerifiedAt: $row['email_verified_at'] ?? null,
            accountType: (string) $row['account_type'],
            status: (string) $row['status'],
            lastSeenAt: $row['last_seen_at'] ?? null,
            twoFactorEnabled: (bool) ($row['two_factor_enabled'] ?? false),
            roles: $row['_roles'] ?? [],
            permissions: $row['_permissions'] ?? [],
            profile: $row['_profile'] ?? null
        );
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /** @param array<int, string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function can(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }

    public function displayName(): string
    {
        return (string) ($this->profile['display_name'] ?? $this->email);
    }

    public function slug(): ?string
    {
        return $this->profile['slug'] ?? null;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function isSeller(): bool
    {
        return in_array($this->accountType, ['seller', 'both'], true);
    }

    public function isBuyer(): bool
    {
        return in_array($this->accountType, ['buyer', 'both'], true);
    }
}
