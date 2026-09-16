<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $pdo = Database::pdo();
        $panel = new AdminPanelRepository();
        $stats = [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
            'freelancers' => (int) $pdo->query("SELECT COUNT(*) FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE r.slug = 'freelancer'")->fetchColumn(),
            'clients' => (int) $pdo->query("SELECT COUNT(*) FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE r.slug = 'client'")->fetchColumn(),
            'services' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
            'published_services' => (int) $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'published'")->fetchColumn(),
            'pending_services' => (int) $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'pending_review'")->fetchColumn(),
            'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'orders_completed' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
            'orders_cancelled' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
            'projects' => (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
            'proposals' => (int) $pdo->query('SELECT COUNT(*) FROM proposals')->fetchColumn(),
            'withdrawals_pending' => (int) $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status IN ('requested','reviewing')")->fetchColumn(),
        ];

        return $this->panel('admin/dashboard', [
            'title' => 'Painel master',
            'stats' => $stats,
            'finance' => $panel->financeSnapshot(),
            'recentUsers' => $panel->recentUsers(),
            'recentOrders' => $panel->recentOrders(),
        ]);
    }
}
