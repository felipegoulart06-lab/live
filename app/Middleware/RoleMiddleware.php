<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $roles = '')
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $roles = array_filter(explode(',', $this->roles));

        if (!$user || ($roles !== [] && !$user->hasAnyRole($roles))) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Acesso negado.'], 403);
            }

            return Response::view('pages/errors/403', [
                'title' => 'Acesso negado',
                'authUser' => $user,
                'csrf' => \App\Core\Session::csrfToken(),
            ], 'layouts/main', 403);
        }

        return $next($request);
    }
}
