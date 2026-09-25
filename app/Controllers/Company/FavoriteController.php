<?php

declare(strict_types=1);

namespace App\Controllers\Company;

use App\Controllers\Area\AreaController;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Queries\CreatorQueries;
use App\Queries\ListingQueries;

final class FavoriteController extends AreaController
{
    public function index(Request $request): Response
    {
        $uid = $this->user()->id;

        return $this->render('company/favorites', [
            'title' => 'Favoritos',
            'listings' => ListingQueries::cards(
                'l.title',
                60,
                "AND EXISTS (SELECT 1 FROM favorites f WHERE f.user_id = :u AND f.target_type = 'listing' AND f.target_id = l.id)",
                ['u' => $uid]
            ),
            'creators' => Db::all(
                'SELECT ' . CreatorQueries::CARD_SELECT . ' ' . CreatorQueries::PUBLIC_FROM . "
                   AND EXISTS (SELECT 1 FROM favorites f WHERE f.user_id = :u AND f.target_type = 'creator' AND f.target_id = u.id)
                 ORDER BY p.display_name LIMIT 60",
                ['u' => $uid]
            ),
        ]);
    }
}
