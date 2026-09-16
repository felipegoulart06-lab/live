<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class FinanceAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function payments(Request $request): Response
    {
        if ($denied = $this->canOrDeny('finance.manage')) {
            return $denied;
        }
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->panel->orders($q, $status, $page);

        return $this->panel('admin/finance/payments', [
            'title' => 'Pagamentos',
            'snapshot' => $this->panel->financeSnapshot(),
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function withdrawals(Request $request): Response
    {
        if ($denied = $this->canOrDeny('finance.manage')) {
            return $denied;
        }
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->panel->withdrawals($status, $page);

        return $this->panel('admin/finance/withdrawals', [
            'title' => 'Saques',
            'snapshot' => $this->panel->financeSnapshot(),
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['status' => $status],
        ]);
    }

    public function reviewWithdrawal(Request $request): Response
    {
        if ($denied = $this->canOrDeny('finance.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->reviewWithdrawal($id, (string) $request->input('decision', ''), $this->actor());
        if ($result['ok']) {
            $this->audit('withdrawal.review', 'withdrawal', $id, null, ['decision' => $request->input('decision')]);
            $this->withSuccess('Saque atualizado.');
        } else {
            $this->withError($result['message'] ?? 'Falha.');
        }

        return $this->redirect('/admin/saques');
    }
}
