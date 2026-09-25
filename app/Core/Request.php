<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, mixed> $query @param array<string, mixed> $body @param array<string, mixed> $server @param array<string, mixed> $files @param array<string, mixed> $cookies */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $files,
        private readonly array $cookies,
        private array $params = []
    ) {
    }

    public static function capture(): self
    {
        $query = $_GET;
        $uri = self::resolvePath($query);

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $uri, $query, $body, $_SERVER, $_FILES, $_COOKIE);
    }

    /** @param array<string, mixed> $query */
    private static function resolvePath(array &$query): string
    {
        $rewrite = $query['__path'] ?? null;
        if (is_array($rewrite)) {
            $rewrite = implode('/', array_map('strval', $rewrite));
        }
        if (is_string($rewrite) && $rewrite !== '') {
            unset($query['__path']);
            $uri = '/' . ltrim(rawurldecode(str_replace('%2F', '/', $rewrite)), '/');
        } else {
            $uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
            $isScript = (bool) preg_match('#/(?:api/)?index\.php$#', $uri);
            if ($isScript) {
                $pathInfo = (string) ($_SERVER['PATH_INFO'] ?? $_SERVER['ORIG_PATH_INFO'] ?? '');
                $uri = $pathInfo !== '' ? $pathInfo : '/';
            }
        }

        $uri = str_replace('\\', '/', $uri);
        foreach (['/api/index.php', '/index.php', '/api'] as $prefix) {
            if ($uri === $prefix) {
                $uri = '/';
                break;
            }
            if (str_starts_with($uri, $prefix . '/')) {
                $uri = substr($uri, strlen($prefix)) ?: '/';
                break;
            }
        }

        // Only a real script path marks a subdirectory install; the Vercel PHP runtime reports the
        // requested path as SCRIPT_NAME, and stripping its dirname cut "/a/b/c" down to "/c".
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = str_ends_with($scriptName, '.php') ? dirname($scriptName) : '/';
        if (!in_array($scriptDir, ['/', '.', '\\', '/api', '/public'], true)) {
            if ($uri === $scriptDir) {
                $uri = '/';
            } elseif (str_starts_with($uri, $scriptDir . '/')) {
                $uri = substr($uri, strlen($scriptDir)) ?: '/';
            }
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path === '/' ? '/' : rtrim($this->path, '/');
    }

    public function is(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with($this->path(), '/api/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function ip(): string
    {
        return client_ip();
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $this->server[$key] ?? $this->server[$name] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /** @param array<string, string> $params */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $this->files;
    }

    public function csrfToken(): ?string
    {
        $header = $this->header('X-CSRF-TOKEN');
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $token = $this->input('_csrf');

        return is_string($token) ? $token : null;
    }
}
