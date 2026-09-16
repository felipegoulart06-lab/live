<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class PaymentGatewayManager
{
    public function resolve(string $driver): PaymentGatewayInterface
    {
        return match ($driver) {
            'stripe' => new StripeGateway(),
            default => new MercadoPagoGateway(),
        };
    }
}
