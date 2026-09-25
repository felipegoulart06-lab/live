<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\ListingController as PublicListingController;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Queries\ListingQueries;
use App\Services\Audit;
use App\Services\Listings;

final class ListingController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->listing($request, false);
    }

    public function pending(Request $request): Response
    {
        return $this->listing($request, true);
    }

    private function listing(Request $request, bool $onlyPending): Response
    {
        $where = ['l.deleted_at IS NULL'];
        $params = [];
        $status = $onlyPending ? 'pending' : (string) $request->query('status', '');
        if (isset(status_map('listing')[$status])) {
            $where[] = 'l.status = :s';
            $params['s'] = $status;
        }
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $where[] = '(LOWER(l.title) LIKE :q' . Db::ESCAPE . ' OR LOWER(p.display_name) LIKE :q2' . Db::ESCAPE . ')';
            $params['q'] = $params['q2'] = Db::like($q);
        }
        $category = (int) $request->query('categoria', 0);
        if ($category > 0) {
            $where[] = 'l.category_id = :c';
            $params['c'] = $category;
        }
        [$from, $to] = $this->period($request);
        if ($from) {
            $where[] = 'l.created_at >= :from';
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where[] = 'l.created_at <= :to';
            $params['to'] = $to . ' 23:59:59';
        }
        $order = match ((string) $request->query('ordem', '')) {
            'antigos' => 'l.created_at ASC',
            'contratos' => 'l.contracts_count DESC',
            'visitas' => 'l.views_count DESC',
            default => $onlyPending ? 'l.submitted_at ASC' : 'l.updated_at DESC',
        };

        return $this->render('admin/listings/index', [
            'title' => $onlyPending ? 'Anúncios pendentes' : 'Anúncios',
            'onlyPending' => $onlyPending,
            'status' => $status,
            'q' => $q,
            'category' => $category,
            'categories' => Db::all('SELECT id, name FROM categories ORDER BY sort_order, name'),
            'result' => Db::paginate(
                'l.uuid, l.slug, l.title, l.status, l.cover_path, l.starting_price_cents, l.views_count, l.contracts_count, l.submitted_at, l.created_at, l.updated_at,
                 p.display_name, c.name AS category_name',
                'FROM listings l JOIN profiles p ON p.user_id = l.creator_id LEFT JOIN categories c ON c.id = l.category_id WHERE ' . implode(' AND ', $where),
                $params,
                $this->page($request),
                25,
                $order . ', l.id DESC'
            ),
        ]);
    }

    public function show(Request $request): Response
    {
        $row = $this->find($request);
        $listing = ListingQueries::anyById((int) $row['id']);

        return $this->render('admin/listings/show', ListingQueries::details((int) $row['id'], (int) $row['creator_id']) + [
            'title' => $row['title'] ?: 'Anúncio',
            'listing' => $listing,
            'checklist' => Listings::checklist($row),
            'categories' => Db::all('SELECT id, name FROM categories ORDER BY sort_order, name'),
            'history' => Db::all(
                'SELECT m.action, m.reason, m.created_at, p.display_name AS actor_name, u.role AS actor_role
                 FROM listing_moderations m LEFT JOIN users u ON u.id = m.actor_id LEFT JOIN profiles p ON p.user_id = m.actor_id
                 WHERE m.listing_id = :id ORDER BY m.created_at DESC, m.id DESC',
                ['id' => (int) $row['id']]
            ),
            'creatorUuid' => Db::value('SELECT uuid FROM users WHERE id = :id', ['id' => (int) $row['creator_id']]),
            'embed' => Listings::embedUrl($row['video_url']),
        ]);
    }

    public function preview(Request $request): Response
    {
        $row = $this->find($request);

        return $this->view('public/listing', (new PublicListingController())->detailData(ListingQueries::anyById((int) $row['id']), true) + ['title' => 'Prévia · ' . $row['title']]);
    }

    public function approve(Request $request): Response
    {
        $row = $this->find($request);
        if ($row['status'] !== 'pending') {
            throw new HttpException(422, 'Só anúncios pendentes podem ser aprovados.');
        }
        Listings::approve($row, $this->user()->id);
        $this->success('Anúncio aprovado e publicado.');

        return $this->nextPending();
    }

    public function reject(Request $request): Response
    {
        $row = $this->find($request);
        if (!in_array($row['status'], ['pending', 'active', 'paused'], true)) {
            throw new HttpException(422, 'Este anúncio não pode ser reprovado.');
        }
        $reason = $this->reason($request, 'reason', 10);
        if ($reason === null) {
            Session::flash('errors', ['reason' => ['Explique o motivo em pelo menos 10 caracteres. O criador vai ler este texto.']]);

            return $this->redirect('/admin/anuncios/' . $row['uuid'] . '#moderar');
        }
        Listings::reject($row, $this->user()->id, $reason);
        $this->success('Anúncio reprovado. O criador recebeu o motivo.');

        return $this->nextPending();
    }

    public function pause(Request $request): Response
    {
        $row = $this->find($request);
        $reason = $this->reason($request, 'reason', 5);
        if ($reason === null) {
            throw new HttpException(422, 'Informe o motivo da pausa (mínimo de 5 caracteres).');
        }
        Listings::setPaused($row, $this->user()->id, true, true, $reason);
        $this->success('Anúncio pausado.');

        return $this->redirect('/admin/anuncios/' . $row['uuid']);
    }

    public function reactivate(Request $request): Response
    {
        $row = $this->find($request);
        Listings::setPaused($row, $this->user()->id, false, true);
        $this->success('Anúncio reativado.');

        return $this->redirect('/admin/anuncios/' . $row['uuid']);
    }

    public function update(Request $request): Response
    {
        $row = $this->find($request);
        $data = [
            'category_id' => (string) $request->input('category_id', ''),
            'title' => trim((string) $request->input('title', '')),
            'short_description' => trim((string) $request->input('short_description', '')),
            'description' => trim((string) $request->input('description', '')),
        ];
        $ids = implode(',', array_column(Db::all('SELECT id FROM categories'), 'id'));
        if ($response = $this->invalid($data, [
            'category_id' => 'required|in:' . $ids,
            'title' => 'required|min:10|max:90',
            'short_description' => 'required|min:20|max:200',
            'description' => 'required|min:80|max:5000',
        ], ['category_id' => 'Categoria', 'title' => 'Título', 'short_description' => 'Resumo', 'description' => 'Descrição'], '/admin/anuncios/' . $row['uuid'])) {
            return $response;
        }
        $changed = array_keys(array_filter($data, static fn ($v, $k) => (string) $row[$k] !== (string) $v, ARRAY_FILTER_USE_BOTH));
        Db::update('listings', $data + ['updated_at' => now()], ['id' => (int) $row['id']]);
        Audit::admin('listing.edit', 'listing', (int) $row['id'], 'Editou o anúncio "' . $data['title'] . '"', ['fields' => $changed]);
        $this->success('Anúncio atualizado.');

        return $this->redirect('/admin/anuncios/' . $row['uuid']);
    }

    public function destroy(Request $request): Response
    {
        $row = $this->find($request);
        Listings::softDelete($row, $this->user()->id, true);
        $this->success('Anúncio excluído.');

        return $this->redirect('/admin/anuncios');
    }

    /** @return array<string, mixed> */
    private function find(Request $request): array
    {
        $row = Db::first('SELECT * FROM listings WHERE uuid = :u AND deleted_at IS NULL', ['u' => (string) $request->param('id')]);

        return $row ?? throw HttpException::notFound('Anúncio não encontrado.');
    }

    private function nextPending(): Response
    {
        $next = Db::value("SELECT uuid FROM listings WHERE status = 'pending' AND deleted_at IS NULL ORDER BY submitted_at LIMIT 1");

        return $this->redirect($next ? '/admin/anuncios/' . $next : '/admin/anuncios/pendentes');
    }
}
