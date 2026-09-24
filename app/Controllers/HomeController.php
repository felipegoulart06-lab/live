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
            'title' => setting('meta_title', brand_name() . ' — horas de vídeo para a sua empresa'),
            'metaDescription' => setting('meta_description', 'Empresas encontram criadores para gravar 2, 4, 6 ou 8 horas de vídeo. O tema é definido por quem paga. Telefone e WhatsApp não ficam expostos.'),
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
