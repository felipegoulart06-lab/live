<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\SettingService;
use Closure;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (string) SettingService::get('maintenance_mode', '0') === '1';
        if (!$enabled) {
            return $next($request);
        }

        $user = Auth::user();
        if ($user && $user->hasAnyRole(['super_admin', 'admin'])) {
            return $next($request);
        }

        $path = $request->path();
        if (in_array($path, ['/entrar', '/sair'], true) || str_starts_with($path, '/admin')) {
            return $next($request);
        }

        return Response::view('pages/errors/maintenance', [
            'title' => 'Em manutenção',
            'authUser' => $user,
            'csrf' => \App\Core\Session::csrfToken(),
        ], 'layouts/main', 503);
    }
}
