<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Models\User;
use App\Repositories\LoginLogRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use PDOException;

final class GoogleAuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly ProfileRepository $profiles = new ProfileRepository(),
        private readonly LoginLogRepository $logs = new LoginLogRepository()
    ) {
    }

    public function enabled(): bool
    {
        return SettingService::googleEnabled();
    }

    public function redirectUri(): string
    {
        return url('/entrar/google/retorno');
    }

    public function authorizationUrl(string $intent = 'buyer'): string
    {
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_google_state', $state);
        Session::set('oauth_google_intent', $intent === 'seller' ? 'seller' : 'buyer');

        $query = http_build_query([
            'client_id' => SettingService::googleClientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    /** @return array{ok:bool, message?:string} */
    public function handleCallback(Request $request): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'message' => 'Login com Google não está ativo.'];
        }

        $error = trim((string) $request->query('error', ''));
        if ($error !== '') {
            return ['ok' => false, 'message' => 'Login com Google cancelado.'];
        }

        $state = (string) $request->query('state', '');
        $expected = (string) Session::get('oauth_google_state', '');
        Session::forget('oauth_google_state');
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            return ['ok' => false, 'message' => 'Sessão do Google inválida. Tente novamente.'];
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return ['ok' => false, 'message' => 'Código do Google ausente.'];
        }

        $token = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => SettingService::googleClientId(),
            'client_secret' => SettingService::googleClientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        $access = is_array($token) ? (string) ($token['access_token'] ?? '') : '';
        if ($access === '') {
            Logger::error('Google token falhou', ['body' => $token]);

            return ['ok' => false, 'message' => 'Não foi possível validar o Google.'];
        }

        $profile = $this->httpGet('https://www.googleapis.com/oauth2/v3/userinfo', $access);
        $googleId = is_array($profile) ? (string) ($profile['sub'] ?? '') : '';
        $email = is_array($profile) ? strtolower(trim((string) ($profile['email'] ?? ''))) : '';
        $name = is_array($profile) ? trim((string) ($profile['name'] ?? '')) : '';
        $picture = is_array($profile) ? (string) ($profile['picture'] ?? '') : '';
        $emailVerified = is_array($profile) && !empty($profile['email_verified']);

        if ($googleId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'O Google não enviou um e-mail válido.'];
        }

        $row = $this->users->findOAuth('google', $googleId);
        if (!$row) {
            $existing = $this->users->findByEmail($email);
            if ($existing && $existing['deleted_at'] === null) {
                if ($existing['status'] !== 'active') {
                    return ['ok' => false, 'message' => 'Esta conta está indisponível no momento.'];
                }
                $this->users->linkOAuth((int) $existing['id'], 'google', $googleId, $email);
                $row = $this->users->findActive((int) $existing['id']);
            } else {
                if ((string) SettingService::get('registrations_open', '1') !== '1') {
                    return ['ok' => false, 'message' => 'Cadastros estão fechados no momento.'];
                }
                $intent = (string) Session::get('oauth_google_intent', 'buyer');
                $created = $this->createUser($email, $name !== '' ? $name : strtok($email, '@'), $intent, $emailVerified, $picture);
                if (!$created) {
                    return ['ok' => false, 'message' => 'Não foi possível criar a conta com o Google.'];
                }
                $this->users->linkOAuth($created, 'google', $googleId, $email);
                $row = $this->users->findActive($created);
            }
        }

        Session::forget('oauth_google_intent');

        if (!$row || ($row['status'] ?? '') !== 'active') {
            return ['ok' => false, 'message' => 'Esta conta está indisponível no momento.'];
        }

        $user = User::fromArray($row);
        Auth::login($user);
        $this->users->markLogin($user->id, $request->ip());
        $this->logs->record($user->id, $email, $request->ip(), $request->userAgent(), true, 'google');
        Logger::info('user.login.google', ['user_id' => $user->id]);

        return ['ok' => true];
    }

    private function createUser(string $email, string $name, string $intent, bool $verified, string $avatar): ?int
    {
        $accountType = $intent === 'seller' ? 'seller' : 'buyer';
        $roleSlug = $accountType === 'seller' ? 'freelancer' : 'client';
        $pdo = \App\Core\Database::pdo();
        $pdo->beginTransaction();

        try {
            $userId = $this->users->create([
                'uuid' => nexo_uuid(),
                'email' => $email,
                'password' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                'account_type' => $accountType,
                'status' => 'active',
                'email_verified_at' => $verified ? now() : null,
            ]);

            $roleId = $this->users->roleIdBySlug($roleSlug);
            if ($roleId) {
                $this->users->attachRole($userId, $roleId);
            }

            $this->profiles->create([
                'user_id' => $userId,
                'display_name' => $name,
                'professional_name' => $accountType === 'seller' ? $name : null,
                'slug' => $this->uniqueSlug($name),
                'country' => 'BR',
                'avatar_path' => $avatar !== '' ? $avatar : null,
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at)
                 VALUES (:user_id, 0, 0, 0, :currency, :created_at, :updated_at)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'currency' => env('APP_CURRENCY', 'BRL'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pdo->commit();

            return $userId;
        } catch (PDOException $e) {
            $pdo->rollBack();
            Logger::error('Falha no cadastro Google', ['email' => $email]);

            return null;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = slugify($name) ?: 'usuario';
        $slug = $base;
        $i = 1;
        while ($this->profiles->slugExists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /** @param array<string, string> $fields */
    private function httpPost(string $url, array $fields): mixed
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        return is_string($raw) ? json_decode($raw, true) : null;
    }

    private function httpGet(string $url, string $accessToken): mixed
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        return is_string($raw) ? json_decode($raw, true) : null;
    }
}
