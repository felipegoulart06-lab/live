<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

/** `role:admin`, `role:creator`, `role:company` or `role:admin.master`. Runs after `auth`. */
final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $spec)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        [$role, $level] = array_pad(explode('.', $this->spec, 2), 2, null);

        $allowed = $user !== null
            && $user->role === $role
            && ($level === null || $user->adminLevel === $level);

        if (!$allowed) {
            Logger::security('Acesso negado por papel', [
                'user_id' => $user?->id,
                'role' => $user?->role,
                'required' => $this->spec,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            throw HttpException::forbidden();
        }

        return $next($request);
    }
}
