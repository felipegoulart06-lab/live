<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class WebhookDispatcher
{
    /** @param array<string, mixed> $payload */
    public static function dispatch(string $event, array $payload): void
    {
        Logger::info('webhook.event', ['event' => $event, 'payload' => $payload]);
    }
}
