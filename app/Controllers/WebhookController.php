<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Services\MercadoPago;
use App\Services\MercadoPagoClient;
use Throwable;

final class WebhookController extends Controller
{
    public function mercadopago(Request $request): Response
    {
        if ($request->method() === 'GET') {
            $setup = MercadoPagoClient::setup();

            return Response::json([
                'ok' => true,
                'topic' => 'order',
                'ready' => $setup['public_key'] && $setup['access_token'] && $setup['webhook_secret'] && $setup['https_url'],
            ]);
        }

        try {
            MercadoPago::handleWebhook($request);
        } catch (HttpException $e) {
            return Response::json(['ok' => false], $e->status);
        } catch (Throwable $e) {
            Logger::error('Falha ao processar webhook Mercado Pago');

            return Response::json(['ok' => false], 500);
        }

        return Response::json(['ok' => true]);
    }
}
