<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class PageRepository extends Model
{
    protected string $table = 'pages';

    /** @return array<int, array<string, mixed>> */
    public function published(): array
    {
        return $this->query(
            "SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY sort_order ASC, title ASC"
        )->fetchAll();
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $row = $this->query(
            "SELECT id, title, slug, content, meta_title, meta_description
             FROM pages
             WHERE slug = :slug AND status = 'published'
             LIMIT 1",
            ['slug' => $slug]
        )->fetch();

        return $row ?: null;
    }
}
