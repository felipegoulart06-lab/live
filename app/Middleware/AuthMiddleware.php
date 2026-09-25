<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->wantsJson() || $request->isAjax()) {
                return Response::json(['message' => 'Entre na sua conta para continuar.'], 401);
            }
            if ($request->method() === 'GET') {
                Session::set('intended', $request->path());
            }
            Session::flash('error', 'Entre na sua conta para continuar.');

            return Response::redirect(url('/login'));
        }

        return $next($request);
    }
}
