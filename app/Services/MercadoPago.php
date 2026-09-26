<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;

/**
 * Checkout transparente: o navegador só envia o token gerado pelo Brick.
 * O valor cobrado sai sempre da linha de pagamento do contrato.
 */
final class MercadoPago
{
    public static function configured(): bool
    {
        return MercadoPagoClient::configured();
    }

    public static function publicKey(): string
    {
        return MercadoPagoClient::publicKey();
    }

    /** @param array<string, mixed> $contract @param array<string, mixed> $payment @param array<string, mixed> $input @return array<string, mixed> */
    public static function charge(array $contract, array $payment, array $input, string $payerEmail, string $ip): array
    {
        if ((int) $contract['company_id'] !== (int) $payment['company_id']) {
            throw HttpException::notFound('Pagamento não encontrado.');
        }
        if ($contract['status'] !== 'awaiting_payment' || $payment['status'] !== 'pending') {
            throw new HttpException(422, 'Este contrato não está aguardando pagamento.');
        }

        $pendingPix = self::pixFromPayload((string) ($payment['gateway_payload'] ?? ''));
        if ($pendingPix !== null && ($payment['gateway_status'] ?? '') === 'pending') {
            return ['status' => 'pending', 'pix' => $pendingPix];
        }

        $methodId = strtolower(trim((string) ($input['payment_method_id'] ?? '')));
        if ($methodId === '') {
            throw new HttpException(422, 'Escolha uma forma de pagamento.');
        }

        $amount = number_format(((int) $payment['amount_cents']) / 100, 2, '.', '');
        $description = 'Contrato ' . $contract['code'] . ' · CinquentaConto';
        $methodType = self::methodType($methodId, $input);
        $orderPayment = [
            'amount' => $amount,
            'payment_method' => [
                'id' => $methodId,
                'type' => $methodType,
            ],
        ];

        if ($methodType === 'bank_transfer') {
            $orderPayment['expiration_time'] = 'PT24H';
        } else {
            $token = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($input['token'] ?? '')) ?? '';
            if (strlen($token) < 10) {
                throw new HttpException(422, 'Não foi possível validar o cartão. Recarregue a página e tente de novo.');
            }
            $orderPayment['payment_method']['token'] = $token;
            $orderPayment['payment_method']['installments'] = max(1, min(12, (int) ($input['installments'] ?? 1)));
        }

        $payer = ['email' => $payerEmail];
        $identification = $input['payer']['identification'] ?? [];
        if (is_array($identification)) {
            $doc = self::sanitizeDocument((string) ($identification['number'] ?? ''));
            $type = strtoupper((string) ($identification['type'] ?? (strlen($doc) === 14 ? 'CNPJ' : 'CPF')));
            if ($doc !== '' && in_array($type, ['CPF', 'CNPJ'], true)) {
                $payer['identification'] = ['type' => $type, 'number' => $doc];
            }
        }

        $payload = [
            'type' => 'online',
            'processing_mode' => 'automatic',
            'capture_mode' => 'automatic_async',
            'total_amount' => $amount,
            'external_reference' => (string) $payment['uuid'],
            'description' => $description,
            'payer' => $payer,
            'transactions' => [
                'payments' => [$orderPayment],
            ],
        ];
        $notify = MercadoPagoClient::notificationUrl();
        if ($notify !== '') {
            $payload['config'] = ['notification_url' => $notify];
        }

        $idempotency = uuid4();
        Db::update('payments', [
            'idempotency_key' => $idempotency,
            'gateway' => 'mercadopago',
            'updated_at' => now(),
        ], ['id' => (int) $payment['id']]);

        $result = MercadoPagoClient::createOrder($payload, $idempotency);

        return self::applyGatewayResult($contract, $payment, $result);
    }

    public static function handleWebhook(Request $request): void
    {
        $secret = MercadoPagoClient::webhookSecret();
        if ($secret === '') {
            Logger::security('Webhook Mercado Pago recusado: secret ausente', ['ip' => $request->ip()]);
            throw new HttpException(503, 'Webhook não configurado.');
        }
        if (!self::validSignature($request, $secret)) {
            Logger::security('Webhook Mercado Pago com assinatura inválida', ['ip' => $request->ip()]);
            throw new HttpException(401, 'Assinatura inválida.');
        }

        $type = strtolower((string) ($request->query('type') ?? $request->query('topic') ?? $request->input('type', '')));
        $id = self::resourceId($request);
        if ($id === '' || ($type !== '' && $type !== 'order')) {
            return;
        }

        $eventKey = 'mercadopago:' . ($type !== '' ? $type : 'order') . ':' . strtolower($id);
        try {
            Db::insert('webhook_events', ['provider' => 'mercadopago', 'event_key' => $eventKey, 'processed_at' => now()]);
        } catch (\Throwable) {
            // duplicate notification
        }

        $remote = MercadoPagoClient::getOrder($id);
        $reference = (string) ($remote['external_reference'] ?? '');
        if ($reference === '') {
            return;
        }
        $payment = Db::first('SELECT * FROM payments WHERE uuid = :u', ['u' => $reference]);
        if (!$payment) {
            return;
        }
        $contract = Db::first('SELECT * FROM contracts WHERE id = :id', ['id' => (int) $payment['contract_id']]);
        if (!$contract) {
            return;
        }

        try {
            self::applyGatewayResult($contract, $payment, $remote);
        } catch (HttpException) {
            Logger::security('Webhook Mercado Pago ignorado após validação de valor', ['payment' => $reference]);
        }
    }

    /** @param array<string, mixed> $contract @param array<string, mixed> $payment @param array<string, mixed> $remote @return array<string, mixed> */
    public static function applyGatewayResult(array $contract, array $payment, array $remote): array
    {
        $tx = self::firstTransaction($remote);
        $status = strtolower((string) ($tx['status'] ?? $remote['status'] ?? ''));
        $detail = strtolower((string) ($tx['status_detail'] ?? $remote['status_detail'] ?? ''));
        $mpId = (string) ($remote['id'] ?? '');
        $amountRemote = (int) round(((float) ($tx['amount'] ?? $remote['total_amount'] ?? 0)) * 100);
        if ($amountRemote > 0 && $amountRemote !== (int) $payment['amount_cents']) {
            Logger::security('Valor do Mercado Pago diverge do contrato', [
                'payment' => $payment['uuid'],
                'expected' => (int) $payment['amount_cents'],
                'got' => $amountRemote,
            ]);
            throw new HttpException(422, 'O valor do pagamento não confere com o contrato.');
        }

        $method = (string) ($tx['payment_method']['id'] ?? 'mercadopago');
        $normalized = self::normalizeStatus($status, $detail);
        $snapshot = json_encode(self::safeSnapshot($remote), JSON_UNESCAPED_UNICODE);

        Db::update('payments', [
            'gateway' => 'mercadopago',
            'gateway_reference' => $mpId !== '' ? $mpId : $payment['gateway_reference'],
            'gateway_status' => $status !== '' ? $status : $payment['gateway_status'],
            'gateway_payload' => $snapshot,
            'method' => $method,
            'updated_at' => now(),
        ], ['id' => (int) $payment['id']]);

        if ($normalized === 'approved') {
            $fresh = Db::first('SELECT * FROM contracts WHERE id = :id', ['id' => (int) $contract['id']]);
            if ($fresh) {
                Deals::confirmPayment($fresh, null, $method, $mpId, 'mercadopago');
            }

            return ['status' => 'approved'];
        }

        if (in_array($normalized, ['cancelled', 'rejected'], true) && $contract['status'] === 'awaiting_payment') {
            return ['status' => $normalized, 'message' => self::humanStatus($status, $detail)];
        }

        if ($normalized === 'refunded' && in_array($contract['status'], ['confirmed', 'in_progress'], true)) {
            Deals::cancel($contract, 0, 'Pagamento estornado pelo Mercado Pago.', true);

            return ['status' => 'refunded'];
        }

        return [
            'status' => $normalized,
            'message' => self::humanStatus($status, $detail),
            'pix' => self::pixFromRemote($remote),
        ];
    }

    public static function humanStatus(string $status, string $detail): string
    {
        return match ($detail !== '' ? $detail : $status) {
            'accredited', 'approved', 'processed' => 'Pagamento aprovado.',
            'waiting_transfer', 'action_required', 'pending_waiting_payment', 'pending_waiting_transfer', 'pending' => 'Pagamento em processamento. Se foi Pix, pague o QR Code para confirmar.',
            'cc_rejected_bad_filled_card_number', 'cc_rejected_bad_filled_date', 'cc_rejected_bad_filled_other', 'cc_rejected_bad_filled_security_code' => 'Confira os dados do cartão e tente de novo.',
            'cc_rejected_insufficient_amount' => 'Cartão sem limite suficiente.',
            'cc_rejected_high_risk', 'cc_rejected_blacklist' => 'O pagamento foi recusado por segurança. Use outro cartão ou Pix.',
            'cc_rejected_call_for_authorize' => 'Autorize o pagamento com o banco e tente de novo.',
            'rejected', 'failed', 'cancelled', 'expired' => 'O pagamento foi recusado. Tente outro meio.',
            default => 'Não foi possível concluir o pagamento. Tente de novo ou use Pix.',
        };
    }

    /** @param array<string, mixed> $input */
    private static function methodType(string $methodId, array $input): string
    {
        $hint = strtolower((string) ($input['payment_type_id'] ?? $input['paymentTypeId'] ?? $input['selectedPaymentMethod'] ?? ''));
        if ($methodId === 'pix' || $hint === 'bank_transfer' || $hint === 'pix') {
            return 'bank_transfer';
        }
        if (str_contains($hint, 'debit') || $methodId === 'debit_card') {
            return 'debit_card';
        }

        return 'credit_card';
    }

    private static function normalizeStatus(string $status, string $detail): string
    {
        if (in_array($status, ['processed', 'accredited'], true) || $detail === 'accredited') {
            return 'approved';
        }
        if (in_array($status, ['refunded', 'charged_back'], true) || str_contains($detail, 'refund')) {
            return 'refunded';
        }
        if (in_array($status, ['cancelled', 'canceled', 'expired', 'failed', 'rejected'], true)) {
            return $status === 'rejected' || $status === 'failed' ? 'rejected' : 'cancelled';
        }
        if (in_array($status, ['action_required', 'processing', 'pending'], true) || $detail === 'waiting_transfer') {
            return 'pending';
        }

        return $status !== '' ? $status : 'pending';
    }

    /** @param array<string, mixed> $remote @return array<string, mixed> */
    private static function firstTransaction(array $remote): array
    {
        $payments = $remote['transactions']['payments'] ?? [];

        return is_array($payments[0] ?? null) ? $payments[0] : [];
    }

    private static function validSignature(Request $request, string $secret): bool
    {
        $header = (string) $request->header('X-Signature', '');
        $requestId = (string) $request->header('X-Request-Id', '');
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[trim($k)] = trim($v);
        }
        $ts = $parts['ts'] ?? '';
        $hash = $parts['v1'] ?? '';
        if ($ts === '' || $hash === '') {
            return false;
        }
        $dataId = strtolower(self::resourceId($request));
        $chunks = [];
        if ($dataId !== '') {
            $chunks[] = 'id:' . $dataId;
        }
        if ($requestId !== '') {
            $chunks[] = 'request-id:' . $requestId;
        }
        $chunks[] = 'ts:' . $ts;
        $manifest = implode(';', $chunks) . ';';
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }

    private static function resourceId(Request $request): string
    {
        $query = [];
        parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $query);
        $fromQuery = (string) ($query['data.id'] ?? $query['data_id'] ?? $query['id'] ?? '');
        $data = $request->input('data');
        $fromBody = is_array($data) ? (string) ($data['id'] ?? '') : '';
        $id = $fromQuery !== '' ? $fromQuery : $fromBody;

        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $id) ?? '');
    }

    /** @param array<string, mixed> $remote @return array<string, mixed> */
    private static function safeSnapshot(array $remote): array
    {
        $tx = self::firstTransaction($remote);
        $method = is_array($tx['payment_method'] ?? null) ? $tx['payment_method'] : [];

        return [
            'id' => $remote['id'] ?? null,
            'status' => $tx['status'] ?? $remote['status'] ?? null,
            'status_detail' => $tx['status_detail'] ?? $remote['status_detail'] ?? null,
            'payment_method_id' => $method['id'] ?? null,
            'payment_type_id' => $method['type'] ?? null,
            'transaction_amount' => $tx['amount'] ?? $remote['total_amount'] ?? null,
            'date_created' => $remote['created_date'] ?? $remote['date_created'] ?? null,
            'qr_code' => $method['qr_code'] ?? null,
            'qr_code_base64' => $method['qr_code_base64'] ?? $method['qr_code_based64'] ?? null,
            'ticket_url' => $method['ticket_url'] ?? null,
        ];
    }

    /** @return array{qr_code:?string,qr_code_base64:?string,ticket_url:?string}|null */
    private static function pixFromRemote(array $remote): ?array
    {
        $method = self::firstTransaction($remote)['payment_method'] ?? null;
        if (!is_array($method) || empty($method['qr_code'])) {
            return null;
        }

        return [
            'qr_code' => (string) $method['qr_code'],
            'qr_code_base64' => (string) ($method['qr_code_base64'] ?? $method['qr_code_based64'] ?? ''),
            'ticket_url' => (string) ($method['ticket_url'] ?? ''),
        ];
    }

    /** @return array{qr_code:?string,qr_code_base64:?string,ticket_url:?string}|null */
    private static function pixFromPayload(string $json): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['qr_code'])) {
            return null;
        }

        return [
            'qr_code' => (string) $data['qr_code'],
            'qr_code_base64' => (string) ($data['qr_code_base64'] ?? ''),
            'ticket_url' => (string) ($data['ticket_url'] ?? ''),
        ];
    }

    public static function sanitizeDocument(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (!in_array(strlen($digits), [11, 14], true)) {
            return '';
        }

        return $digits;
    }
}
