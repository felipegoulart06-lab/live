<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

final class PageController extends Controller
{
    public const SLUGS = ['termos', 'privacidade', 'sobre', 'como-funciona', 'contato'];

    public function show(Request $request): Response
    {
        $slug = trim($request->path(), '/');
        $page = Db::first("SELECT slug, title, content, updated_at FROM pages WHERE slug = :s AND status = 'published'", ['s' => $slug]);
        if (!$page) {
            throw HttpException::notFound();
        }

        return $this->view('public/page', ['title' => $page['title'] . ' · ' . brand_name(), 'page' => $page]);
    }

    public function help(Request $request): Response
    {
        $faqs = Db::all("SELECT question, answer, audience FROM faqs WHERE status = 'visible' ORDER BY sort_order, id");

        return $this->view('public/help', ['title' => 'Ajuda · ' . brand_name(), 'faqs' => $faqs]);
    }

    public function legacyAccount(Request $request): Response
    {
        return $this->redirect(Auth::user()?->homePath() ?? '/login');
    }
}
