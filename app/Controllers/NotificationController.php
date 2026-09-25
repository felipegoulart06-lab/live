<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Shared by every role; rows are always filtered by the signed-in user. */
final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user();

        return $this->view('area/notifications', [
            'title' => 'Notificações',
            'result' => Db::paginate(
                'id, type, title, body, url, read_at, created_at',
                'FROM notifications WHERE user_id = :u',
                ['u' => $user->id],
                $this->page($request),
                25,
                'created_at DESC, id DESC'
            ),
        ], 'layouts/panel');
    }

    public function open(Request $request): Response
    {
        $user = $this->user();
        $row = Db::first('SELECT id, url FROM notifications WHERE id = :id AND user_id = :u', ['id' => (int) $request->param('id'), 'u' => $user->id]);
        if (!$row) {
            throw HttpException::notFound('Notificação não encontrada.');
        }
        Db::run('UPDATE notifications SET read_at = :now WHERE id = :id AND read_at IS NULL', ['now' => now(), 'id' => (int) $row['id']]);
        $target = (string) ($row['url'] ?? '');

        return $this->redirect(str_starts_with($target, '/') && !str_starts_with($target, '//') ? $target : '/notificacoes');
    }

    public function readAll(Request $request): Response
    {
        $user = $this->user();
        Db::run('UPDATE notifications SET read_at = :now WHERE user_id = :u AND read_at IS NULL', ['now' => now(), 'u' => $user->id]);
        $this->success('Todas as notificações foram marcadas como lidas.');

        return $this->redirect('/notificacoes');
    }
}
