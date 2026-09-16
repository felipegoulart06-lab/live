<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Models\User;
use App\Repositories\LoginLogRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use App\Validators\Validator;
use PDOException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly ProfileRepository $profiles = new ProfileRepository(),
        private readonly LoginLogRepository $logs = new LoginLogRepository(),
        private readonly TokenRepository $tokens = new TokenRepository()
    ) {
    }

    /** @return array{ok:bool, errors?:array, user?:User, message?:string} */
    public function register(array $input, Request $request): array
    {
        $validator = new Validator();
        $ok = $validator->validate($input, [
            'name' => 'required|min:3|max:80',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:120|confirmed',
            'account_intent' => 'required|in:buyer,seller',
        ]);

        if (!$ok) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->users->emailExists($email)) {
            return ['ok' => false, 'errors' => ['email' => ['Este e-mail já está cadastrado.']]];
        }

        $accountType = $input['account_intent'] === 'seller' ? 'seller' : 'buyer';
        $roleSlug = $accountType === 'seller' ? 'freelancer' : 'client';

        $pdo = \App\Core\Database::pdo();
        $pdo->beginTransaction();

        try {
            $userId = $this->users->create([
                'uuid' => $this->uuid(),
                'email' => $email,
                'password' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
                'account_type' => $accountType,
                'status' => 'active',
            ]);

            $roleId = $this->users->roleIdBySlug($roleSlug);
            if ($roleId) {
                $this->users->attachRole($userId, $roleId);
            }

            $name = trim((string) $input['name']);
            $this->profiles->create([
                'user_id' => $userId,
                'display_name' => $name,
                'professional_name' => $accountType === 'seller' ? $name : null,
                'slug' => $this->uniqueSlug($name),
                'country' => 'BR',
            ]);

            $this->createWallet($userId);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            Logger::error('Falha no cadastro', ['email' => $email]);

            return ['ok' => false, 'message' => 'Não foi possível concluir o cadastro.'];
        }

        $row = $this->users->findActive($userId);
        $user = User::fromArray($row ?? ['id' => $userId, 'uuid' => '', 'email' => $email, 'email_verified_at' => null, 'account_type' => $accountType, 'status' => 'active', 'last_seen_at' => null, 'two_factor_enabled' => 0]);

        Auth::login($user);
        $this->users->markLogin($userId, $request->ip());
        $this->logs->record($userId, $email, $request->ip(), $request->userAgent(), true);
        Logger::info('user.created', ['user_id' => $userId]);

        $verify = $this->tokens->createToken($userId, 'email_verify', 1440);
        MailService::queue('email_verification', $email, ['name' => $name, 'token' => $verify]);

        return ['ok' => true, 'user' => $user];
    }

    /** @return array{ok:bool, errors?:array, message?:string} */
    public function attempt(array $input, Request $request): array
    {
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, (int) env('RATE_LIMIT_LOGIN', 8), (int) env('RATE_LIMIT_WINDOW', 300))) {
            Logger::security('Rate limit login', ['ip' => $request->ip()]);

            return ['ok' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos.'];
        }

        $validator = new Validator();
        $ok = $validator->validate($input, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$ok) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        $email = strtolower(trim((string) $input['email']));
        $row = $this->users->findByEmail($email);

        if (!$row || $row['deleted_at'] !== null || !password_verify((string) $input['password'], (string) $row['password'])) {
            $this->logs->record($row['id'] ?? null, $email, $request->ip(), $request->userAgent(), false, 'invalid_credentials');
            Logger::security('Falha de login', ['email' => $email, 'ip' => $request->ip()]);

            return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
        }

        if ($row['status'] !== 'active') {
            $this->logs->record((int) $row['id'], $email, $request->ip(), $request->userAgent(), false, 'inactive');

            return ['ok' => false, 'message' => 'Esta conta está indisponível no momento.'];
        }

        $active = $this->users->findActive((int) $row['id']);
        $user = User::fromArray($active ?? $row);
        Auth::login($user);
        $this->users->markLogin($user->id, $request->ip());
        $this->logs->record($user->id, $email, $request->ip(), $request->userAgent(), true);

        return ['ok' => true];
    }

    /** @return array{ok:bool, message:string} */
    public function requestPasswordReset(string $email): array
    {
        $email = strtolower(trim($email));
        $row = $this->users->findByEmail($email);
        if ($row && $row['deleted_at'] === null) {
            $token = $this->tokens->createToken((int) $row['id'], 'password_reset', 60);
            MailService::queue('password_reset', $email, ['token' => $token]);
        }

        return ['ok' => true, 'message' => 'Se o e-mail existir, enviaremos as instruções de recuperação.'];
    }

    /** @return array{ok:bool, message?:string, errors?:array} */
    public function resetPassword(string $token, array $input): array
    {
        $validator = new Validator();
        if (!$validator->validate($input, ['password' => 'required|min:8|max:120|confirmed'])) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }

        $record = $this->tokens->consume($token, 'password_reset');
        if (!$record) {
            return ['ok' => false, 'message' => 'Link inválido ou expirado.'];
        }

        $this->users->updateById((int) $record['user_id'], [
            'password' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
        ]);
        Logger::info('password.changed', ['user_id' => $record['user_id']]);

        return ['ok' => true, 'message' => 'Senha redefinida. Entre com a nova senha.'];
    }

    public function verifyEmail(string $token): bool
    {
        $record = $this->tokens->consume($token, 'email_verify');
        if (!$record) {
            return false;
        }

        $this->users->updateById((int) $record['user_id'], [
            'email_verified_at' => now(),
        ]);

        return true;
    }

    private function uniqueSlug(string $name): string
    {
        $base = slugify($name) ?: 'profissional';
        $slug = $base;
        $i = 1;
        while ($this->profiles->slugExists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function createWallet(int $userId): void
    {
        $stmt = \App\Core\Database::pdo()->prepare(
            'INSERT INTO wallets (user_id, available_cents, pending_cents, reserved_cents, currency, created_at, updated_at)
             VALUES (:user_id, 0, 0, 0, :currency, :created_at, :updated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'currency' => env('APP_CURRENCY', 'BRL'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
