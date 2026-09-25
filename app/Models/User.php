<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public const ROLES = ['admin' => 'Administrador', 'creator' => 'Criador', 'company' => 'Empresa'];

    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $adminLevel,
        public readonly string $status,
        public readonly string $displayName,
        public readonly string $slug,
        public readonly ?string $avatarPath,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['uuid'],
            (string) $row['email'],
            (string) $row['role'],
            $row['admin_level'] !== null ? (string) $row['admin_level'] : null,
            (string) $row['status'],
            (string) ($row['display_name'] ?? $row['email']),
            (string) ($row['slug'] ?? ''),
            $row['avatar_path'] ?? null,
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMaster(): bool
    {
        return $this->role === 'admin' && $this->adminLevel === 'master';
    }

    public function isCreator(): bool
    {
        return $this->role === 'creator';
    }

    public function isCompany(): bool
    {
        return $this->role === 'company';
    }

    public function homePath(): string
    {
        return match ($this->role) {
            'admin' => '/admin',
            'creator' => '/painel',
            default => '/empresa',
        };
    }

    public function areaPrefix(): string
    {
        return $this->isCreator() ? '/painel' : '/empresa';
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->displayName))[0] ?: $this->displayName;
    }
}
