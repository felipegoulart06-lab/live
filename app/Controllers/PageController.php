<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\PageRepository;

final class PageController extends Controller
{
    public function show(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $page = (new PageRepository())->findPublishedBySlug($slug);
        if (!$page) {
            return Response::view('pages/errors/404', [
                'title' => 'Página não encontrada',
                'authUser' => $this->user(),
                'csrf' => csrf_token(),
                'menuCategories' => (new CategoryRepository())->menuTree(),
            ], 'layouts/main', 404);
        }

        return $this->view('pages/cms', [
            'title' => $page['meta_title'] ?: $page['title'],
            'metaDescription' => $page['meta_description'] ?: '',
            'page' => $page,
            'menuCategories' => (new CategoryRepository())->menuTree(),
        ]);
    }
}
