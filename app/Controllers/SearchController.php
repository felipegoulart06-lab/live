<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;

final class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = ["s.status = 'published'", 'u.status = \'active\'', 'u.deleted_at IS NULL'];

        if ($q !== '') {
            $term = '%' . $q . '%';
            $where[] = '(s.title LIKE :q_title OR s.short_description LIKE :q_short OR s.description LIKE :q_desc OR p.display_name LIKE :q_name OR c.name LIKE :q_cat)';
            $params['q_title'] = $term;
            $params['q_short'] = $term;
            $params['q_desc'] = $term;
            $params['q_name'] = $term;
            $params['q_cat'] = $term;
        }

        $category = trim((string) $request->query('categoria', ''));
        if ($category !== '') {
            $where[] = 'c.slug = :categoria';
            $params['categoria'] = $category;
        }

        $min = $request->query('preco_min');
        if ($min !== null && $min !== '' && is_numeric($min)) {
            $where[] = 's.starting_price_cents >= :preco_min';
            $params['preco_min'] = (int) ((float) $min * 100);
        }

        $max = $request->query('preco_max');
        if ($max !== null && $max !== '' && is_numeric($max)) {
            $where[] = 's.starting_price_cents <= :preco_max';
            $params['preco_max'] = (int) ((float) $max * 100);
        }

        if ($request->query('verificado') === '1') {
            $where[] = 'p.is_verified = 1';
        }

        if ($request->query('entrega_rapida') === '1') {
            $where[] = 's.min_delivery_days <= 2';
        }

        $order = match ((string) $request->query('ordenar', 'relevantes')) {
            'vendidos' => 's.orders_count DESC',
            'avaliados' => 's.rating_avg DESC, s.rating_count DESC',
            'menor_preco' => 's.starting_price_cents ASC',
            'maior_preco' => 's.starting_price_cents DESC',
            'recentes' => 's.published_at DESC',
            'entrega' => 's.min_delivery_days ASC',
            default => 's.is_featured DESC, s.orders_count DESC, s.rating_avg DESC',
        };

        $sqlWhere = implode(' AND ', $where);
        $countStmt = (new ServiceRepository())->query(
            "SELECT COUNT(*) AS total
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}",
            $params
        );
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $rows = (new ServiceRepository())->query(
            "SELECT s.id, s.title, s.slug, s.short_description, s.starting_price_cents, s.min_delivery_days,
                    s.rating_avg, s.rating_count, s.orders_count, s.cover_path,
                    c.slug AS category_slug, c.name AS category_name,
                    p.display_name, p.slug AS freelancer_slug, p.avatar_path, p.is_verified
             FROM services s
             INNER JOIN categories c ON c.id = s.category_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN profiles p ON p.user_id = u.id
             WHERE {$sqlWhere}
             ORDER BY {$order}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return $this->view('pages/search', [
            'title' => $q !== '' ? 'Busca: ' . $q : 'Explorar serviços',
            'query' => $q,
            'results' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'menuCategories' => (new CategoryRepository())->menuTree(),
            'filters' => $request->all(),
        ]);
    }
}
