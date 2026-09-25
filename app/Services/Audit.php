<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Db;

/** Append-only: the app exposes no route that updates or deletes these rows. */
final class Audit
{
    /** @param array<string, mixed> $meta */
    public static function admin(string $action, string $objectType, ?int $objectId, string $description, array $meta = []): void
    {
        Db::insert('admin_actions', [
            'admin_id' => Auth::id(),
            'action' => $action,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'description' => mb_substr($description, 0, 500),
            'ip' => client_ip(),
            'meta' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }

    public static function activity(?int $userId, string $action, string $description, ?string $objectType = null, ?int $objectId = null): void
    {
        Db::insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'description' => mb_substr($description, 0, 500),
            'ip' => client_ip(),
            'created_at' => now(),
        ]);
    }
}
