<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Queries\ListingQueries;
use App\Services\Audit;
use App\Services\Deals;
use App\Services\Messages;
use App\Services\Notifier;

/** Actions a signed-in company (or any user, for reports) takes from public pages. */
final class InteractionController extends Controller
{
    public function requestHours(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden('Apenas contas de empresa podem contratar horas.');
        }
        $this->throttle('request:' . $user->id, 10, 3600);
        $listing = $this->activeListing((string) $request->param('slug'));

        $data = [
            'package_id' => (string) $request->input('package_id', ''),
            'theme' => trim((string) $request->input('theme', '')),
            'briefing' => trim((string) $request->input('briefing', '')),
            'message' => trim((string) $request->input('message', '')),
            'desired_date' => (string) $request->input('desired_date', ''),
            'desired_time' => (string) $request->input('desired_time', ''),
        ];
        $back = '/anuncios/' . $listing['slug'] . '#contratar';
        if ($response = $this->invalid($data, [
            'package_id' => 'required|integer',
            'theme' => 'required|min:8|max:300',
            'briefing' => 'max:4000',
            'message' => 'max:2000',
            'desired_date' => 'date|future',
            'desired_time' => 'time',
        ], ['package_id' => 'Pacote', 'theme' => 'Tema do vídeo', 'briefing' => 'Briefing', 'message' => 'Mensagem', 'desired_date' => 'Data desejada', 'desired_time' => 'Horário'], $back)) {
            return $response;
        }

        $addonIds = array_values(array_filter(array_map('intval', (array) $request->input('addons', [])), static fn (int $id): bool => $id > 0));
        $created = Deals::createRequest($user->id, $listing, (int) $data['package_id'], $addonIds, $data);
        $this->success('Solicitação ' . $created['code'] . ' enviada. O criador tem até ' . setting('request_expiry_hours') . ' horas para responder.');

        return $this->redirect('/empresa/solicitacoes/' . $created['uuid']);
    }

    public function message(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            throw HttpException::forbidden('Apenas contas de empresa podem iniciar conversas com criadores.');
        }
        $this->throttle('conversation:' . $user->id, 20, 3600);
        $listing = $this->activeListing((string) $request->param('slug'));
        $body = trim((string) $request->input('body', ''));
        if ($response = $this->invalid(['body' => $body], ['body' => 'required|min:2|max:2000'], ['body' => 'Mensagem'], '/anuncios/' . $listing['slug'])) {
            return $response;
        }

        $conversationId = Messages::conversationFor($user->id, (int) $listing['creator_id'], (int) $listing['id']);
        Messages::post($conversationId, $user->id, $body);
        $uuid = (string) Db::value('SELECT uuid FROM conversations WHERE id = :id', ['id' => $conversationId]);
        $this->success('Mensagem enviada.');

        return $this->redirect('/empresa/mensagens/' . $uuid);
    }

    public function favorite(Request $request): Response
    {
        $user = $this->user();
        $type = (string) $request->input('type', '');
        $slug = (string) $request->input('slug', '');
        $this->throttle('favorite:' . $user->id, 60, 60);

        $targetId = match ($type) {
            'listing' => Db::value("SELECT id FROM listings WHERE slug = :s AND status = 'active' AND deleted_at IS NULL", ['s' => $slug]),
            'creator' => Db::value("SELECT p.user_id FROM profiles p JOIN users u ON u.id = p.user_id WHERE p.slug = :s AND u.role = 'creator' AND u.status = 'active'", ['s' => $slug]),
            default => null,
        };
        if (!$targetId) {
            throw HttpException::notFound();
        }
        $params = ['u' => $user->id, 't' => $type, 'id' => (int) $targetId];
        $exists = Db::value('SELECT id FROM favorites WHERE user_id = :u AND target_type = :t AND target_id = :id', $params);
        if ($exists) {
            Db::run('DELETE FROM favorites WHERE id = :id', ['id' => (int) $exists]);
            $this->success('Removido dos favoritos.');
        } else {
            Db::insert('favorites', ['user_id' => $user->id, 'target_type' => $type, 'target_id' => (int) $targetId, 'created_at' => now()]);
            $this->success('Salvo nos favoritos.');
        }

        return $this->back($type === 'listing' ? '/anuncios/' . $slug : '/criadores/' . $slug);
    }

    public function report(Request $request): Response
    {
        $user = $this->user();
        $this->throttle('report:' . $user->id, 10, 3600);
        $type = (string) $request->input('target_type', '');
        $ref = (string) $request->input('target', '');
        $data = ['reason' => (string) $request->input('reason', ''), 'description' => trim((string) $request->input('description', ''))];
        if ($response = $this->invalid($data + ['target_type' => $type], [
            'target_type' => 'required|in:listing,creator',
            'reason' => 'required|in:conteudo_improprio,contato_externo,fraude,informacao_falsa,outro',
            'description' => 'max:2000',
        ], ['reason' => 'Motivo', 'description' => 'Descrição', 'target_type' => 'Tipo'])) {
            return $response;
        }
        $target = $type === 'listing'
            ? Db::first('SELECT id, title AS label FROM listings WHERE slug = :s AND deleted_at IS NULL', ['s' => $ref])
            : Db::first("SELECT p.user_id AS id, p.display_name AS label FROM profiles p JOIN users u ON u.id = p.user_id WHERE p.slug = :s AND u.role = 'creator'", ['s' => $ref]);
        if (!$target) {
            throw HttpException::notFound();
        }

        $id = Db::insert('reports', [
            'uuid' => uuid4(),
            'reporter_id' => $user->id,
            'target_type' => $type,
            'target_id' => (int) $target['id'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?: null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Audit::activity($user->id, 'report_created', 'Enviou uma denúncia sobre "' . $target['label'] . '"', 'report', $id);
        Notifier::admins('report_new', 'Nova denúncia recebida', (string) $target['label'], '/admin/denuncias');
        $this->success('Denúncia enviada. A equipe vai analisar.');

        return $this->back('/');
    }

    /** @return array<string, mixed> */
    private function activeListing(string $slug): array
    {
        $listing = ListingQueries::publicBySlug($slug);

        return $listing ?? throw HttpException::notFound('Este anúncio não está disponível.');
    }
}
