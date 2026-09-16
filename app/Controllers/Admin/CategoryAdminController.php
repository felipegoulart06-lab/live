<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class CategoryAdminController extends AdminController
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

        return $this->panel('admin/categories/index', [
            'title' => 'Categorias',
            'rows' => $this->panel->categories(),
        ]);
    }

    public function create(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/categories/form', [
            'title' => 'Nova categoria',
            'category' => null,
            'subs' => [],
        ]);
    }

    public function store(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $result = $this->ops->saveCategory(null, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Falha.');
            $this->withErrors([], $request->all());

            return $this->redirect('/admin/categorias/nova');
        }
        $this->audit('category.create', 'category', (int) $result['id']);
        $this->withSuccess('Categoria criada.');

        return $this->redirect('/admin/categorias/' . $result['id'] . '/editar');
    }

    public function edit(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $row = $this->panel->category($id);
        if (!$row) {
            $this->withError('Categoria não encontrada.');

            return $this->redirect('/admin/categorias');
        }

        return $this->panel('admin/categories/form', [
            'title' => 'Editar categoria',
            'category' => $row,
            'subs' => $this->panel->subcategories($id),
        ]);
    }

    public function update(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->saveCategory($id, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Falha.');

            return $this->redirect('/admin/categorias/' . $id . '/editar');
        }
        $this->audit('category.update', 'category', $id);
        $this->withSuccess('Categoria atualizada.');

        return $this->redirect('/admin/categorias/' . $id . '/editar');
    }

    public function storeSub(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->saveSubcategory($id, null, $request->all());
        $this->withSuccess($result['ok'] ? 'Subcategoria adicionada.' : ($result['message'] ?? 'Falha.'));

        return $this->redirect('/admin/categorias/' . $id . '/editar');
    }

    public function deleteSub(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $this->ops->deleteRow('subcategories', (int) $request->param('sub'));
        $this->withSuccess('Subcategoria removida.');

        return $this->redirect('/admin/categorias/' . (int) $request->param('id') . '/editar');
    }
}
