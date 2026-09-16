<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        return Session::csrfToken();
    }

    public static function field(): string
    {
        $token = e(self::token());

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function validate(?string $token): bool
    {
        $session = Session::csrfToken();

        return is_string($token) && $token !== '' && hash_equals($session, $token);
    }
}
