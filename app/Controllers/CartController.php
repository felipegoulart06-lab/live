<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Queries\ListingQueries;
use App\Services\Cart;
use App\Services\Deals;

final class CartController extends Controller
{
    public function show(Request $request): Response
    {
        $snap = Cart::snapshot();

        return $this->view('public/cart', [
            'title' => 'Carrinho · ' . brand_name(),
            'noindex' => true,
            'lines' => $snap['lines'],
            'totalCents' => $snap['total_cents'],
            'days' => $snap['days'],
        ]);
    }

    public function add(Request $request): Response
    {
        $this->throttle('cart:' . $request->ip(), 40, 3600);
        $listing = ListingQueries::publicBySlug((string) $request->param('slug'));
        if (!$listing) {
            throw HttpException::notFound('Este anúncio não está disponível.');
        }

        $data = [
            'package_id' => (string) $request->input('package_id', ''),
            'theme' => trim((string) $request->input('theme', '')),
            'briefing' => trim((string) $request->input('briefing', '')),
            'desired_date' => (string) $request->input('desired_date', ''),
            'desired_time' => (string) $request->input('desired_time', ''),
        ];
        $back = '/anuncios/' . $listing['slug'] . '#contratar';
        if ($response = $this->invalid($data, [
            'package_id' => 'required|integer',
            'theme' => 'max:300',
            'briefing' => 'max:4000',
            'desired_date' => 'date|future',
            'desired_time' => 'time',
        ], ['package_id' => 'Pacote', 'theme' => 'Tema', 'briefing' => 'Briefing', 'desired_date' => 'Data desejada', 'desired_time' => 'Horário'], $back)) {
            return $response;
        }

        $addonIds = array_values(array_filter(array_map('intval', (array) $request->input('addons', [])), static fn (int $id): bool => $id > 0));
        Cart::add($listing, (int) $data['package_id'], $addonIds, $data);
        $this->success('Adicionado ao carrinho. Você entra na conta da empresa só na hora de pagar.');

        return $this->redirect('/carrinho');
    }

    public function remove(Request $request): Response
    {
        Cart::remove(trim((string) $request->input('id', '')));
        $this->success('Item removido do carrinho.');

        return $this->redirect('/carrinho');
    }

    public function checkout(Request $request): Response
    {
        $user = $this->user();
        if (!$user->isCompany()) {
            $this->error('Só contas de empresa finalizam a compra. Entre com a conta da empresa.');

            return $this->redirect('/carrinho');
        }

        $this->throttle('request:' . $user->id, 10, 3600);
        $snap = Cart::snapshot();
        if ($snap['lines'] === []) {
            $this->error('Seu carrinho está vazio.');

            return $this->redirect('/carrinho');
        }

        $line = Cart::takeFirst();
        if (!$line) {
            return $this->redirect('/carrinho');
        }
        $listing = ListingQueries::publicBySlug((string) $line['listing_slug']);
        if (!$listing) {
            $this->error('Este anúncio saiu do ar. Ele foi retirado do carrinho.');

            return $this->redirect('/carrinho');
        }

        $theme = trim((string) ($line['theme'] ?? ''));
        if (mb_strlen($theme) < 8) {
            $theme = trim((string) $listing['title']);
        }
        $contractUuid = Deals::checkoutNow($user->id, $listing, (int) $line['package_id'], array_map('intval', (array) ($line['addon_ids'] ?? [])), [
            'theme' => $theme,
            'briefing' => (string) ($line['briefing'] ?? ''),
            'message' => '',
            'desired_date' => (string) ($line['desired_date'] ?? ''),
            'desired_time' => (string) ($line['desired_time'] ?? ''),
        ]);
        if (Cart::count() > 0) {
            $this->success('Pedido criado. Pague este e os outros itens continuam no carrinho.');
        }

        return $this->redirect('/empresa/contratos/' . $contractUuid . '/checkout');
    }
}
