<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class MailService
{
    /** @param array<string, mixed> $payload */
    public static function queue(string $template, string $to, array $payload = []): void
    {
        Logger::info('mail.queued', [
            'template' => $template,
            'to' => $to,
            'payload_keys' => array_keys($payload),
        ]);

        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf("[%s] TEMPLATE=%s TO=%s DATA=%s\n", date('Y-m-d H:i:s'), $template, $to, json_encode($payload, JSON_UNESCAPED_UNICODE));
        file_put_contents($dir . '/mail.log', $line, FILE_APPEND | LOCK_EX);
    }
}
