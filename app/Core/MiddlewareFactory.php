<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;

final class MiddlewareFactory
{
    public static function make(string $spec): MiddlewareInterface
    {
        [$name, $params] = array_pad(explode(':', $spec, 2), 2, null);

        return match ($name) {
            'auth' => new AuthMiddleware(),
            'guest' => new GuestMiddleware(),
            'role' => new RoleMiddleware((string) $params),
            default => new $spec(),
        };
    }
}
