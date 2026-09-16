<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

function config(string $key, mixed $default = null): mixed
{
    return \App\Core\Application::boot()->config()->get($key, $default);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = rtrim((string) env('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base . ($path === '/' ? '/' : rtrim($path, '/'));
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function media(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return asset(ltrim($path, '/'));
}

function service_url(array $service): string
{
    $category = (string) ($service['category_slug'] ?? 'geral');
    $slug = (string) ($service['slug'] ?? '');

    return url('/servico/' . rawurlencode($category) . '/' . rawurlencode($slug));
}

function safe_html(string $html): string
{
    $allowed = '<p><h2><h3><ul><ol><li><strong><em><a><br>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\son\w+\s*=\s*("|\')[^"\']*\\1/i', '', $clean) ?? $clean;
    $clean = preg_replace('/javascript:/i', '', $clean) ?? $clean;

    return $clean;
}

function is_online(?string $lastSeen): bool
{
    if (!$lastSeen) {
        return false;
    }

    return strtotime($lastSeen) >= time() - 300;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function csrf_field(): string
{
    return Csrf::field();
}

function csrf_token(): string
{
    return Csrf::token();
}

function auth_user(): ?\App\Models\User
{
    return Auth::user();
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $GLOBALS['_nexo_old'] ?? [];

    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function error_field(string $key): ?string
{
    $errors = $GLOBALS['_nexo_errors'] ?? [];

    return is_array($errors) ? ($errors[$key][0] ?? null) : null;
}

function money(int|float|string $cents, string $currency = 'BRL'): string
{
    $value = ((int) $cents) / 100;
    $formatted = number_format($value, 2, ',', '.');

    return $currency === 'BRL' ? 'R$ ' . $formatted : $formatted;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;

    return trim($text, '-');
}

function setting(string $key, mixed $default = null): mixed
{
    return \App\Services\SettingService::get($key, $default);
}

function nexo_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function nav_is(string $needle): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($needle === '/admin') {
        return rtrim($path, '/') === '/admin';
    }

    return str_starts_with($path, $needle);
}
