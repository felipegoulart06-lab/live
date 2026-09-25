<?php

declare(strict_types=1);

namespace App\Core;

use SessionHandlerInterface;

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(private readonly int $lifetime)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $row = Db::first('SELECT payload, last_activity FROM sessions WHERE id = :id', ['id' => $id]);
        if (!$row || (int) $row['last_activity'] < time() - $this->lifetime) {
            return '';
        }

        $raw = (string) $row['payload'];
        $decoded = base64_decode($raw, true);

        return $decoded !== false ? $decoded : $raw;
    }

    public function write(string $id, string $data): bool
    {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        Db::run(
            'INSERT INTO sessions (id, user_id, payload, last_activity) VALUES (:id, :user_id, :payload, :last_activity)
             ON CONFLICT (id) DO UPDATE SET user_id = excluded.user_id, payload = excluded.payload, last_activity = excluded.last_activity',
            ['id' => $id, 'user_id' => $userId, 'payload' => base64_encode($data), 'last_activity' => time()]
        );

        return true;
    }

    public function destroy(string $id): bool
    {
        Db::run('DELETE FROM sessions WHERE id = :id', ['id' => $id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return Db::run('DELETE FROM sessions WHERE last_activity < :limit', ['limit' => time() - $max_lifetime])->rowCount();
    }
}
