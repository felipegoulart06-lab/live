<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Queries\CreatorQueries;
use App\Queries\ListingQueries;

final class CreatorController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = array_map(
            static fn ($v) => is_string($v) ? mb_substr(trim($v), 0, 80) : '',
            array_intersect_key($request->all(), array_flip(['q', 'cidade', 'verificado', 'ordem']))
        );

        return $this->view('public/creators', [
            'title' => 'Criadores · ' . brand_name(),
            'result' => CreatorQueries::search($filters, $this->page($request)),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request): Response
    {
        $creator = CreatorQueries::publicBySlug((string) $request->param('slug'));
        if (!$creator) {
            throw HttpException::notFound('Criador não encontrado.');
        }
        $listings = ListingQueries::cards('l.rating_avg DESC, l.id DESC', 24, 'AND l.creator_id = :c', ['c' => (int) $creator['id']]);
        if ($listings === []) {
            throw HttpException::notFound('Este criador não tem anúncios ativos no momento.');
        }
        $user = Auth::user();

        return $this->view('public/creator', [
            'title' => $creator['display_name'] . ' · ' . brand_name(),
            'metaDescription' => mb_substr((string) ($creator['headline'] ?? ''), 0, 160),
            'canonicalPath' => '/criadores/' . $creator['slug'],
            'creator' => $creator,
            'listings' => $listings,
            'reviews' => ListingQueries::reviews('creator_id', (int) $creator['id'], 12),
            'availability' => Db::all('SELECT weekday, start_time, end_time FROM availability WHERE creator_id = :c ORDER BY weekday', ['c' => (int) $creator['id']]),
            'favorited' => $user && (bool) Db::value(
                "SELECT 1 FROM favorites WHERE user_id = :u AND target_type = 'creator' AND target_id = :id",
                ['u' => $user->id, 'id' => (int) $creator['id']]
            ),
        ]);
    }
}
