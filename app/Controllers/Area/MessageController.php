<?php

declare(strict_types=1);

namespace App\Controllers\Area;

use App\Core\Db;
use App\Core\Gate;
use App\Core\Request;
use App\Core\Response;
use App\Services\Messages;

final class MessageController extends AreaController
{
    public function index(Request $request): Response
    {
        return $this->render('area/messages', $this->listData($request, null) + ['title' => 'Mensagens']);
    }

    public function show(Request $request): Response
    {
        $user = $this->user();
        $conversation = Gate::ownConversation((string) $request->param('id'));
        Messages::markRead($conversation, $user->id);

        $messages = array_reverse(Db::all(
            'SELECT m.id, m.body, m.created_at, m.sender_id, p.display_name AS sender_name
             FROM messages m JOIN profiles p ON p.user_id = m.sender_id
             WHERE m.conversation_id = :c ORDER BY m.created_at DESC, m.id DESC LIMIT 200',
            ['c' => (int) $conversation['id']]
        ));
        $header = Db::first(
            'SELECT co.company_name, cp.display_name AS creator_name, cp.slug AS creator_slug, l.title AS listing_title, l.slug AS listing_slug
             FROM conversations cv
             JOIN companies co ON co.user_id = cv.company_id
             JOIN profiles cp ON cp.user_id = cv.creator_id
             LEFT JOIN listings l ON l.id = cv.listing_id
             WHERE cv.id = :id',
            ['id' => (int) $conversation['id']]
        );

        return $this->render('area/messages', $this->listData($request, (int) $conversation['id']) + [
            'title' => 'Mensagens',
            'conversation' => $conversation + $header,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request): Response
    {
        $user = $this->user();
        $conversation = Gate::ownConversation((string) $request->param('id'));
        $this->throttle('message:' . $user->id, 30, 60);
        $body = trim((string) $request->input('body', ''));
        $back = $this->area() . '/mensagens/' . $conversation['uuid'];
        if ($response = $this->invalid(['body' => $body], ['body' => 'required|max:2000'], ['body' => 'Mensagem'], $back)) {
            return $response;
        }
        Messages::post((int) $conversation['id'], $user->id, $body);

        return $this->redirect($back . '#fim');
    }

    /** @return array<string, mixed> */
    private function listData(Request $request, ?int $activeId): array
    {
        $user = $this->user();
        [$mine, $read] = $user->isCreator() ? ['creator_id', 'creator_read_at'] : ['company_id', 'company_read_at'];
        $result = Db::paginate(
            "cv.id, cv.uuid, cv.last_message_at, cv.{$read} AS my_read_at, co.company_name, cp.display_name AS creator_name, cp.avatar_path AS creator_avatar,
             l.title AS listing_title,
             (SELECT m.body FROM messages m WHERE m.conversation_id = cv.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_body",
            "FROM conversations cv
             JOIN companies co ON co.user_id = cv.company_id
             JOIN profiles cp ON cp.user_id = cv.creator_id
             LEFT JOIN listings l ON l.id = cv.listing_id
             WHERE cv.{$mine} = :u AND cv.last_message_at IS NOT NULL",
            ['u' => $user->id],
            $this->page($request),
            30,
            'cv.last_message_at DESC'
        );

        return ['result' => $result, 'activeId' => $activeId];
    }
}
