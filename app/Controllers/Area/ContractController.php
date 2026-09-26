<?php

declare(strict_types=1);

namespace App\Controllers\Area;

use App\Core\Db;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Storage;
use App\Queries\ContractQueries;
use App\Services\Deals;
use App\Services\MercadoPago;

final class ContractController extends AreaController
{
    public function index(Request $request): Response
    {
        $user = $this->user();
        $owner = $this->ownerColumn();
        $status = (string) $request->query('status', '');
        $where = "WHERE c.{$owner} = :u";
        $params = ['u' => $user->id];
        if (isset(status_map('contract')[$status])) {
            $where .= ' AND c.status = :s';
            $params['s'] = $status;
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where .= ' AND (LOWER(c.code) LIKE :q' . Db::ESCAPE . ' OR LOWER(l.title) LIKE :q2' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = Db::like($q);
        }

        return $this->render('area/contracts/index', [
            'title' => 'Contratos',
            'status' => $status,
            'q' => $q,
            'result' => Db::paginate(
                'c.uuid, c.code, c.hours, c.total_cents, c.creator_amount_cents, c.status, c.scheduled_date, c.scheduled_time, c.created_at,
                 l.title AS listing_title, co.company_name, cp.display_name AS creator_name',
                "FROM contracts c JOIN listings l ON l.id = c.listing_id JOIN companies co ON co.user_id = c.company_id JOIN profiles cp ON cp.user_id = c.creator_id {$where}",
                $params,
                $this->page($request),
                20,
                "CASE WHEN c.status IN ('awaiting_payment', 'confirmed', 'in_progress') THEN 0 ELSE 1 END, c.created_at DESC"
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $row = Gate::ownContract((string) $request->param('id'));
        if ($this->user()->isCompany() && $row['status'] === 'awaiting_payment') {
            return $this->redirect('/empresa/contratos/' . $row['uuid'] . '/checkout');
        }
        $company = $this->user()->isCompany()
            ? \App\Core\Db::first('SELECT document FROM companies WHERE user_id = :u', ['u' => $this->user()->id])
            : null;

        return $this->render('area/contracts/show', ContractQueries::detail($row) + [
            'title' => 'Contrato ' . $row['code'],
            'mpEnabled' => MercadoPago::configured() && $this->user()->isCompany(),
            'mpPublicKey' => MercadoPago::publicKey(),
            'payerEmail' => $this->user()->email,
            'payerDocument' => MercadoPago::sanitizeDocument((string) ($company['document'] ?? '')),
        ]);
    }

    public function checkout(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden();
        }
        $row = Gate::ownContract((string) $request->param('id'));
        if ($row['status'] !== 'awaiting_payment') {
            return $this->redirect('/empresa/contratos/' . $row['uuid']);
        }
        $company = Db::first('SELECT document FROM companies WHERE user_id = :u', ['u' => $user->id]);
        $detail = ContractQueries::detail($row);

        return $this->view('area/contracts/pay', $detail + [
            'title' => 'Pagar ' . $row['code'],
            'noindex' => true,
            'mpEnabled' => MercadoPago::configured(),
            'mpPublicKey' => MercadoPago::publicKey(),
            'payerEmail' => $user->email,
            'payerDocument' => MercadoPago::sanitizeDocument((string) ($company['document'] ?? '')),
        ], 'layouts/public');
    }

    public function start(Request $request): Response
    {
        $row = Gate::ownContract((string) $request->param('id'));
        Deals::start($row, $this->user()->id);
        $this->success('Contrato marcado como em andamento.');

        return $this->redirect('/painel/contratos/' . $row['uuid']);
    }

    public function complete(Request $request): Response
    {
        $row = Gate::ownContract((string) $request->param('id'));
        $note = trim((string) $request->input('note', ''));
        if ($response = $this->invalid(['note' => $note], ['note' => 'max:1000'], ['note' => 'Observação'], '/painel/contratos/' . $row['uuid'])) {
            return $response;
        }
        Deals::complete($row, $this->user()->id, $note ?: null);
        $this->success('Contrato concluído. O valor entra em "aguardando repasse".');

        return $this->redirect('/painel/contratos/' . $row['uuid']);
    }

    public function schedule(Request $request): Response
    {
        $row = Gate::ownContract((string) $request->param('id'));
        $data = ['scheduled_date' => (string) $request->input('scheduled_date', ''), 'scheduled_time' => (string) $request->input('scheduled_time', '')];
        if ($response = $this->invalid($data, ['scheduled_date' => 'required|date|future', 'scheduled_time' => 'time'], ['scheduled_date' => 'Data', 'scheduled_time' => 'Horário'], '/painel/contratos/' . $row['uuid'])) {
            return $response;
        }
        Deals::schedule($row, $this->user()->id, $data['scheduled_date'], $data['scheduled_time'] ?: null);
        $this->success('Agenda atualizada. A empresa foi avisada.');

        return $this->redirect('/painel/contratos/' . $row['uuid']);
    }

    public function cancel(Request $request): Response
    {
        $row = Gate::ownContract((string) $request->param('id'));
        $reason = trim((string) $request->input('reason', ''));
        $back = $this->area() . '/contratos/' . $row['uuid'];
        if ($response = $this->invalid(['reason' => $reason], ['reason' => 'required|min:5|max:500'], ['reason' => 'Motivo'], $back)) {
            return $response;
        }
        Deals::cancel($row, $this->user()->id, $reason, false);
        $this->success('Contrato cancelado.');

        return $this->redirect($back);
    }

    public function pay(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden();
        }
        $this->throttle('pay:' . $user->id, 10, 300);
        $row = Gate::ownContract((string) $request->param('id'));
        $payment = Db::first('SELECT * FROM payments WHERE contract_id = :c ORDER BY id DESC LIMIT 1', ['c' => (int) $row['id']]);
        if (!$payment) {
            throw HttpException::notFound('Pagamento não encontrado.');
        }
        if (!MercadoPago::configured()) {
            throw new HttpException(503, 'Pagamento online ainda não está disponível.');
        }

        $input = $request->all();
        $doc = MercadoPago::sanitizeDocument((string) (($input['payer']['identification']['number'] ?? '') ?: ''));
        if ($doc === '') {
            $company = Db::first('SELECT document FROM companies WHERE user_id = :u', ['u' => $user->id]);
            $doc = MercadoPago::sanitizeDocument((string) ($company['document'] ?? ''));
            if ($doc !== '') {
                $input['payer']['identification'] = [
                    'type' => strlen($doc) === 14 ? 'CNPJ' : 'CPF',
                    'number' => $doc,
                ];
            }
        }

        try {
            $result = MercadoPago::charge($row, $payment, $input, $user->email, $request->ip());
        } catch (HttpException $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()], $e->status >= 400 ? $e->status : 422);
        } catch (\Throwable) {
            return Response::json(['ok' => false, 'message' => 'Não foi possível concluir o pagamento. Tente de novo.'], 502);
        }

        if (($result['status'] ?? '') === 'approved') {
            return Response::json([
                'ok' => true,
                'status' => 'approved',
                'redirect' => url('/empresa/contratos/' . $row['uuid']),
                'message' => 'Pagamento aprovado.',
            ]);
        }

        return Response::json([
            'ok' => ($result['status'] ?? '') === 'pending',
            'status' => $result['status'] ?? 'pending',
            'message' => $result['message'] ?? null,
            'pix' => $result['pix'] ?? null,
        ], ($result['status'] ?? '') === 'rejected' ? 422 : 200);
    }

    public function paymentStatus(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden();
        }
        $row = Gate::ownContract((string) $request->param('id'));
        $payment = Db::first('SELECT status, gateway_status, gateway_payload FROM payments WHERE contract_id = :c ORDER BY id DESC LIMIT 1', ['c' => (int) $row['id']]);
        $payload = json_decode((string) ($payment['gateway_payload'] ?? ''), true);

        return Response::json([
            'contract' => $row['status'],
            'payment' => $payment['status'] ?? null,
            'gateway' => $payment['gateway_status'] ?? null,
            'pix' => is_array($payload) && !empty($payload['qr_code']) ? [
                'qr_code' => $payload['qr_code'],
                'qr_code_base64' => $payload['qr_code_base64'] ?? '',
            ] : null,
        ]);
    }

    public function review(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden();
        }
        $row = Gate::ownContract((string) $request->param('id'));
        $data = ['rating' => (string) $request->input('rating', ''), 'comment' => trim((string) $request->input('comment', ''))];
        $back = '/empresa/contratos/' . $row['uuid'];
        if ($response = $this->invalid($data, ['rating' => 'required|between:1,5', 'comment' => 'required|min:10|max:1500'], ['rating' => 'Nota', 'comment' => 'Comentário'], $back)) {
            return $response;
        }
        Deals::review($row, $user->id, (int) $data['rating'], $data['comment']);
        $this->success('Obrigado pela avaliação.');

        return $this->redirect($back);
    }

    public function attach(Request $request): Response
    {
        $user = $this->user();
        $row = Gate::ownContract((string) $request->param('id'));
        $this->throttle('upload:' . $user->id, 60, 3600);
        $back = $this->area() . '/contratos/' . $row['uuid'] . '#arquivos';
        if (in_array($row['status'], ['cancelled'], true)) {
            throw new HttpException(422, 'Não é possível anexar arquivos a um contrato cancelado.');
        }
        if ((int) Db::value("SELECT COUNT(*) FROM attachments WHERE context = 'contract' AND context_id = :id", ['id' => (int) $row['id']]) >= 30) {
            throw new HttpException(422, 'Este contrato atingiu o limite de 30 arquivos.');
        }
        $file = $request->files()['file'] ?? [];
        $stored = Storage::store(is_array($file) ? $file : [], 'contracts/' . $row['uuid'], false, true);
        Db::insert('attachments', [
            'uuid' => uuid4(),
            'owner_id' => $user->id,
            'context' => 'contract',
            'context_id' => (int) $row['id'],
            'disk' => 'private',
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'created_at' => now(),
        ]);
        Deals::event((int) $row['id'], $user->id, 'attachment', $stored['original_name']);
        $this->success('Arquivo anexado.');

        return $this->redirect($back);
    }
}
