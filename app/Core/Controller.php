<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Validators\Validator;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], string $layout = 'layouts/public', int $status = 200): Response
    {
        return Response::view($view, $data, $layout, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    protected function back(string $fallback = '/'): Response
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host = parse_url(url('/'), PHP_URL_HOST);
        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $host) {
            return Response::redirect($referer);
        }

        return Response::redirect(url($fallback));
    }

    protected function success(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function error(string $message): void
    {
        Session::flash('error', $message);
    }

    /**
     * Validates or flashes errors + old input. Returns null on success, or the redirect to send back.
     * @param array<string, mixed> $data @param array<string, string> $rules @param array<string, string> $labels
     */
    protected function invalid(array $data, array $rules, array $labels = [], string $fallback = '/'): ?Response
    {
        $validator = new Validator($labels);
        if ($validator->validate($data, $rules)) {
            return null;
        }
        Session::flash('errors', $validator->errors());
        Session::flash('old', array_diff_key($data, array_flip(['password', 'password_confirmation', 'current_password', '_csrf'])));
        Session::flash('error', 'Revise os campos destacados.');

        return $this->back($fallback);
    }

    protected function user(): User
    {
        return Gate::user();
    }

    protected function page(Request $request): int
    {
        return max(1, (int) $request->query('pagina', 1));
    }

    protected function throttle(string $key, int $max, int $window): void
    {
        if (RateLimiter::tooManyAttempts($key, $max, $window)) {
            throw HttpException::tooMany();
        }
    }
}
