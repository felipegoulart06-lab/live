<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class MercadoPagoGateway implements PaymentGatewayInterface
{
    public function charge(array $payload): GatewayResult
    {
        return new GatewayResult(false, 'not_configured', null, 'Gateway Mercado Pago ainda não configurado.');
    }

    public function refund(array $payload): GatewayResult
    {
        return new GatewayResult(false, 'not_configured', null, 'Gateway Mercado Pago ainda não configurado.');
    }

    public function handleWebhook(array $payload, string $signature): GatewayResult
    {
        return new GatewayResult(false, 'not_configured');
    }
}
