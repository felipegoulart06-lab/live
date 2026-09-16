<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class ProjectAdminController extends AdminController
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
        $result = $this->panel->projects($q, $status, $page);

        return $this->panel('admin/projects/index', [
            'title' => 'Projetos',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        if ($denied = $this->canOrDeny('orders.view')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->setProjectStatus($id, (string) $request->input('status', ''));
        $this->withSuccess($result['ok'] ? 'Projeto atualizado.' : ($result['message'] ?? 'Falha.'));
        if ($result['ok']) {
            $this->audit('project.status', 'project', $id);
        }

        return $this->redirect('/admin/projetos');
    }
}
