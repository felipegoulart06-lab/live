<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RoleMiddleware;

final class MiddlewareFactory
{
    public static function make(string $spec): MiddlewareInterface
    {
        [$name, $params] = array_pad(explode(':', $spec, 2), 2, null);

        $map = [
            'auth' => AuthMiddleware::class,
            'guest' => GuestMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'admin' => AdminMiddleware::class,
        ];

        $class = $map[$name] ?? $spec;

        if ($params !== null && in_array($class, [RoleMiddleware::class, PermissionMiddleware::class], true)) {
            return new $class($params);
        }

        return new $class();
    }
}
