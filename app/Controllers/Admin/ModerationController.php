<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Audit;
use App\Services\Deals;
use App\Services\Notifier;

final class ModerationController extends AdminController
{
    public const REASONS = [
        'conteudo_improprio' => 'Conteúdo impróprio',
        'contato_externo' => 'Contato fora da plataforma',
        'fraude' => 'Suspeita de fraude',
        'informacao_falsa' => 'Informação falsa',
        'outro' => 'Outro motivo',
    ];

    public function reports(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $where = '1 = 1';
        $params = [];
        if (isset(status_map('report')[$status])) {
            $where .= ' AND r.status = :s';
            $params['s'] = $status;
        }

        return $this->render('admin/moderation/reports', [
            'title' => 'Denúncias',
            'status' => $status,
            'result' => Db::paginate(
                "r.uuid, r.target_type, r.target_id, r.reason, r.status, r.created_at, p.display_name AS reporter_name,
                 CASE WHEN r.target_type = 'listing' THEN (SELECT l.title FROM listings l WHERE l.id = r.target_id)
                      ELSE (SELECT tp.display_name FROM profiles tp WHERE tp.user_id = r.target_id) END AS target_label",
                "FROM reports r LEFT JOIN profiles p ON p.user_id = r.reporter_id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                "CASE WHEN r.status IN ('open', 'reviewing') THEN 0 ELSE 1 END, r.created_at DESC"
            ),
        ]);
    }

    public function report(Request $request): Response
    {
        $report = $this->findByUuid('reports', (string) $request->param('id'), 'Denúncia não encontrada.');
        $target = $report['target_type'] === 'listing'
            ? Db::first('SELECT l.uuid, l.title AS label, l.status FROM listings l WHERE l.id = :id', ['id' => (int) $report['target_id']])
            : Db::first('SELECT u.uuid, p.display_name AS label, u.status FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => (int) $report['target_id']]);

        return $this->render('admin/moderation/report', [
            'title' => 'Denúncia',
            'report' => $report,
            'target' => $target,
            'reporter' => $report['reporter_id'] ? Db::first('SELECT u.uuid, u.role, p.display_name FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => (int) $report['reporter_id']]) : null,
            'notes' => Db::all('SELECT n.note, n.created_at, p.display_name FROM report_notes n LEFT JOIN profiles p ON p.user_id = n.admin_id WHERE n.report_id = :id ORDER BY n.created_at, n.id', ['id' => (int) $report['id']]),
        ]);
    }

    public function updateReport(Request $request): Response
    {
        $report = $this->findByUuid('reports', (string) $request->param('id'), 'Denúncia não encontrada.');
        $status = (string) $request->input('status', '');
        $response = trim((string) $request->input('response', ''));
        $note = trim((string) $request->input('note', ''));
        if (!isset(status_map('report')[$status]) || mb_strlen($response) > 1000 || mb_strlen($note) > 1000) {
            throw new HttpException(422, 'Revise a situação e os textos (até 1000 caracteres).');
        }
        if ($status === 'resolved' && $response === '') {
            throw new HttpException(422, 'Para resolver a denúncia, escreva a resposta ao denunciante.');
        }
        Db::update('reports', [
            'status' => $status,
            'response' => $response ?: $report['response'],
            'resolved_by' => in_array($status, ['resolved', 'archived'], true) ? $this->user()->id : null,
            'resolved_at' => in_array($status, ['resolved', 'archived'], true) ? now() : null,
            'updated_at' => now(),
        ], ['id' => (int) $report['id']]);
        if ($note !== '') {
            Db::insert('report_notes', ['report_id' => (int) $report['id'], 'admin_id' => $this->user()->id, 'note' => $note, 'created_at' => now()]);
        }
        Audit::admin('report.update', 'report', (int) $report['id'], 'Atualizou denúncia para ' . status_label('report', $status));
        if ($status === 'resolved' && $report['reporter_id']) {
            Notifier::send((int) $report['reporter_id'], 'report_resolved', 'Sua denúncia foi analisada', $response, null);
        }
        $this->success('Denúncia atualizada.');

        return $this->redirect('/admin/denuncias/' . $report['uuid']);
    }

    public function reviews(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $where = '1 = 1';
        $params = [];
        if (isset(status_map('review')[$status])) {
            $where .= ' AND r.status = :s';
            $params['s'] = $status;
        }
        if ((int) $request->query('nota', 0) > 0) {
            $where .= ' AND r.rating = :n';
            $params['n'] = (int) $request->query('nota');
        }

        return $this->render('admin/moderation/reviews', [
            'title' => 'Avaliações',
            'status' => $status,
            'result' => Db::paginate(
                'r.id, r.rating, r.comment, r.status, r.moderation_note, r.created_at, co.company_name, cp.display_name AS creator_name, l.title AS listing_title, c.uuid AS contract_uuid',
                "FROM reviews r JOIN companies co ON co.user_id = r.company_id JOIN profiles cp ON cp.user_id = r.creator_id
                 JOIN listings l ON l.id = r.listing_id JOIN contracts c ON c.id = r.contract_id WHERE {$where}",
                $params,
                $this->page($request),
                25,
                'r.created_at DESC'
            ),
        ]);
    }

    public function moderateReview(Request $request): Response
    {
        $review = Db::first('SELECT * FROM reviews WHERE id = :id', ['id' => (int) $request->param('id')]) ?? throw HttpException::notFound();
        $hide = $request->input('status') === 'hidden';
        $note = trim((string) $request->input('note', ''));
        if ($hide && mb_strlen($note) < 5) {
            throw new HttpException(422, 'Explique por que a avaliação será ocultada (mínimo de 5 caracteres).');
        }
        Db::update('reviews', [
            'status' => $hide ? 'hidden' : 'visible',
            'moderated_by' => $this->user()->id,
            'moderated_at' => now(),
            'moderation_note' => $note ?: null,
            'updated_at' => now(),
        ], ['id' => (int) $review['id']]);
        Deals::refreshRatings((int) $review['listing_id'], (int) $review['creator_id']);
        Audit::admin($hide ? 'review.hide' : 'review.show', 'review', (int) $review['id'], ($hide ? 'Ocultou' : 'Reexibiu') . ' uma avaliação', $note ? ['note' => $note] : []);
        $this->success($hide ? 'Avaliação ocultada. A nota média foi recalculada.' : 'Avaliação visível novamente.');

        return $this->back('/admin/avaliacoes');
    }
}
