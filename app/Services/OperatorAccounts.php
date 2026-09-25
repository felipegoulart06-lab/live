<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/** Operator logins that must exist in whatever database the app is using (SQLite or Postgres). */
final class OperatorAccounts
{
    public static function ensure(): void
    {
        self::ensureOne('admin', [
            'email' => (string) env('SEED_ADMIN_EMAIL', 'admin@cinquentaconto.com.br'),
            'password' => (string) env('SEED_ADMIN_PASSWORD', 'CinquentaAdmin!234'),
            'display_name' => 'Administração CinquentaConto',
        ], 'master');

        self::ensureOne('admin', [
            'email' => 'moderacao@cinquentaconto.com.br',
            'password' => (string) env('SEED_USER_PASSWORD', 'Demo12345'),
            'display_name' => 'Moderação',
        ], 'staff');

        self::ensureOne('creator', [
            'email' => (string) env('SEED_CREATOR_EMAIL', 'anunciante@cinquentaconto.com.br'),
            'password' => (string) env('SEED_CREATOR_PASSWORD', 'CinquentaCriador!234'),
            'display_name' => 'Anunciante CinquentaConto',
            'headline' => 'Horas de vídeo em talking head',
            'bio' => 'Anuncio horas de gravação em câmera para empresas. O roteiro e o tema saem do briefing de vocês.',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        self::ensureOne('company', [
            'email' => (string) env('SEED_COMPANY_EMAIL', 'empresa@cinquentaconto.com.br'),
            'password' => (string) env('SEED_COMPANY_PASSWORD', 'CinquentaEmpresa!234'),
            'display_name' => 'Empresa CinquentaConto',
            'company_name' => 'Empresa CinquentaConto',
            'responsible_name' => 'Responsável',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function ensureOne(string $role, array $data, ?string $adminLevel = null): void
    {
        $email = mb_strtolower(trim((string) $data['email']));
        if ($email === '' || Accounts::emailTaken($email)) {
            return;
        }

        Accounts::create($role, $data, $adminLevel);
    }
}
