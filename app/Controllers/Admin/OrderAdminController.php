<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class OrderAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->canOrDeny('orders.view')) {
            return $denied;
        }
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->panel->orders($q, $status, $page);

        return $this->panel('admin/orders/index', [
            'title' => 'Pedidos',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function show(Request $request): Response
    {
        if ($denied = $this->canOrDeny('orders.view')) {
            return $denied;
        }
        $row = $this->panel->order((int) $request->param('id'));
        if (!$row) {
            $this->withError('Pedido não encontrado.');

            return $this->redirect('/admin/pedidos');
        }

        return $this->panel('admin/orders/show', [
            'title' => 'Pedido ' . $row['public_code'],
            'order' => $row,
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        if ($denied = $this->canOrDeny('orders.view')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->setOrderStatus($id, (string) $request->input('status', ''));
        if ($result['ok']) {
            $this->audit('order.status', 'order', $id, null, ['status' => $request->input('status')]);
            $this->withSuccess('Pedido atualizado.');
        } else {
            $this->withError($result['message'] ?? 'Falha.');
        }

        return $this->redirect('/admin/pedidos/' . $id);
    }
}
