<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class TokenRepository extends Model
{
    protected string $table = 'security_tokens';

    protected bool $timestamps = false;

    protected array $fillable = [
        'user_id',
        'token_hash',
        'type',
        'expires_at',
        'used_at',
        'created_at',
    ];

    public function createToken(int $userId, string $type, int $minutes = 60): string
    {
        $plain = bin2hex(random_bytes(32));
        $this->create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'type' => $type,
            'expires_at' => date('Y-m-d H:i:s', time() + ($minutes * 60)),
            'created_at' => now(),
        ]);

        return $plain;
    }

    public function consume(string $plain, string $type): ?array
    {
        $hash = hash('sha256', $plain);
        $row = $this->query(
            'SELECT id, user_id, expires_at, used_at
             FROM security_tokens
             WHERE token_hash = :hash AND type = :type
             LIMIT 1',
            ['hash' => $hash, 'type' => $type]
        )->fetch();

        if (!$row || $row['used_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        $this->query(
            'UPDATE security_tokens SET used_at = :now WHERE id = :id',
            ['now' => now(), 'id' => $row['id']]
        );

        return $row;
    }
}
