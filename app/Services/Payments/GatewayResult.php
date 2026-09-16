<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class GatewayResult
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $externalId = null,
        public readonly ?string $message = null,
        public readonly array $meta = []
    ) {
    }
}
