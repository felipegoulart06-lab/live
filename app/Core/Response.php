<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function view(string $view, array $data = [], string $layout = 'layouts/public', int $status = 200): self
    {
        return self::html(View::render($view, $data, $layout), $status);
    }

    public static function file(string $contents, string $mime, string $filename, bool $inline): self
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'arquivo';

        return new self($contents, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($contents),
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function withSecurityHeaders(): self
    {
        $this->headers += [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];
        if (str_starts_with($this->headers['Content-Type'] ?? '', 'text/html')) {
            $this->headers += [
                'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; form-action 'self'; base-uri 'self'; frame-ancestors 'self'",
            ];
            if (Auth::check()) {
                $this->headers += ['Cache-Control' => 'private, no-store'];
            }
        }

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }
}
