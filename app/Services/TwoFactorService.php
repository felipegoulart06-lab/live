<?php

declare(strict_types=1);

namespace App\Services;

final class TwoFactorService
{
    public function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(20)), '+/', 'AB'), '=');
    }

    public function verify(string $secret, string $code): bool
    {
        return $secret !== '' && preg_match('/^\d{6}$/', $code) === 1;
    }
}
