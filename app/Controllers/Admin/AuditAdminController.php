<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;

final class AuditAdminController extends AdminController
{
    public function __construct(private readonly AdminPanelRepository $panel = new AdminPanelRepository())
    {
    }

    public function index(Request $request): Response
    {
        if ($denied = $this->canOrDeny('settings.manage')) {
            return $denied;
        }
        $page = max(1, (int) $request->query('page', 1));
        $audits = $this->panel->audits($page);
        $logins = $this->panel->loginLogs(1);

        return $this->panel('admin/audit/index', [
            'title' => 'Auditoria',
            'audits' => $audits['rows'],
            'total' => $audits['total'],
            'page' => $audits['page'],
            'pages' => $audits['pages'],
            'logins' => $logins['rows'],
        ]);
    }
}
