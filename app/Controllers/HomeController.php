<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ContentRepository;
use App\Repositories\PageRepository;
use App\Repositories\ProfilePublicRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = new CategoryRepository();
        $services = new ServiceRepository();
        $profiles = new ProfilePublicRepository();
        $projects = new ProjectRepository();
        $content = new ContentRepository();
        $pages = new PageRepository();

        return $this->view('pages/home', [
            'title' => setting('meta_title', setting('platform_name', 'Nexo') . ' — profissionais para o que você precisa agora'),
            'metaDescription' => setting('meta_description', 'Contrate profissionais verificados para design, programação, marketing, vídeo e dezenas de outras especialidades.'),
            'menuCategories' => $categories->menuTree(),
            'popularCategories' => $categories->popular(8),
            'recommended' => $services->homeList('recommended', 8),
            'featured' => $services->homeList('featured', 8),
            'bestSelling' => $services->homeList('best_selling', 8),
            'recentServices' => $services->homeList('recent', 8),
            'fastDelivery' => $services->homeList('fast', 8),
            'offers' => $services->homeList('offers', 4),
            'newSellers' => $profiles->highlightedSellers('new', 8),
            'topRated' => $profiles->highlightedSellers('top_rated', 8),
            'verifiedSellers' => $profiles->highlightedSellers('verified', 8),
            'openProjects' => $projects->openRecent(6),
            'faqs' => $content->faqs(),
            'testimonials' => $content->testimonials(),
            'banners' => $content->banners(),
            'footerPages' => $pages->published(),
        ]);
    }
}
