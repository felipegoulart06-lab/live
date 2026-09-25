<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Queries\ListingQueries;
use App\Services\Listings;

final class ListingController extends Controller
{
    private const FILTER_KEYS = ['q', 'categoria', 'preco_min', 'preco_max', 'horas', 'prazo', 'nota', 'verificado', 'cidade', 'ordem'];

    public function index(Request $request): Response
    {
        $filters = array_intersect_key($request->all(), array_flip(self::FILTER_KEYS));
        $filters = array_map(static fn ($v) => is_string($v) ? mb_substr(trim($v), 0, 80) : '', $filters);
        $result = ListingQueries::search($filters, $this->page($request));
        $categories = ListingQueries::visibleCategories();
        $current = null;
        foreach ($categories as $category) {
            if (($filters['categoria'] ?? '') === $category['slug']) {
                $current = $category;
            }
        }

        return $this->view('public/listings', [
            'title' => ($current ? $current['name'] . ' · ' : '') . 'Anúncios de horas de vídeo',
            'heading' => $current['name'] ?? 'Horas de vídeo',
            'intro' => $current['description'] ?? 'Compare criadores, pacotes de horas, prazos e avaliações de outras empresas.',
            'result' => $result,
            'filters' => $filters,
            'categories' => $categories,
            'hourOptions' => array_map('intval', array_column(Db::all('SELECT DISTINCT hours FROM listing_packages ORDER BY hours'), 'hours')),
            'canonicalPath' => $current ? '/categorias/' . $current['slug'] : '/anuncios',
        ]);
    }

    public function category(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $category = Db::first("SELECT slug FROM categories WHERE slug = :s AND status = 'visible'", ['s' => $slug]);
        if (!$category) {
            throw HttpException::notFound('Categoria não encontrada.');
        }
        return $this->index(new Request('GET', '/anuncios', $_GET + ['categoria' => $slug], [], $_SERVER, [], []));
    }

    public function show(Request $request): Response
    {
        $listing = ListingQueries::publicBySlug((string) $request->param('slug'));
        if (!$listing) {
            throw HttpException::notFound('Este anúncio não existe ou não está mais disponível.');
        }

        $viewKey = 'viewed_' . $listing['id'];
        if (!Session::get($viewKey) && Auth::id() !== (int) $listing['creator_id']) {
            Session::set($viewKey, 1);
            Db::run('UPDATE listings SET views_count = views_count + 1 WHERE id = :id', ['id' => (int) $listing['id']]);
        }

        return $this->view('public/listing', $this->detailData($listing, false));
    }

    /** Shared by the public page and the creator preview. @return array<string, mixed> */
    public function detailData(array $listing, bool $preview): array
    {
        $details = ListingQueries::details((int) $listing['id'], (int) $listing['creator_id']);
        $user = Auth::user();
        $favorited = $user && (bool) Db::value(
            "SELECT 1 FROM favorites WHERE user_id = :u AND target_type = 'listing' AND target_id = :id",
            ['u' => $user->id, 'id' => (int) $listing['id']]
        );
        $pendingRequest = $user && $user->isCompany() ? Db::first(
            "SELECT uuid, code FROM requests WHERE company_id = :u AND listing_id = :l AND status = 'pending'",
            ['u' => $user->id, 'l' => (int) $listing['id']]
        ) : null;

        return $details + [
            'title' => $listing['title'] . ' · ' . brand_name(),
            'metaDescription' => mb_substr((string) $listing['short_description'], 0, 160),
            'canonicalPath' => '/anuncios/' . $listing['slug'],
            'ogImage' => !empty($listing['cover_path']) ? media((string) $listing['cover_path']) : null,
            'listing' => $listing,
            'reviews' => ListingQueries::reviews('listing_id', (int) $listing['id']),
            'others' => ListingQueries::cards('l.rating_avg DESC, l.id DESC', 3, 'AND l.creator_id = :c AND l.id <> :id', ['c' => (int) $listing['creator_id'], 'id' => (int) $listing['id']]),
            'embed' => Listings::embedUrl($listing['video_url'] ?? null),
            'favorited' => $favorited,
            'pendingRequest' => $pendingRequest,
            'preview' => $preview,
        ];
    }
}
