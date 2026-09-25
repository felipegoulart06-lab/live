<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Queries\ContractQueries;
use App\Services\Audit;
use App\Services\Deals;

/** Requests, contracts, payments and support access to conversations. */
final class DealController extends AdminController
{
    public function requests(Request $request): Response
    {
        Deals::expireStale();
        [$where, $params] = $this->filters($request, 'r', 'request', ['r.code', 'l.title', 'co.company_name', 'cp.display_name']);

        return $this->render('admin/deals/requests', [
            'title' => 'Solicitações',
            'result' => Db::paginate(
                'r.uuid, r.code, r.hours, r.total_cents, r.status, r.created_at, r.expires_at, l.title AS listing_title, co.company_name, cp.display_name AS creator_name, ct.uuid AS contract_uuid',
                "FROM requests r JOIN listings l ON l.id = r.listing_id JOIN companies co ON co.user_id = r.company_id JOIN profiles cp ON cp.user_id = r.creator_id
                 LEFT JOIN contracts ct ON ct.request_id = r.id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                'r.created_at DESC, r.id DESC'
            ),
        ]);
    }

    public function contracts(Request $request): Response
    {
        [$where, $params] = $this->filters($request, 'c', 'contract', ['c.code', 'l.title', 'co.company_name', 'cp.display_name']);

        return $this->render('admin/deals/contracts', [
            'title' => 'Contratos',
            'result' => Db::paginate(
                'c.uuid, c.code, c.hours, c.total_cents, c.fee_cents, c.status, c.scheduled_date, c.created_at, l.title AS listing_title, co.company_name, cp.display_name AS creator_name',
                "FROM contracts c JOIN listings l ON l.id = c.listing_id JOIN companies co ON co.user_id = c.company_id JOIN profiles cp ON cp.user_id = c.creator_id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                'c.created_at DESC, c.id DESC'
            ),
        ]);
    }

    public function contract(Request $request): Response
    {
        $row = $this->findByUuid('contracts', (string) $request->param('id'), 'Contrato não encontrado.');

        return $this->render('admin/deals/contract', ContractQueries::detail($row) + [
            'title' => 'Contrato ' . $row['code'],
            'people' => Db::first(
                'SELECT uc.uuid AS company_uuid, ur.uuid AS creator_uuid FROM users uc, users ur WHERE uc.id = :c AND ur.id = :r',
                ['c' => (int) $row['company_id'], 'r' => (int) $row['creator_id']]
            ),
        ]);
    }

    public function confirmPayment(Request $request): Response
    {
        Gate::master();
        $row = $this->findByUuid('contracts', (string) $request->param('id'), 'Contrato não encontrado.');
        $method = (string) $request->input('method', '');
        $reference = trim((string) $request->input('reference', ''));
        if (!in_array($method, ['pix', 'boleto', 'cartao', 'transferencia'], true) || mb_strlen($reference) > 120) {
            throw new HttpException(422, 'Escolha a forma de pagamento e informe uma referência de até 120 caracteres.');
        }
        Deals::confirmPayment($row, $this->user()->id, $method, $reference ?: null, 'manual');
        Audit::admin('payment.confirm', 'contract', (int) $row['id'], 'Registrou o pagamento do contrato ' . $row['code'], ['method' => $method, 'reference' => $reference]);
        $this->success('Pagamento registrado. O contrato está confirmado.');

        return $this->redirect('/admin/contratos/' . $row['uuid']);
    }

    public function cancelContract(Request $request): Response
    {
        $row = $this->findByUuid('contracts', (string) $request->param('id'), 'Contrato não encontrado.');
        $reason = $this->reason($request, 'reason', 5);
        if ($reason === null) {
            throw new HttpException(422, 'Informe o motivo do cancelamento (mínimo de 5 caracteres).');
        }
        if (in_array($row['status'], ['confirmed', 'in_progress'], true)) {
            Gate::master();
        }
        Deals::cancel($row, $this->user()->id, $reason, true);
        $this->success(in_array($row['status'], ['confirmed', 'in_progress'], true) ? 'Contrato cancelado e pagamento marcado como reembolsado.' : 'Contrato cancelado.');

        return $this->redirect('/admin/contratos/' . $row['uuid']);
    }

    public function note(Request $request): Response
    {
        $row = $this->findByUuid('contracts', (string) $request->param('id'), 'Contrato não encontrado.');
        $note = $this->reason($request, 'note', 3);
        if ($note === null) {
            throw new HttpException(422, 'Escreva a observação (3 a 1000 caracteres).');
        }
        Deals::event((int) $row['id'], $this->user()->id, 'note', $note);
        Audit::admin('contract.note', 'contract', (int) $row['id'], 'Adicionou observação ao contrato ' . $row['code']);
        $this->success('Observação registrada na linha do tempo.');

        return $this->redirect('/admin/contratos/' . $row['uuid']);
    }

    public function payments(Request $request): Response
    {
        [$where, $params] = $this->filters($request, 'pay', 'payment', ['c.code', 'co.company_name', 'cp.display_name']);
        $totals = [];
        foreach (Db::all('SELECT status, COUNT(*) AS total, COALESCE(SUM(amount_cents), 0) AS amount, COALESCE(SUM(net_cents), 0) AS net FROM payments GROUP BY status') as $row) {
            $totals[(string) $row['status']] = $row;
        }

        return $this->render('admin/deals/payments', [
            'title' => 'Pagamentos',
            'totals' => $totals,
            'result' => Db::paginate(
                'pay.uuid, pay.amount_cents, pay.fee_cents, pay.net_cents, pay.status, pay.method, pay.gateway_reference, pay.paid_at, pay.paid_out_at, pay.created_at,
                 c.uuid AS contract_uuid, c.code, co.company_name, cp.display_name AS creator_name',
                "FROM payments pay JOIN contracts c ON c.id = pay.contract_id JOIN companies co ON co.user_id = pay.company_id JOIN profiles cp ON cp.user_id = pay.creator_id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                'pay.created_at DESC, pay.id DESC'
            ),
        ]);
    }

    public function payout(Request $request): Response
    {
        Gate::master();
        $payment = $this->findByUuid('payments', (string) $request->param('id'), 'Pagamento não encontrado.');
        $reference = mb_substr(trim((string) $request->input('reference', '')), 0, 120);
        Deals::registerPayout($payment, $reference);
        $this->success('Repasse registrado.');

        return $this->back('/admin/pagamentos');
    }

    public function conversations(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $where = 'cv.last_message_at IS NOT NULL';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (LOWER(co.company_name) LIKE :q' . Db::ESCAPE . ' OR LOWER(cp.display_name) LIKE :q2' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = Db::like($q);
        }

        return $this->render('admin/deals/conversations', [
            'title' => 'Mensagens',
            'q' => $q,
            'result' => Db::paginate(
                'cv.uuid, cv.last_message_at, co.company_name, cp.display_name AS creator_name, l.title AS listing_title,
                 (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = cv.id) AS messages_count',
                "FROM conversations cv JOIN companies co ON co.user_id = cv.company_id JOIN profiles cp ON cp.user_id = cv.creator_id
                 LEFT JOIN listings l ON l.id = cv.listing_id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                'cv.last_message_at DESC'
            ),
        ]);
    }

    /** Support access: read-only and always written to the audit log. */
    public function conversation(Request $request): Response
    {
        $conversation = $this->findByUuid('conversations', (string) $request->param('id'), 'Conversa não encontrada.');
        $reason = $this->reason($request, 'motivo', 5);
        $info = Db::first(
            'SELECT co.company_name, cp.display_name AS creator_name, l.title AS listing_title FROM conversations cv
             JOIN companies co ON co.user_id = cv.company_id JOIN profiles cp ON cp.user_id = cv.creator_id LEFT JOIN listings l ON l.id = cv.listing_id
             WHERE cv.id = :id',
            ['id' => (int) $conversation['id']]
        );
        if ($reason === null) {
            return $this->render('admin/deals/conversation-gate', ['title' => 'Acesso à conversa', 'conversation' => $conversation + $info]);
        }
        Audit::admin('conversation.view', 'conversation', (int) $conversation['id'], 'Leu a conversa entre ' . $info['company_name'] . ' e ' . $info['creator_name'], ['reason' => $reason]);

        return $this->render('admin/deals/conversation', [
            'title' => 'Conversa',
            'conversation' => $conversation + $info,
            'reason' => $reason,
            'messages' => Db::all(
                'SELECT m.body, m.created_at, m.sender_id, p.display_name AS sender_name, u.role AS sender_role
                 FROM messages m JOIN profiles p ON p.user_id = m.sender_id JOIN users u ON u.id = m.sender_id
                 WHERE m.conversation_id = :c ORDER BY m.created_at, m.id LIMIT 500',
                ['c' => (int) $conversation['id']]
            ),
        ]);
    }

    /** @param array<int, string> $searchColumns @return array{0: string, 1: array<string, mixed>} */
    private function filters(Request $request, string $alias, string $domain, array $searchColumns): array
    {
        $where = ['1 = 1'];
        $params = [];
        $status = (string) $request->query('status', '');
        if (isset(status_map($domain)[$status])) {
            $where[] = "{$alias}.status = :st";
            $params['st'] = $status;
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $parts = [];
            foreach ($searchColumns as $i => $column) {
                $parts[] = "LOWER({$column}) LIKE :q{$i}" . Db::ESCAPE;
                $params['q' . $i] = Db::like($q);
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        [$from, $to] = $this->period($request);
        if ($from) {
            $where[] = "{$alias}.created_at >= :from";
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where[] = "{$alias}.created_at <= :to";
            $params['to'] = $to . ' 23:59:59';
        }

        return [implode(' AND ', $where), $params];
    }
}
