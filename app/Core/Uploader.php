<?php

declare(strict_types=1);

namespace App\Core;

final class Uploader
{
    /** @var array<int, string> */
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'bat', 'cmd', 'com', 'msi', 'sh', 'bash', 'cgi', 'pl', 'py',
        'js', 'htaccess', 'html', 'htm', 'shtml', 'svg',
    ];

    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'document' => ['application/pdf', 'application/zip', 'application/x-zip-compressed', 'image/jpeg', 'image/png', 'image/webp'],
    ];

    public static function store(array $file, string $directory, string $kind = 'image'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $maxBytes = (int) env('UPLOAD_MAX_MB', 20) * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            return null;
        }

        $original = (string) $file['name'];
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        $allowed = self::ALLOWED[$kind] ?? self::ALLOWED['image'];
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $safeExt = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            default => $extension,
        };

        $relative = $directory . '/' . bin2hex(random_bytes(16)) . '.' . $safeExt;
        $absolute = BASE_PATH . '/storage/uploads/' . $relative;
        $dir = dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $absolute)) {
            return null;
        }

        return $relative;
    }

    public static function storePublicImage(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $stored = self::store($file, 'tmp-public', 'image');
        if ($stored === null) {
            return null;
        }

        $source = BASE_PATH . '/storage/uploads/' . $stored;
        $name = basename($stored);
        $relative = 'images/' . trim($folder, '/') . '/' . $name;
        $dest = BASE_PATH . '/public/assets/' . $relative;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!rename($source, $dest) && !copy($source, $dest)) {
            return null;
        }
        @unlink($source);

        return $relative;
    }
}
