<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class PageAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/pages/index', [
            'title' => 'Páginas',
            'rows' => $this->panel->pages(),
        ]);
    }

    public function create(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/pages/form', ['title' => 'Nova página', 'page' => null]);
    }

    public function store(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $result = $this->ops->savePage(null, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Falha.');

            return $this->redirect('/admin/paginas/nova');
        }
        $this->audit('page.create', 'page', (int) $result['id']);
        $this->withSuccess('Página criada.');

        return $this->redirect('/admin/paginas/' . $result['id'] . '/editar');
    }

    public function edit(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $row = $this->panel->page((int) $request->param('id'));
        if (!$row) {
            $this->withError('Página não encontrada.');

            return $this->redirect('/admin/paginas');
        }

        return $this->panel('admin/pages/form', ['title' => 'Editar página', 'page' => $row]);
    }

    public function update(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->savePage($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Falha.');

            return $this->redirect('/admin/paginas/' . $id . '/editar');
        }
        $this->audit('page.update', 'page', $id);
        $this->withSuccess('Página atualizada.');

        return $this->redirect('/admin/paginas/' . $id . '/editar');
    }

    public function destroy(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $this->ops->deleteRow('pages', $id);
        $this->audit('page.delete', 'page', $id);
        $this->withSuccess('Página excluída.');

        return $this->redirect('/admin/paginas');
    }
}
