<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\Settings;
use Closure;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Settings::get('maintenance_mode', '0') !== '1' || Auth::user()?->isAdmin()) {
            return $next($request);
        }

        $path = $request->path();
        if (in_array($path, ['/login', '/sair'], true)) {
            return $next($request);
        }

        return Response::view('errors/maintenance', ['title' => 'Em manutenção'], 'layouts/public', 503);
    }
}
