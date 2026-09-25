<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Files never go into the database: the DB keeps the relative path only.
 * Local: public files under public/uploads, private files under storage/private.
 * Production: Supabase Storage (public bucket for listing images/avatars, private bucket for attachments).
 */
final class Storage
{
    public const FOLDERS = ['users', 'listings', 'contracts', 'avatars'];

    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private const FILE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/zip' => 'docx',
    ];

    /**
     * @param array<string, mixed> $file one entry of $_FILES
     * @return array{path: string, thumb_path: ?string, mime: string, size: int, original_name: string}
     */
    public static function store(array $file, string $folder, bool $imagesOnly, bool $private = false): array
    {
        [$top] = explode('/', $folder, 2);
        if (!in_array($top, self::FOLDERS, true) || !preg_match('#^[a-z]+(/[a-z0-9-]+)*$#', $folder)) {
            throw new RuntimeException('Pasta de upload inválida.');
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo é maior que o permitido.',
                UPLOAD_ERR_NO_FILE => 'Selecione um arquivo.',
                default => 'Não foi possível receber o arquivo. Tente novamente.',
            });
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp))) {
            throw new UploadException('Arquivo inválido.');
        }

        $maxMb = (int) config($imagesOnly ? 'uploads.image_max_mb' : 'uploads.file_max_mb', 5);
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > $maxMb * 1024 * 1024) {
            throw new UploadException("O arquivo deve ter no máximo {$maxMb} MB.");
        }

        $original = mb_substr(basename((string) ($file['name'] ?? 'arquivo')), 0, 180);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $allowed = $imagesOnly ? self::IMAGE_TYPES : self::FILE_TYPES;
        if (!isset($allowed[$mime])) {
            throw new UploadException($imagesOnly ? 'Envie uma imagem JPG, PNG ou WEBP.' : 'Formato não permitido. Envie PDF, DOCX, TXT, JPG, PNG ou WEBP.');
        }
        $expected = $allowed[$mime];
        $extensionOk = $extension === $expected || ($expected === 'jpg' && $extension === 'jpeg');
        if (!$extensionOk) {
            throw new UploadException('A extensão do arquivo não corresponde ao conteúdo.');
        }
        if ($mime === 'application/zip' && $extension !== 'docx') {
            throw new UploadException('Formato não permitido.');
        }

        $contents = (string) file_get_contents($tmp);
        $thumb = null;
        if (isset(self::IMAGE_TYPES[$mime])) {
            if (@getimagesizefromstring($contents) === false) {
                throw new UploadException('A imagem está corrompida.');
            }
            // Re-encoding drops EXIF (location) and anything appended to the image bytes.
            $contents = Image::sanitize($contents, $mime, 1920) ?? $contents;
            $thumb = Image::sanitize($contents, $mime, 640);
        }

        $name = bin2hex(random_bytes(12));
        $path = $folder . '/' . $name . '.' . $expected;
        self::put($path, $contents, $mime, $private);

        $thumbPath = null;
        if ($thumb !== null) {
            $thumbPath = $folder . '/' . $name . '_thumb.' . $expected;
            self::put($thumbPath, $thumb, $mime, $private);
        }

        return ['path' => $path, 'thumb_path' => $thumbPath, 'mime' => $mime, 'size' => strlen($contents), 'original_name' => $original];
    }

    public static function url(string $path): string
    {
        if (self::remote()) {
            return config('uploads.supabase_url') . '/storage/v1/object/public/' . config('uploads.public_bucket') . '/' . self::encodePath($path);
        }

        return url('/uploads/' . $path);
    }

    public static function read(string $path, bool $private): ?string
    {
        if (self::remote()) {
            [$status, $body] = self::request('GET', self::objectUrl($path, $private), null, null);

            return $status === 200 ? $body : null;
        }
        $file = self::localPath($path, $private);

        return is_file($file) ? (string) file_get_contents($file) : null;
    }

    public static function delete(?string $path, bool $private = false): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'images/')) {
            return;
        }
        if (self::remote()) {
            self::request('DELETE', self::objectUrl($path, $private), null, null);

            return;
        }
        $file = self::localPath($path, $private);
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function available(): bool
    {
        return self::remote() || !Paths::serverless();
    }

    private static function put(string $path, string $contents, string $mime, bool $private): void
    {
        if (self::remote()) {
            [$status] = self::request('POST', self::objectUrl($path, $private), $contents, $mime);
            if ($status < 200 || $status >= 300) {
                Logger::error('Falha no upload para o Storage', ['status' => $status, 'path' => $path]);
                throw new UploadException('Não foi possível salvar o arquivo agora. Tente novamente.');
            }

            return;
        }
        if (Paths::serverless()) {
            throw new UploadException('O armazenamento de arquivos ainda não foi configurado neste ambiente.');
        }
        $file = self::localPath($path, $private);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        file_put_contents($file, $contents, LOCK_EX);
    }

    private static function remote(): bool
    {
        return config('uploads.supabase_url') !== '' && config('uploads.supabase_key') !== '';
    }

    private static function objectUrl(string $path, bool $private): string
    {
        $bucket = $private ? config('uploads.private_bucket') : config('uploads.public_bucket');

        return config('uploads.supabase_url') . '/storage/v1/object/' . $bucket . '/' . self::encodePath($path);
    }

    private static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private static function localPath(string $path, bool $private): string
    {
        if (str_contains($path, '..')) {
            throw new RuntimeException('Caminho inválido.');
        }

        return BASE_PATH . ($private ? '/storage/private/' : '/public/uploads/') . $path;
    }

    /** @return array{0: int, 1: string} */
    private static function request(string $method, string $url, ?string $body, ?string $mime): array
    {
        $headers = ['Authorization: Bearer ' . config('uploads.supabase_key'), 'apikey: ' . config('uploads.supabase_key')];
        if ($mime !== null) {
            $headers[] = 'Content-Type: ' . $mime;
            $headers[] = 'x-upsert: false';
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, is_string($response) ? $response : ''];
    }
}
