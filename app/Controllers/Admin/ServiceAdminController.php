<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class ServiceAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->canOrDeny('services.moderate')) {
            return $denied;
        }
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->panel->services($q, $status, $page);

        return $this->panel('admin/services/index', [
            'title' => 'Serviços',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        if ($denied = $this->canOrDeny('services.moderate')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->setServiceStatus($id, (string) $request->input('status', ''));
        $this->withSuccess($result['ok'] ? 'Status do serviço atualizado.' : ($result['message'] ?? 'Falha.'));
        if ($result['ok']) {
            $this->audit('service.status', 'service', $id, null, ['status' => $request->input('status')]);
        }

        return $this->redirect('/admin/servicos');
    }

    public function toggleFeatured(Request $request): Response
    {
        if ($denied = $this->canOrDeny('services.moderate')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $this->ops->toggleServiceFeatured($id);
        $this->audit('service.featured', 'service', $id);
        $this->withSuccess('Destaque atualizado.');

        return $this->redirect('/admin/servicos');
    }
}
