<?php

declare(strict_types=1);

namespace App\Controllers\Creator;

use App\Controllers\Area\AreaController;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;

final class FinanceController extends AreaController
{
    public function index(Request $request): Response
    {
        $uid = $this->user()->id;
        $status = (string) $request->query('status', '');
        $where = 'WHERE pay.creator_id = :u';
        $params = ['u' => $uid];
        if (isset(status_map('payment')[$status])) {
            $where .= ' AND pay.status = :s';
            $params['s'] = $status;
        }

        $summary = Db::first(
            "SELECT COALESCE(SUM(CASE WHEN status = 'pending' THEN net_cents ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN net_cents ELSE 0 END), 0) AS paid,
                    COALESCE(SUM(CASE WHEN status = 'awaiting_payout' THEN net_cents ELSE 0 END), 0) AS awaiting_payout,
                    COALESCE(SUM(CASE WHEN status = 'paid_out' THEN net_cents ELSE 0 END), 0) AS paid_out,
                    COALESCE(SUM(CASE WHEN status IN ('paid', 'awaiting_payout', 'paid_out') THEN fee_cents ELSE 0 END), 0) AS fees
             FROM payments WHERE creator_id = :u",
            ['u' => $uid]
        );

        return $this->render('creator/finance', [
            'title' => 'Financeiro',
            'summary' => $summary,
            'status' => $status,
            'result' => Db::paginate(
                'pay.uuid, pay.amount_cents, pay.fee_cents, pay.net_cents, pay.status, pay.paid_at, pay.paid_out_at, pay.created_at, c.code, c.uuid AS contract_uuid, co.company_name',
                "FROM payments pay JOIN contracts c ON c.id = pay.contract_id JOIN companies co ON co.user_id = pay.company_id {$where}",
                $params,
                $this->page($request),
                20,
                'pay.created_at DESC, pay.id DESC'
            ),
        ]);
    }
}
