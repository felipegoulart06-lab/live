<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Queries\CreatorQueries;
use App\Queries\ListingQueries;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $featuredCreators = CreatorQueries::highlighted('featured_creators', 8);
        $mostHired = ListingQueries::highlighted('most_hired', 10);
        if ($mostHired === []) {
            $mostHired = ListingQueries::cards('l.contracts_count DESC, l.rating_avg DESC', 10);
        }
        $newListings = ListingQueries::highlighted('new_listings', 10);
        if ($newListings === []) {
            $newListings = ListingQueries::cards('l.published_at DESC, l.id DESC', 10);
        }
        $forCompanies = ListingQueries::highlighted('video_for_company', 10);

        $videoTypes = Db::all(
            "SELECT c.name, c.slug, c.description, c.image_path FROM home_highlights h
             JOIN categories c ON c.id = h.item_id AND c.status = 'visible'
             WHERE h.section = 'video_types' AND h.item_type = 'category' ORDER BY h.sort_order, h.id"
        );
        if ($videoTypes === []) {
            $videoTypes = array_slice(ListingQueries::visibleCategories(), 0, 8);
        }

        return $this->view('public/home', [
            'title' => brand_name() . ' · Horas de vídeo para empresas',
            'featuredCreators' => $featuredCreators,
            'mostHired' => $mostHired,
            'newListings' => $newListings,
            'forCompanies' => $forCompanies,
            'videoTypes' => $videoTypes,
            'faqs' => Db::all("SELECT question, answer FROM faqs WHERE status = 'visible' AND audience IN ('all', 'company') ORDER BY sort_order, id LIMIT 6"),
        ]);
    }
}
