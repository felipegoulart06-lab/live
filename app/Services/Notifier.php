<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class Notifier
{
    public static function send(int $userId, string $type, string $title, ?string $body = null, ?string $path = null): void
    {
        Db::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => mb_substr($title, 0, 180),
            'body' => $body !== null ? mb_substr($body, 0, 500) : null,
            'url' => $path,
            'created_at' => now(),
        ]);

        $email = Db::value('SELECT email FROM users WHERE id = :id', ['id' => $userId]);
        if (is_string($email)) {
            MailService::queue($type, $email, ['title' => $title, 'body' => $body, 'url' => $path !== null ? url($path) : null]);
        }
    }

    public static function admins(string $type, string $title, ?string $body = null, ?string $path = null): void
    {
        $ids = Db::all("SELECT id FROM users WHERE role = 'admin' AND status = 'active' AND deleted_at IS NULL");
        foreach ($ids as $row) {
            Db::insert('notifications', [
                'user_id' => (int) $row['id'],
                'type' => $type,
                'title' => mb_substr($title, 0, 180),
                'body' => $body,
                'url' => $path,
                'created_at' => now(),
            ]);
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND read_at IS NULL', ['u' => $userId]);
    }
}
