<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ContentRepository;
use App\Repositories\PageRepository;
use App\Repositories\ServiceRepository;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = new CategoryRepository();
        $services = new ServiceRepository();
        $content = new ContentRepository();
        $pages = new PageRepository();

        return $this->view('pages/home', [
            'title' => setting('meta_title', brand_name() . ' — transforme seu talento em renda extra'),
            'metaDescription' => setting('meta_description', 'Explore projetos, publique serviços e contrate freelancers com preço acessível.'),
            'menuCategories' => $categories->menuTree(),
            'popularCategories' => $categories->popular(8),
            'featured' => $services->homeList('featured', 10),
            'bestSelling' => $services->homeList('best_selling', 10),
            'recentServices' => $services->homeList('today', 10) ?: $services->homeList('recent', 10),
            'specialized' => $services->homeList('recommended', 10),
            'banners' => $content->banners(),
            'footerPages' => $pages->published(),
        ]);
    }
}
