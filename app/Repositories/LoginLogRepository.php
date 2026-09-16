<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Model;

final class LoginLogRepository extends Model
{
    protected string $table = 'login_logs';

    protected bool $timestamps = false;

    protected array $fillable = [
        'user_id',
        'email_attempt',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
        'created_at',
    ];

    public function record(?int $userId, string $email, string $ip, string $ua, bool $success, ?string $reason = null): void
    {
        $this->create([
            'user_id' => $userId,
            'email_attempt' => $email,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'success' => $success ? 1 : 0,
            'failure_reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
