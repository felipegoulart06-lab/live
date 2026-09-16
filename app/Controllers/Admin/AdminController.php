<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Models\User;

abstract class AdminController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function panel(string $view, array $data = []): Response
    {
        $data['user'] = $this->requireUser();

        return $this->view($view, $data, 'layouts/admin');
    }

    protected function audit(string $action, string $type, ?int $id, mixed $old = null, mixed $new = null): void
    {
        $admin = $this->requireUser();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO audit_logs (admin_id, action, object_type, object_id, old_values, new_values, ip_address, created_at)
             VALUES (:admin_id, :action, :object_type, :object_id, :old_values, :new_values, :ip, :created_at)'
        );
        $stmt->execute([
            'admin_id' => $admin->id,
            'action' => $action,
            'object_type' => $type,
            'object_id' => $id,
            'old_values' => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
            'new_values' => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at' => now(),
        ]);
    }

    protected function actor(): User
    {
        return $this->requireUser();
    }

    protected function canOrDeny(string $permission): ?Response
    {
        if (Auth::can($permission)) {
            return null;
        }

        return Response::view('pages/errors/403', [
            'title' => 'Acesso negado',
            'authUser' => $this->user(),
            'csrf' => csrf_token(),
            'user' => $this->user(),
        ], 'layouts/admin', 403);
    }
}
