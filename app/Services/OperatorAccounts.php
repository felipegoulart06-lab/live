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

        self::ensureCompanyCheckoutFixture();
    }

    /** Operator company needs a payable contract so Mercado Pago can be tested. */
    private static function ensureCompanyCheckoutFixture(): void
    {
        try {
            $email = mb_strtolower(trim((string) env('SEED_COMPANY_EMAIL', 'empresa@cinquentaconto.com.br')));
            $company = Db::first("SELECT id FROM users WHERE email = :e AND role = 'company'", ['e' => $email]);
            if (!$company) {
                return;
            }
            $companyId = (int) $company['id'];
            Db::run(
                "UPDATE companies SET document = :d WHERE user_id = :u AND (document IS NULL OR TRIM(document) = '')",
                ['d' => '19119119100', 'u' => $companyId]
            );
            $open = (int) Db::value(
                "SELECT COUNT(*) FROM contracts WHERE company_id = :u AND status = 'awaiting_payment'",
                ['u' => $companyId]
            );
            if ($open > 0) {
                return;
            }
            $pending = Db::first(
                "SELECT * FROM requests WHERE company_id = :u AND status = 'pending' ORDER BY id DESC LIMIT 1",
                ['u' => $companyId]
            );
            if ($pending) {
                Deals::acceptRequest($pending, (int) $pending['creator_id']);

                return;
            }
            $listing = Db::first(
                "SELECT * FROM listings WHERE status = 'active' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1"
            );
            if (!$listing) {
                return;
            }
            $package = Db::first(
                'SELECT * FROM listing_packages WHERE listing_id = :l ORDER BY price_cents ASC LIMIT 1',
                ['l' => (int) $listing['id']]
            );
            if (!$package) {
                return;
            }
            $created = Deals::createRequest($companyId, $listing, (int) $package['id'], [], [
                'theme' => 'Teste de checkout Mercado Pago',
                'briefing' => 'Contrato de teste para Checkout Transparente via Orders.',
                'message' => null,
                'desired_date' => null,
                'desired_time' => null,
            ]);
            $request = Db::first('SELECT * FROM requests WHERE uuid = :u', ['u' => $created['uuid']]);
            if ($request) {
                Deals::acceptRequest($request, (int) $listing['creator_id']);
            }
        } catch (\Throwable) {
            // Demo fixture must not take the site down.
        }
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
