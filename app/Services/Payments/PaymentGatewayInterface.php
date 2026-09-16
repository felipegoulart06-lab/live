<?php

declare(strict_types=1);

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /** @param array<string, mixed> $payload */
    public function charge(array $payload): GatewayResult;

    /** @param array<string, mixed> $payload */
    public function refund(array $payload): GatewayResult;

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload, string $signature): GatewayResult;
}
