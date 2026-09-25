<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $path = $request->path();
            $isWebhook = str_starts_with($path, '/webhooks/');

            if (!$isWebhook && !Csrf::validate($request->csrfToken())) {
                Logger::security('CSRF inválido', [
                    'ip' => $request->ip(),
                    'path' => $path,
                ]);

                if ($request->wantsJson() || $request->isAjax()) {
                    return Response::json(['message' => 'Sessão expirada. Recarregue a página.'], 419);
                }

                if (in_array($path, ['/login', '/cadastro', '/recuperar-senha'], true)) {
                    Session::flash('error', 'A sessão expirou. Tente entrar de novo.');

                    return Response::redirect(url($path));
                }

                return Response::html('Sessão expirada. Recarregue a página e tente novamente.', 419);
            }
        }

        return $next($request);
    }
}
