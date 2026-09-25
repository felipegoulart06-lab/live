<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

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
    if (\App\Core\Paths::serverless() || $base === '') {
        $host = (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '' && preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
            $scheme = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || \App\Core\Paths::serverless() ? 'https' : 'http';
            $base = $scheme . '://' . $host;
        }
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    $path = '/' . ltrim($path, '/');
    [$pathOnly, $query] = array_pad(explode('?', $path, 2), 2, null);
    $pathOnly = $pathOnly === '/' ? '/' : rtrim($pathOnly, '/');

    return $base . $pathOnly . ($query !== null && $query !== '' ? '?' . $query : '');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . substr((string) filemtime($file), -6) : '';

    return url('/assets/' . ltrim($path, '/')) . $version;
}

/** Seeded demo images live in the repo (`images/...`); uploads resolve through the storage disk. */
function media(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    if (str_starts_with($path, 'images/')) {
        return url('/assets/' . $path);
    }

    return \App\Core\Storage::url($path);
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

function money(int|float|string|null $cents): string
{
    return 'R$ ' . number_format(((int) $cents) / 100, 2, ',', '.');
}

/** "1.250,50", "1250.5" or "1250" → cents. Returns null when the text is not a price. */
function parse_money(mixed $value): ?int
{
    $text = trim(str_replace(['R$', ' ', "\u{00A0}"], '', (string) $value));
    if ($text === '') {
        return null;
    }
    if (str_contains($text, ',')) {
        $text = str_replace(['.', ','], ['', '.'], $text);
    }
    if (!is_numeric($text) || (float) $text < 0 || (float) $text > 10_000_000) {
        return null;
    }

    return (int) round(((float) $text) * 100);
}

function money_input(int|string|null $cents): string
{
    return $cents === null || $cents === '' ? '' : number_format(((int) $cents) / 100, 2, ',', '.');
}

function slugify(string $text): string
{
    // iconv transliteration depends on the OS locale (Windows turns "ç" into "?"), so map accents explicitly.
    $text = strtr(mb_strtolower($text), [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;

    return trim(substr($text, 0, 80), '-');
}

function setting(string $key, mixed $default = null): mixed
{
    return \App\Services\Settings::get($key, $default);
}

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function brand_name(): string
{
    return (string) setting('platform_name', 'CinquentaConto');
}

function short_name(?string $name): string
{
    $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];
    if (count($parts) < 2) {
        return $parts[0] ?? '';
    }

    return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1)) . '.';
}

function initials(?string $name): string
{
    $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

    return mb_strtoupper($first . $last);
}

/** Public texts must not carry phone numbers, e-mails or messenger links. */
function redact_contact(string $text): string
{
    $text = preg_replace('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', '[contato oculto]', $text) ?? $text;
    $text = preg_replace('/(?:\+?\d{1,3}[\s.-]*)?(?:\(?\d{2}\)?[\s.-]*)?\d{4,5}[\s.-]?\d{4}/', '[contato oculto]', $text) ?? $text;
    $text = preg_replace('#(?:https?://)?(?:t\.me|wa\.me|api\.whatsapp\.com)/\S+#i', '[contato oculto]', $text) ?? $text;

    return trim($text);
}

function nl2p(?string $text): string
{
    $blocks = preg_split("/\R{2,}/", trim((string) $text)) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        if (trim($block) !== '') {
            $html .= '<p>' . nl2br(e(trim($block))) . '</p>';
        }
    }

    return $html;
}

function listing_url(string $slug): string
{
    return url('/anuncios/' . rawurlencode($slug));
}

function creator_url(string $slug): string
{
    return url('/criadores/' . rawurlencode($slug));
}

function category_url(string $slug): string
{
    return url('/categorias/' . rawurlencode($slug));
}

/** Current URL with some query keys replaced (null removes the key). @param array<string, mixed> $changes */
function query_url(array $changes): string
{
    $query = $_GET;
    unset($query['__path']);
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (isset($_GET['__path'])) {
        $path = '/' . ltrim((string) $_GET['__path'], '/');
    }

    return url($path . ($query !== [] ? '?' . http_build_query($query) : ''));
}

function current_path(): string
{
    if (isset($_GET['__path'])) {
        return '/' . trim((string) $_GET['__path'], '/');
    }

    return rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
}

function nav_is(string $prefix, bool $exact = false): bool
{
    $path = current_path();

    return $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, $prefix . '/'));
}

function nav_href_active(string $href): bool
{
    $parts = parse_url($href) ?: [];
    $path = (string) ($parts['path'] ?? $href);
    $query = [];
    parse_str((string) ($parts['query'] ?? ''), $query);
    if (current_path() !== $path) {
        return false;
    }
    foreach ($query as $key => $value) {
        if ((string) ($_GET[$key] ?? '') !== (string) $value) {
            return false;
        }
    }
    if ($query === [] && isset($_GET['status']) && (string) $_GET['status'] !== '' && str_contains($path, '/anuncios')) {
        return false;
    }

    return true;
}

function is_online(?string $lastSeen): bool
{
    return $lastSeen !== null && $lastSeen !== '' && strtotime($lastSeen) >= time() - 300;
}

function fmt_date(?string $value): string
{
    return $value ? date('d/m/Y', strtotime($value)) : '—';
}

function fmt_datetime(?string $value): string
{
    return $value ? date('d/m/Y H:i', strtotime($value)) : '—';
}

function time_ago(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $diff = time() - strtotime($value);

    return match (true) {
        $diff < 60 => 'agora',
        $diff < 3600 => 'há ' . intdiv($diff, 60) . ' min',
        $diff < 86400 => 'há ' . intdiv($diff, 3600) . ' h',
        $diff < 86400 * 30 => 'há ' . intdiv($diff, 86400) . ' dias',
        default => fmt_date($value),
    };
}

function weekday_name(int $day): string
{
    return ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$day] ?? '';
}

/** @return array<string, array{0: string, 1: string}> status => [label, tone] */
function status_map(string $domain): array
{
    return match ($domain) {
        'listing' => [
            'draft' => ['Rascunho', 'neutral'],
            'pending' => ['Pendente', 'warn'],
            'active' => ['Ativo', 'ok'],
            'paused' => ['Pausado', 'neutral'],
            'rejected' => ['Reprovado', 'err'],
        ],
        'request' => [
            'pending' => ['Aguardando resposta', 'warn'],
            'accepted' => ['Aceita', 'ok'],
            'declined' => ['Recusada', 'err'],
            'expired' => ['Expirada', 'neutral'],
            'cancelled' => ['Cancelada', 'neutral'],
        ],
        'contract' => [
            'awaiting_payment' => ['Aguardando pagamento', 'warn'],
            'confirmed' => ['Confirmado', 'info'],
            'in_progress' => ['Em andamento', 'info'],
            'completed' => ['Concluído', 'ok'],
            'cancelled' => ['Cancelado', 'err'],
        ],
        'payment' => [
            'pending' => ['Pendente', 'warn'],
            'paid' => ['Pago', 'info'],
            'awaiting_payout' => ['Aguardando repasse', 'warn'],
            'paid_out' => ['Repassado', 'ok'],
            'cancelled' => ['Cancelado', 'neutral'],
            'refunded' => ['Reembolsado', 'err'],
        ],
        'user' => [
            'active' => ['Ativo', 'ok'],
            'blocked' => ['Bloqueado', 'err'],
        ],
        'report' => [
            'open' => ['Aberta', 'warn'],
            'reviewing' => ['Em análise', 'info'],
            'resolved' => ['Resolvida', 'ok'],
            'archived' => ['Arquivada', 'neutral'],
        ],
        'review' => [
            'visible' => ['Visível', 'ok'],
            'hidden' => ['Oculta', 'neutral'],
        ],
        'visibility' => [
            'visible' => ['Visível', 'ok'],
            'hidden' => ['Oculta', 'neutral'],
            'published' => ['Publicada', 'ok'],
            'draft' => ['Rascunho', 'neutral'],
        ],
        default => [],
    };
}

function status_label(string $domain, ?string $status): string
{
    return status_map($domain)[(string) $status][0] ?? (string) $status;
}

function status_badge(string $domain, ?string $status): string
{
    [$label, $tone] = status_map($domain)[(string) $status] ?? [(string) $status, 'neutral'];

    return '<span class="status status--' . e($tone) . '">' . e($label) . '</span>';
}

function view(string $component, array $data = []): string
{
    return \App\Core\View::component($component, $data);
}

function client_ip(): string
{
    $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwarded !== '' && \App\Core\Paths::serverless()) {
        return trim(explode(',', $forwarded)[0]);
    }

    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function rating_stars(float $rating): string
{
    $full = (int) round($rating);

    return str_repeat('★', $full) . str_repeat('☆', max(0, 5 - $full));
}
