<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

final class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['super_admin', 'admin', 'moderator'])) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Acesso negado.'], 403);
            }

            return Response::view('pages/errors/403', [
                'title' => 'Acesso negado',
                'authUser' => $user,
                'csrf' => Session::csrfToken(),
            ], 'layouts/main', 403);
        }

        return $next($request);
    }
}
