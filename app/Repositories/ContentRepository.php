<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class ContentRepository extends Model
{
    protected string $table = 'faqs';

    /** @return array<int, array<string, mixed>> */
    public function faqs(int $limit = 8): array
    {
        return $this->query(
            "SELECT id, question, answer FROM faqs WHERE is_active = 1 AND placement IN ('home','both') ORDER BY sort_order ASC LIMIT " . (int) $limit
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonials(int $limit = 6): array
    {
        return $this->query(
            'SELECT id, author_name, author_role, quote, rating, avatar_path
             FROM testimonials
             WHERE is_active = 1
             ORDER BY sort_order ASC
             LIMIT ' . (int) $limit
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(string $placement = 'home_hero'): array
    {
        return $this->query(
            "SELECT id, title, subtitle, cta_label, cta_url, image_path
             FROM banners
             WHERE is_active = 1 AND placement = :placement
               AND (starts_at IS NULL OR starts_at <= :now_start)
               AND (ends_at IS NULL OR ends_at >= :now_end)
             ORDER BY sort_order ASC",
            ['placement' => $placement, 'now_start' => now(), 'now_end' => now()]
        )->fetchAll();
    }
}
