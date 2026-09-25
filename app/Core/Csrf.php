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
        if (!is_string($token) || $token === '') {
            return false;
        }

        $session = Session::csrfToken();
        if (hash_equals($session, $token)) {
            return true;
        }

        $cookie = (string) ($_COOKIE['cc_csrf'] ?? '');

        return $cookie !== '' && hash_equals($cookie, $token);
    }
}
