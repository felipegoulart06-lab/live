<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FreelancerAdminRepository;
use App\Services\FreelancerAdminService;

final class FreelancerController extends Controller
{
    public function __construct(
        private readonly FreelancerAdminRepository $freelancers = new FreelancerAdminRepository(),
        private readonly FreelancerAdminService $service = new FreelancerAdminService()
    ) {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $verified = (string) $request->query('verificado', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->freelancers->paginate($q, $status, $verified, $page);

        return $this->view('admin/freelancers/index', [
            'title' => 'Profissionais',
            'user' => $this->requireUser(),
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'filters' => ['q' => $q, 'status' => $status, 'verificado' => $verified],
        ], 'layouts/admin');
    }

    public function create(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return $this->formView(null);
    }

    public function store(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }
        $result = $this->service->create($request->all(), $request->files());
        if (!($result['ok'] ?? false)) {
            $this->withErrors($result['errors'] ?? [], $request->all());
            if (isset($result['message'])) {
                $this->withError($result['message']);
            }

            return $this->redirect('/admin/profissionais/novo');
        }

        $this->withSuccess('Profissional cadastrado.');

        return $this->redirect('/admin/profissionais/' . $result['id'] . '/editar');
    }

    public function edit(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }
        $freelancer = $this->freelancers->findForAdmin((int) $request->param('id'));
        if (!$freelancer || empty($freelancer['is_freelancer'])) {
            $this->withError('Profissional não encontrado.');

            return $this->redirect('/admin/profissionais');
        }

        return $this->formView($freelancer);
    }

    public function update(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }
        $id = (int) $request->param('id');
        $result = $this->service->update($id, $request->all(), $request->files());
        if (!($result['ok'] ?? false)) {
            $this->withErrors($result['errors'] ?? [], $request->all());
            if (isset($result['message'])) {
                $this->withError($result['message']);
            }

            return $this->redirect('/admin/profissionais/' . $id . '/editar');
        }

        $this->withSuccess('Profissional atualizado.');

        return $this->redirect('/admin/profissionais/' . $id . '/editar');
    }

    public function deactivate(Request $request): Response
    {
        if ($denied = $this->deny()) {
            return $denied;
        }
        $result = $this->service->deactivate((int) $request->param('id'));
        if ($result['ok']) {
            $this->withSuccess($result['message']);
        } else {
            $this->withError($result['message']);
        }

        return $this->redirect('/admin/profissionais');
    }

    private function deny(): ?Response
    {
        if ($this->requireUser()->can('users.manage')) {
            return null;
        }

        return Response::view('pages/errors/403', [
            'title' => 'Acesso negado',
            'authUser' => $this->user(),
            'csrf' => csrf_token(),
            'user' => $this->user(),
        ], 'layouts/admin', 403);
    }

    /** @param array<string, mixed>|null $freelancer */
    private function formView(?array $freelancer): Response
    {
        return $this->view('admin/freelancers/form', [
            'title' => $freelancer ? 'Editar profissional' : 'Novo profissional',
            'user' => $this->requireUser(),
            'freelancer' => $freelancer,
            'badges' => $this->freelancers->allBadges(),
            'selectedBadges' => $freelancer ? $this->freelancers->badgeIds((int) $freelancer['id']) : [],
            'skillsText' => $freelancer ? implode(', ', $this->freelancers->skillNames((int) $freelancer['id'])) : '',
            'commissionPercent' => $freelancer ? $this->freelancers->commissionPercent((int) $freelancer['id']) : '',
        ], 'layouts/admin');
    }
}
