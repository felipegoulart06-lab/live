<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->wantsJson() || $request->isAjax()) {
                return Response::json(['message' => 'Não autenticado.'], 401);
            }

            return Response::redirect(url('/entrar'));
        }

        return $next($request);
    }
}
