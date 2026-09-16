<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class ProfileRepository extends Model
{
    protected string $table = 'profiles';

    protected array $fillable = [
        'user_id',
        'display_name',
        'professional_name',
        'slug',
        'headline',
        'bio',
        'phone',
        'city',
        'state',
        'country',
        'avatar_path',
        'cover_path',
        'experience_years',
        'is_verified',
        'created_at',
        'updated_at',
    ];

    public function slugExists(string $slug): bool
    {
        $row = $this->query(
            'SELECT id FROM profiles WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        )->fetch();

        return (bool) $row;
    }
}
