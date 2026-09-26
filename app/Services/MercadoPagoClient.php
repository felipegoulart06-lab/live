<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use RuntimeException;

/** Server-side calls to Mercado Pago. The access token never leaves this class. */
final class MercadoPagoClient
{
    private const BASE = 'https://api.mercadopago.com';

    public static function configured(): bool
    {
        return self::publicKey() !== '' && self::accessToken() !== '';
    }

    public static function publicKey(): string
    {
        return self::credential('public_key', 'mp_public_key');
    }

    public static function accessToken(): string
    {
        return self::credential('access_token', 'mp_access_token');
    }

    public static function webhookSecret(): string
    {
        return self::credential('webhook_secret', 'mp_webhook_secret');
    }

    private static function credential(string $configKey, string $settingKey): string
    {
        $fromEnv = trim((string) config('mercadopago.' . $configKey, ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        try {
            return trim((string) Settings::get($settingKey, ''));
        } catch (\Throwable) {
            return '';
        }
    }

    public static function notificationUrl(): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        if (!str_starts_with($base, 'https://')) {
            return '';
        }

        return $base . '/webhooks/mercadopago';
    }

    /** @return array{public_key:bool,access_token:bool,webhook_secret:bool,https_url:bool} */
    public static function setup(): array
    {
        return [
            'public_key' => self::publicKey() !== '',
            'access_token' => self::accessToken() !== '',
            'webhook_secret' => self::webhookSecret() !== '',
            'https_url' => self::notificationUrl() !== '',
        ];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public static function createOrder(array $payload, string $idempotencyKey): array
    {
        return self::request('POST', '/v1/orders', $payload, [
            'X-Idempotency-Key: ' . $idempotencyKey,
        ]);
    }

    /** @return array<string, mixed> */
    public static function getOrder(string $id): array
    {
        $id = strtoupper(trim($id));
        if (!preg_match('/^[A-Z0-9]{10,80}$/', $id)) {
            throw new RuntimeException('Identificador de order inválido.');
        }

        return self::request('GET', '/v1/orders/' . rawurlencode($id));
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<int, string> $extraHeaders
     * @return array<string, mixed>
     */
    private static function request(string $method, string $path, ?array $payload = null, array $extraHeaders = []): array
    {
        $token = self::accessToken();
        if ($token === '') {
            throw new RuntimeException('Mercado Pago não está configurado.');
        }

        $headers = array_merge([
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json',
        ], $extraHeaders);

        $ch = curl_init(self::BASE . $path);
        if ($ch === false) {
            throw new RuntimeException('Falha ao iniciar a conexão com o Mercado Pago.');
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($payload !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || !is_string($raw)) {
            Logger::error('Falha de rede no Mercado Pago', ['http' => $http]);
            throw new RuntimeException('Não foi possível falar com o Mercado Pago. Tente de novo em instantes.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Resposta inválida do Mercado Pago.');
        }
        if ($http >= 400) {
            Logger::warning('Mercado Pago recusou o pagamento', [
                'http' => $http,
                'status' => $decoded['status'] ?? null,
                'status_detail' => $decoded['status_detail'] ?? null,
            ]);
            throw new RuntimeException('Não foi possível concluir o pagamento. Tente de novo ou use Pix.');
        }

        $decoded['_http'] = $http;

        return $decoded;
    }
}
