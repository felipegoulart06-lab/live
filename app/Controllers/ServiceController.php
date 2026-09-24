<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;

final class ServiceController extends Controller
{
    public function __construct(private readonly ServiceRepository $services = new ServiceRepository())
    {
    }

    public function show(Request $request): Response
    {
        $category = rawurldecode((string) $request->param('category'));
        $slug = rawurldecode((string) $request->param('slug'));
        $service = $this->services->findPublished($category, $slug);

        if (!$service) {
            return Response::view('pages/errors/404', [
                'title' => 'Serviço não encontrado',
                'authUser' => $this->user(),
                'csrf' => csrf_token(),
                'menuCategories' => (new CategoryRepository())->menuTree(),
            ], 'layouts/main', 404);
        }

        $this->services->incrementViews((int) $service['id']);
        $service['views_count'] = (int) $service['views_count'] + 1;

        $packages = $this->services->packages((int) $service['id']);
        $extras = $this->services->extras((int) $service['id']);
        $gallery = $this->services->images((int) $service['id'], $service['cover_path'] ?: null);
        $faqs = $this->services->faqs((int) $service['id']);
        $reviews = $this->services->reviews((int) $service['id']);
        $others = $this->services->otherBySeller((int) $service['user_id'], (int) $service['id']);
        $favorited = Auth::check() && $this->services->isFavorited((int) Auth::id(), (int) $service['id']);

        $tierLabels = [
            'hours_2' => '2h',
            'hours_4' => '4h',
            'hours_6' => '6h',
            'hours_8' => '8h',
            'basic' => '2h',
            'standard' => '4h',
            'premium' => '8h',
        ];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service['title'],
            'description' => $service['short_description'],
            'image' => $service['cover_path'] ? media($service['cover_path']) : null,
            'provider' => [
                '@type' => 'Person',
                'name' => seller_short_name((string) $service['display_name']),
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'BRL',
                'lowPrice' => number_format(((int) $service['starting_price_cents']) / 100, 2, '.', ''),
            ],
            'aggregateRating' => ((int) $service['rating_count'] > 0) ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $service['rating_avg'],
                'reviewCount' => $service['rating_count'],
            ] : null,
        ];

        return $this->view('pages/services/show', [
            'title' => $service['title'],
            'metaDescription' => $service['short_description'],
            'ogImage' => $service['cover_path'] ? media($service['cover_path']) : null,
            'canonicalPath' => '/servico/' . $service['category_slug'] . '/' . $service['slug'],
            'menuCategories' => (new CategoryRepository())->menuTree(),
            'service' => $service,
            'packages' => $packages,
            'extras' => $extras,
            'gallery' => $gallery,
            'faqs' => $faqs,
            'reviews' => $reviews,
            'others' => $others,
            'languages' => $this->services->sellerLanguages((int) $service['user_id']),
            'skills' => $this->services->sellerSkills((int) $service['user_id']),
            'badges' => $this->services->sellerBadges((int) $service['user_id']),
            'favorited' => $favorited,
            'tierLabels' => $tierLabels,
            'jsonLd' => array_filter($schema),
        ]);
    }

    public function favorite(Request $request): Response
    {
        if (!Auth::check()) {
            Session::set('intended', $request->path());
            $this->withError('Entre para salvar este serviço.');

            return $this->redirect('/entrar');
        }

        $service = $this->locate($request);
        if (!$service) {
            return $this->redirect('/');
        }

        $on = $this->services->toggleFavorite((int) Auth::id(), (int) $service['id']);
        $this->withSuccess($on ? 'Serviço salvo nos favoritos.' : 'Serviço removido dos favoritos.');

        return $this->redirect('/servico/' . $service['category_slug'] . '/' . $service['slug']);
    }

    public function hire(Request $request): Response
    {
        $service = $this->locate($request);
        if (!$service) {
            return $this->redirect('/');
        }

        $target = '/servico/' . $service['category_slug'] . '/' . $service['slug'];
        if (!Auth::check()) {
            Session::set('intended', $target);
            $this->withError('Entre para contratar horas. Telefone e WhatsApp do criador não são publicados.');

            return $this->redirect('/entrar');
        }

        $theme = trim((string) $request->input('theme', ''));
        if (mb_strlen($theme) < 8) {
            $this->withError('Descreva o tema do vídeo. Quem paga define o assunto; o criador não publica telefone.');

            return $this->redirect($target);
        }

        $packages = $this->services->packages((int) $service['id']);
        $packageId = (int) $request->input('package_id', 0);
        $package = null;
        foreach ($packages as $pkg) {
            if ((int) $pkg['id'] === $packageId) {
                $package = $pkg;
                break;
            }
        }
        if ($package === null && $packages !== []) {
            $package = $packages[0];
        }

        $extras = $request->input('extras');
        $extraIds = is_array($extras) ? array_map('intval', $extras) : [];

        $code = $this->services->saveHireIntent(
            (int) Auth::id(),
            $service,
            $package,
            $theme,
            trim((string) $request->input('company_name', '')),
            trim((string) $request->input('notes', '')),
            $extraIds
        );

        $hours = $package ? package_hours($package) : 2;
        $this->withSuccess('Pedido ' . $code . ' registrado: ' . $hours . 'h de vídeo com o tema que a empresa definiu. O contato segue só pela plataforma.');

        return $this->redirect($target);
    }

    public function contact(Request $request): Response
    {
        return $this->guardedAction($request, 'A mensagem fica na plataforma. Telefone, e-mail e WhatsApp do criador não são divulgados no anúncio.');
    }

    public function quote(Request $request): Response
    {
        return $this->guardedAction($request, 'Peça mais horas no mesmo pedido. O combinado continua interno, sem telefone público.');
    }

    private function guardedAction(Request $request, string $message): Response
    {
        $service = $this->locate($request);
        if (!$service) {
            return $this->redirect('/');
        }

        $target = '/servico/' . $service['category_slug'] . '/' . $service['slug'];
        if (!Auth::check()) {
            Session::set('intended', $target);
            $this->withError('Entre para continuar. O contato não sai da plataforma.');

            return $this->redirect('/entrar');
        }

        $this->withSuccess($message);

        return $this->redirect($target);
    }

    private function locate(Request $request): ?array
    {
        return $this->services->findPublished(
            rawurldecode((string) $request->param('category')),
            rawurldecode((string) $request->param('slug'))
        );
    }
}
