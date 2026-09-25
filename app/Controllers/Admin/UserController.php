<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Accounts;
use App\Services\Audit;
use App\Services\Notifier;

/** Creators (/admin/criadores), companies (/admin/empresas) and administrators (/admin/administradores). */
final class UserController extends AdminController
{
    public function creators(Request $request): Response
    {
        $where = ["u.role = 'creator'", 'u.deleted_at IS NULL'];
        $params = [];
        $this->commonFilters($request, $where, $params);
        if ($request->query('verificado') === '1') {
            $where[] = 'cr.is_verified = 1';
        } elseif ($request->query('verificado') === 'pedido') {
            $where[] = 'cr.verification_requested_at IS NOT NULL AND cr.is_verified = 0';
        }

        return $this->render('admin/users/creators', [
            'title' => 'Criadores',
            'result' => Db::paginate(
                "u.uuid, u.email, u.status, u.created_at, u.last_seen_at, p.display_name, p.avatar_path, p.city, p.state,
                 cr.is_verified, cr.verification_requested_at, cr.rating_avg, cr.rating_count, cr.contracts_count,
                 (SELECT COUNT(*) FROM listings l WHERE l.creator_id = u.id AND l.status = 'active' AND l.deleted_at IS NULL) AS active_listings",
                'FROM users u JOIN profiles p ON p.user_id = u.id JOIN creators cr ON cr.user_id = u.id WHERE ' . implode(' AND ', $where),
                $params,
                $this->page($request),
                25,
                $this->order($request, 'cr')
            ),
        ]);
    }

    public function companies(Request $request): Response
    {
        $where = ["u.role = 'company'", 'u.deleted_at IS NULL'];
        $params = [];
        $this->commonFilters($request, $where, $params, 'co.company_name');

        return $this->render('admin/users/companies', [
            'title' => 'Empresas',
            'result' => Db::paginate(
                "u.uuid, u.email, u.status, u.created_at, u.last_seen_at, p.display_name, co.company_name, co.city, co.state,
                 (SELECT COUNT(*) FROM contracts c WHERE c.company_id = u.id) AS contracts_count,
                 (SELECT COALESCE(SUM(pay.amount_cents), 0) FROM payments pay WHERE pay.company_id = u.id AND pay.status IN ('paid', 'awaiting_payout', 'paid_out')) AS invested",
                'FROM users u JOIN profiles p ON p.user_id = u.id JOIN companies co ON co.user_id = u.id WHERE ' . implode(' AND ', $where),
                $params,
                $this->page($request),
                25,
                $this->order($request, null)
            ),
        ]);
    }

    public function blocked(Request $request): Response
    {
        $where = ["u.status = 'blocked'", 'u.deleted_at IS NULL'];
        $params = [];
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where[] = '(LOWER(p.display_name) LIKE :q' . \App\Core\Db::ESCAPE . ' OR LOWER(u.email) LIKE :q2' . \App\Core\Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = Db::like($q);
        }
        $role = (string) $request->query('papel', '');
        if (in_array($role, ['creator', 'company'], true)) {
            $where[] = 'u.role = :role';
            $params['role'] = $role;
        }

        return $this->render('admin/users/blocked', [
            'title' => 'Bloqueados',
            'result' => Db::paginate(
                'u.uuid, u.email, u.role, u.status, u.blocked_reason, u.created_at, u.last_seen_at, p.display_name, p.avatar_path, p.city, p.state',
                'FROM users u JOIN profiles p ON p.user_id = u.id WHERE ' . implode(' AND ', $where),
                $params,
                $this->page($request),
                25,
                'u.updated_at DESC, u.id DESC'
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $user = $this->findUser((string) $request->param('id'));
        $expected = str_starts_with($request->path(), '/admin/criadores') ? 'creator' : 'company';
        if ($user['role'] !== $expected) {
            throw HttpException::notFound('Usuário não encontrado.');
        }
        $isCreator = $user['role'] === 'creator';
        $id = (int) $user['id'];
        $column = $isCreator ? 'creator_id' : 'company_id';

        $data = [
            'title' => $user['display_name'],
            'u' => $user,
            'isCreator' => $isCreator,
            'contracts' => Db::all(
                "SELECT c.uuid, c.code, c.status, c.total_cents, c.created_at, l.title AS listing_title, co.company_name, cp.display_name AS creator_name
                 FROM contracts c JOIN listings l ON l.id = c.listing_id JOIN companies co ON co.user_id = c.company_id JOIN profiles cp ON cp.user_id = c.creator_id
                 WHERE c.{$column} = :u ORDER BY c.created_at DESC LIMIT 10",
                ['u' => $id]
            ),
            'requests' => Db::all(
                "SELECT r.uuid, r.code, r.status, r.total_cents, r.created_at, l.title AS listing_title
                 FROM requests r JOIN listings l ON l.id = r.listing_id WHERE r.{$column} = :u ORDER BY r.created_at DESC LIMIT 10",
                ['u' => $id]
            ),
            'activity' => Db::all('SELECT action, description, ip, created_at FROM activity_log WHERE user_id = :u ORDER BY created_at DESC, id DESC LIMIT 15', ['u' => $id]),
            'money' => Db::first(
                "SELECT COALESCE(SUM(CASE WHEN status IN ('paid', 'awaiting_payout', 'paid_out') THEN amount_cents ELSE 0 END), 0) AS gross,
                        COALESCE(SUM(CASE WHEN status = 'awaiting_payout' THEN net_cents ELSE 0 END), 0) AS awaiting_payout,
                        COALESCE(SUM(CASE WHEN status = 'paid_out' THEN net_cents ELSE 0 END), 0) AS paid_out
                 FROM payments WHERE {$column} = :u",
                ['u' => $id]
            ),
        ];
        if ($isCreator) {
            $data['listings'] = Db::all(
                'SELECT uuid, title, status, starting_price_cents, contracts_count, rating_avg, updated_at FROM listings WHERE creator_id = :u AND deleted_at IS NULL ORDER BY updated_at DESC',
                ['u' => $id]
            );
            $data['reviews'] = Db::all(
                'SELECT r.id, r.rating, r.comment, r.status, r.created_at, co.company_name FROM reviews r JOIN companies co ON co.user_id = r.company_id WHERE r.creator_id = :u ORDER BY r.created_at DESC LIMIT 10',
                ['u' => $id]
            );
        }

        return $this->render('admin/users/show', $data);
    }

    public function block(Request $request): Response
    {
        $user = $this->findUser((string) $request->param('id'));
        $this->guardTarget($user);
        $reason = $this->reason($request);
        if ($reason === null) {
            Session::flash('errors', ['reason' => ['Informe o motivo do bloqueio (mínimo de 5 caracteres).']]);

            return $this->back('/admin');
        }
        Db::transaction(static function () use ($user, $reason): void {
            Db::update('users', ['status' => 'blocked', 'blocked_reason' => $reason, 'updated_at' => now()], ['id' => (int) $user['id']]);
            Db::run('DELETE FROM sessions WHERE user_id = :u', ['u' => (int) $user['id']]);
            if ($user['role'] === 'creator') {
                Db::run("UPDATE listings SET status = 'paused', updated_at = :now WHERE creator_id = :u AND status IN ('active', 'pending')", ['now' => now(), 'u' => (int) $user['id']]);
            }
        });
        Audit::admin('user.block', 'user', (int) $user['id'], 'Bloqueou ' . $user['display_name'], ['reason' => $reason]);
        $this->success('Conta bloqueada. As sessões foram encerradas' . ($user['role'] === 'creator' ? ' e os anúncios ativos foram pausados.' : '.'));

        return $this->back('/admin');
    }

    public function unblock(Request $request): Response
    {
        $user = $this->findUser((string) $request->param('id'));
        $this->guardTarget($user);
        Db::update('users', ['status' => 'active', 'blocked_reason' => null, 'updated_at' => now()], ['id' => (int) $user['id']]);
        Audit::admin('user.unblock', 'user', (int) $user['id'], 'Desbloqueou ' . $user['display_name']);
        Notifier::send((int) $user['id'], 'account_unblocked', 'Sua conta foi reativada', $user['role'] === 'creator' ? 'Reative os anúncios que deseja publicar em "Meus anúncios".' : null, $user['role'] === 'creator' ? '/painel/anuncios' : '/empresa');
        $this->success('Conta desbloqueada.');

        return $this->back('/admin');
    }

    public function verify(Request $request): Response
    {
        $user = $this->findUser((string) $request->param('id'));
        if ($user['role'] !== 'creator') {
            throw HttpException::notFound();
        }
        $verify = $request->input('value') === '1';
        Db::update('creators', [
            'is_verified' => $verify ? 1 : 0,
            'verified_at' => $verify ? now() : null,
            'verification_requested_at' => null,
            'updated_at' => now(),
        ], ['user_id' => (int) $user['id']]);
        Audit::admin($verify ? 'creator.verify' : 'creator.unverify', 'user', (int) $user['id'], ($verify ? 'Verificou ' : 'Removeu a verificação de ') . $user['display_name']);
        Notifier::send((int) $user['id'], 'verification', $verify ? 'Seu perfil agora é verificado' : 'Pedido de verificação não aprovado', null, '/painel/perfil');
        $this->success($verify ? 'Criador verificado.' : 'Verificação removida.');

        return $this->back('/admin/criadores');
    }

    public function admins(Request $request): Response
    {
        return $this->render('admin/users/admins', [
            'title' => 'Administradores',
            'admins' => Db::all(
                "SELECT u.uuid, u.email, u.status, u.admin_level, u.last_login_at, u.created_at, p.display_name
                 FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.role = 'admin' AND u.deleted_at IS NULL ORDER BY u.admin_level, p.display_name"
            ),
        ]);
    }

    public function storeAdmin(Request $request): Response
    {
        Gate::master();
        $data = $request->all();
        if ($response = $this->invalid($data, [
            'display_name' => 'required|min:3|max:80',
            'email' => 'required|email',
            'password' => 'required|password|max:120',
            'admin_level' => 'required|in:staff,master',
        ], ['display_name' => 'Nome', 'email' => 'E-mail', 'password' => 'Senha', 'admin_level' => 'Nível'], '/admin/administradores')) {
            return $response;
        }
        if (Accounts::emailTaken((string) $data['email'])) {
            Session::flash('errors', ['email' => ['Este e-mail já está em uso.']]);

            return $this->redirect('/admin/administradores');
        }
        $id = Accounts::create('admin', ['display_name' => $data['display_name'], 'email' => $data['email'], 'password' => $data['password'], 'email_verified_at' => now()], (string) $data['admin_level']);
        Audit::admin('admin.create', 'user', $id, 'Criou o administrador ' . $data['display_name'] . ' (' . $data['admin_level'] . ')');
        $this->success('Administrador criado.');

        return $this->redirect('/admin/administradores');
    }

    public function setAdminLevel(Request $request): Response
    {
        $master = Gate::master();
        $user = $this->findUser((string) $request->param('id'));
        if ($user['role'] !== 'admin' || (int) $user['id'] === $master->id) {
            throw new HttpException(422, 'Não é possível alterar o próprio nível.');
        }
        $level = $request->input('admin_level') === 'master' ? 'master' : 'staff';
        Db::update('users', ['admin_level' => $level, 'updated_at' => now()], ['id' => (int) $user['id']]);
        Audit::admin('admin.level', 'user', (int) $user['id'], 'Alterou ' . $user['display_name'] . ' para ' . $level);
        $this->success('Nível atualizado.');

        return $this->redirect('/admin/administradores');
    }

    /** @param array<int, string> $where @param array<string, mixed> $params */
    private function commonFilters(Request $request, array &$where, array &$params, ?string $extraName = null): void
    {
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where[] = '(LOWER(p.display_name) LIKE :q' . \App\Core\Db::ESCAPE . ' OR LOWER(u.email) LIKE :q2' . \App\Core\Db::ESCAPE . ($extraName ? " OR LOWER({$extraName}) LIKE :q3" . \App\Core\Db::ESCAPE : '') . ')';
            $params['q'] = $params['q2'] = Db::like($q);
            if ($extraName) {
                $params['q3'] = Db::like($q);
            }
        }
        $status = (string) $request->query('status', '');
        if (isset(status_map('user')[$status])) {
            $where[] = 'u.status = :st';
            $params['st'] = $status;
        }
        [$from, $to] = $this->period($request);
        if ($from) {
            $where[] = 'u.created_at >= :from';
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where[] = 'u.created_at <= :to';
            $params['to'] = $to . ' 23:59:59';
        }
    }

    private function order(Request $request, ?string $creatorAlias): string
    {
        return match ((string) $request->query('ordem', '')) {
            'antigos' => 'u.created_at ASC, u.id',
            'nome' => 'p.display_name ASC, u.id',
            'avaliacao' => $creatorAlias ? "{$creatorAlias}.rating_avg DESC, u.id" : 'u.created_at DESC, u.id',
            default => 'u.created_at DESC, u.id DESC',
        };
    }

    /** @return array<string, mixed> */
    private function findUser(string $uuid): array
    {
        $row = Db::first(
            'SELECT u.*, p.display_name, p.slug, p.avatar_path, p.phone, p.city, p.state, p.headline, p.bio,
                    cr.is_verified, cr.verification_requested_at, cr.rating_avg, cr.rating_count, cr.contracts_count, cr.hours_sold,
                    co.company_name, co.legal_name, co.document, co.responsible_name, co.website, co.phone AS company_phone
             FROM users u JOIN profiles p ON p.user_id = u.id
             LEFT JOIN creators cr ON cr.user_id = u.id LEFT JOIN companies co ON co.user_id = u.id
             WHERE u.uuid = :u AND u.deleted_at IS NULL',
            ['u' => $uuid]
        );

        return $row ?? throw HttpException::notFound('Usuário não encontrado.');
    }

    /** Staff admins manage creators and companies; only the master touches other administrators. */
    private function guardTarget(array $user): void
    {
        if ($user['role'] === 'admin') {
            $master = Gate::master();
            if ((int) $user['id'] === $master->id) {
                throw new HttpException(422, 'Você não pode bloquear a própria conta.');
            }
        }
    }
}
