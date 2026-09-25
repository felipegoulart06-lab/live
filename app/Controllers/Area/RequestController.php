<?php

declare(strict_types=1);

namespace App\Controllers\Area;

use App\Core\Db;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Deals;

final class RequestController extends AreaController
{
    public function index(Request $request): Response
    {
        Deals::expireStale();
        $user = $this->user();
        $owner = $this->ownerColumn();
        $status = (string) $request->query('status', '');
        $where = "WHERE r.{$owner} = :u";
        $params = ['u' => $user->id];
        if (isset(status_map('request')[$status])) {
            $where .= ' AND r.status = :s';
            $params['s'] = $status;
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where .= ' AND (LOWER(r.code) LIKE :q' . Db::ESCAPE . ' OR LOWER(l.title) LIKE :q2' . Db::ESCAPE . ' OR LOWER(r.theme) LIKE :q3' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = $params['q3'] = Db::like($q);
        }

        $result = Db::paginate(
            'r.uuid, r.code, r.hours, r.total_cents, r.status, r.theme, r.desired_date, r.expires_at, r.created_at,
             l.title AS listing_title, co.company_name, cp.display_name AS creator_name',
            "FROM requests r
             JOIN listings l ON l.id = r.listing_id
             JOIN companies co ON co.user_id = r.company_id
             JOIN profiles cp ON cp.user_id = r.creator_id
             {$where}",
            $params,
            $this->page($request),
            20,
            "CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC"
        );

        return $this->render('area/requests/index', ['title' => 'Solicitações', 'result' => $result, 'status' => $status, 'q' => $q]);
    }

    public function show(Request $request): Response
    {
        $row = Gate::ownRequest((string) $request->param('id'));
        $details = Db::first(
            'SELECT l.title AS listing_title, l.slug AS listing_slug, co.company_name, cop.display_name AS company_contact,
                    cp.display_name AS creator_name, cp.slug AS creator_slug, cp.avatar_path AS creator_avatar,
                    cv.uuid AS conversation_uuid, ct.uuid AS contract_uuid, ct.code AS contract_code
             FROM requests r
             JOIN listings l ON l.id = r.listing_id
             JOIN companies co ON co.user_id = r.company_id
             JOIN profiles cop ON cop.user_id = r.company_id
             JOIN profiles cp ON cp.user_id = r.creator_id
             LEFT JOIN conversations cv ON cv.id = r.conversation_id
             LEFT JOIN contracts ct ON ct.request_id = r.id
             WHERE r.id = :id',
            ['id' => (int) $row['id']]
        );

        return $this->render('area/requests/show', [
            'title' => 'Solicitação ' . $row['code'],
            'req' => $row + $details,
            'addons' => $row['addons_json'] ? (json_decode((string) $row['addons_json'], true) ?: []) : [],
        ]);
    }

    public function accept(Request $request): Response
    {
        $user = $this->user();
        $row = Gate::ownRequest((string) $request->param('id'));
        $contractUuid = Deals::acceptRequest($row, $user->id);
        $this->success('Solicitação aceita. O contrato foi criado e aguarda o pagamento da empresa.');

        return $this->redirect('/painel/contratos/' . $contractUuid);
    }

    public function decline(Request $request): Response
    {
        $user = $this->user();
        $row = Gate::ownRequest((string) $request->param('id'));
        $reason = trim((string) $request->input('reason', ''));
        if ($response = $this->invalid(['reason' => $reason], ['reason' => 'required|min:5|max:500'], ['reason' => 'Motivo da recusa'], '/painel/solicitacoes/' . $row['uuid'])) {
            return $response;
        }
        Deals::declineRequest($row, $user->id, $reason);
        $this->success('Solicitação recusada. A empresa foi avisada.');

        return $this->redirect('/painel/solicitacoes/' . $row['uuid']);
    }

    public function cancel(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden();
        }
        $row = Gate::ownRequest((string) $request->param('id'));
        Deals::cancelRequest($row, $user->id);
        $this->success('Solicitação cancelada.');

        return $this->redirect('/empresa/solicitacoes/' . $row['uuid']);
    }
}
