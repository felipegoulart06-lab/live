<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class ProjectRepository extends Model
{
    protected string $table = 'projects';

    /** @return array<int, array<string, mixed>> */
    public function openRecent(int $limit = 6): array
    {
        return $this->query(
            "SELECT p.id, p.title, p.slug, p.budget_min_cents, p.budget_max_cents, p.deadline_days,
                    p.proposals_count, p.published_at, c.name AS category_name
             FROM projects p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'open'
             ORDER BY p.published_at DESC
             LIMIT " . (int) $limit
        )->fetchAll();
    }
}
