<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Model;

final class CategoryRepository extends Model
{
    protected string $table = 'categories';

    /** @return array<int, array<string, mixed>> */
    public function menuTree(): array
    {
        return Cache::remember('categories.menu', 300, function (): array {
            $categories = $this->query(
                'SELECT id, name, slug, icon, image_path, short_description, sort_order
                 FROM categories
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC'
            )->fetchAll();

            $subs = $this->query(
                'SELECT id, category_id, name, slug, sort_order
                 FROM subcategories
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC'
            )->fetchAll();

            $grouped = [];
            foreach ($subs as $sub) {
                $grouped[(int) $sub['category_id']][] = $sub;
            }

            foreach ($categories as &$category) {
                $category['children'] = $grouped[(int) $category['id']] ?? [];
            }

            return $categories;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function popular(int $limit = 8): array
    {
        return $this->query(
            'SELECT id, name, slug, icon, image_path, short_description
             FROM categories
             WHERE is_active = 1
             ORDER BY is_featured DESC, sort_order ASC
             LIMIT ' . (int) $limit
        )->fetchAll();
    }
}
