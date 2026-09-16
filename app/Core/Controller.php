<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/main', int $status = 200): Response
    {
        $data['authUser'] = Auth::user();
        $data['csrf'] = Session::csrfToken();
        $data['settings'] = $data['settings'] ?? [];

        return Response::view($view, $data, $layout, $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');

        return Response::redirect($referer);
    }

    protected function withErrors(array $errors, array $old = []): void
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
    }

    protected function withSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function withError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function requirePermission(string $permission): void
    {
        $user = $this->requireUser();
        if (!$user->can($permission)) {
            throw new \RuntimeException('Sem permissão.');
        }
    }

    protected function user(): ?User
    {
        return Auth::user();
    }

    protected function requireUser(): User
    {
        $user = Auth::user();
        if (!$user) {
            throw new \RuntimeException('Usuário não autenticado.');
        }

        return $user;
    }
}
