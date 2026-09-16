<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class UserAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->canOrDeny('users.view')) {
            return $denied;
        }
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $role = (string) $request->query('papel', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->panel->users($q, $status, $role, $page);

        return $this->panel('admin/users/index', [
            'title' => 'Usuários',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status, 'papel' => $role],
            'roles' => $this->panel->allRoles(),
        ]);
    }

    public function edit(Request $request): Response
    {
        if ($denied = $this->canOrDeny('users.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $row = $this->panel->user($id);
        if (!$row) {
            $this->withError('Usuário não encontrado.');

            return $this->redirect('/admin/usuarios');
        }

        return $this->panel('admin/users/form', [
            'title' => 'Conta · ' . ($row['display_name'] ?? $row['email']),
            'account' => $row,
            'roleSlugs' => $this->panel->userRoleSlugs($id),
            'roles' => $this->panel->allRoles(),
        ]);
    }

    public function update(Request $request): Response
    {
        if ($denied = $this->canOrDeny('users.manage')) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->ops->updateUser($id, $request->all(), $this->actor());
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Não foi possível salvar.');

            return $this->redirect('/admin/usuarios/' . $id . '/editar');
        }
        $this->audit('user.update', 'user', $id, null, $request->all());
        $this->withSuccess('Usuário atualizado.');

        return $this->redirect('/admin/usuarios/' . $id . '/editar');
    }
}
