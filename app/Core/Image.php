<?php

declare(strict_types=1);

namespace App\Core;

final class Image
{
    /** Re-encodes and fits the image inside `$maxWidth`. Returns null when GD is not installed. */
    public static function sanitize(string $bytes, string $mime, int $maxWidth): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = min($width, $maxWidth);
        $targetHeight = (int) max(1, round($height * $targetWidth / max(1, $width)));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($mime !== 'image/jpeg') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        match ($mime) {
            'image/png' => imagepng($canvas, null, 7),
            'image/webp' => imagewebp($canvas, null, 82),
            default => imagejpeg($canvas, null, 84),
        };
        $out = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return $out !== '' ? $out : null;
    }
}
