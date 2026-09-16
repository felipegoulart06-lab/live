<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class ProfilePublicRepository extends Model
{
    protected string $table = 'profiles';

    /** @return array<int, array<string, mixed>> */
    public function highlightedSellers(string $scope, int $limit = 8): array
    {
        $order = match ($scope) {
            'new' => 'u.created_at DESC',
            'top_rated' => 'p.rating_avg DESC, p.orders_completed DESC',
            'verified' => 'p.is_verified DESC, p.rating_avg DESC',
            default => 'u.created_at DESC',
        };

        $where = $scope === 'verified' ? 'AND p.is_verified = 1' : '';

        $sql = "SELECT p.display_name, p.professional_name, p.slug, p.headline, p.avatar_path, p.cover_path,
                       p.city, p.state, p.rating_avg, p.rating_count, p.orders_completed, p.is_verified,
                       u.last_seen_at
                FROM profiles p
                INNER JOIN users u ON u.id = p.user_id
                WHERE u.status = 'active' AND u.deleted_at IS NULL {$where}
                  AND EXISTS (
                      SELECT 1 FROM user_roles ur
                      INNER JOIN roles r ON r.id = ur.role_id AND r.slug = 'freelancer'
                      WHERE ur.user_id = u.id
                  )
                ORDER BY {$order}
                LIMIT " . (int) $limit;

        return $this->query($sql)->fetchAll();
    }
}
