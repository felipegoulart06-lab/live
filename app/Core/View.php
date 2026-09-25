<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/public'): string
    {
        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        $data['authUser'] = $data['authUser'] ?? Auth::user();
        $data['errors'] = $data['errors'] ?? Session::getFlash('errors', []);
        $data['old'] = $data['old'] ?? Session::getFlash('old', []);
        $data['success'] = $data['success'] ?? Session::getFlash('success');
        $data['error'] = $data['error'] ?? Session::getFlash('error');
        $GLOBALS['_nexo_old'] = is_array($data['old']) ? $data['old'] : [];
        $GLOBALS['_nexo_errors'] = is_array($data['errors']) ? $data['errors'] : [];

        $content = self::capture($viewFile, $data);

        if ($layout === null) {
            return $content;
        }

        $layoutFile = BASE_PATH . '/views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout não encontrado: ' . $layout);
        }

        $data['content'] = $content;

        return self::capture($layoutFile, $data);
    }

    /** @param array<string, mixed> $data */
    public static function component(string $name, array $data = []): string
    {
        $file = BASE_PATH . '/views/components/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Componente não encontrado: ' . $name);
        }

        return self::capture($file, $data);
    }

    /** @param array<string, mixed> $data */
    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
