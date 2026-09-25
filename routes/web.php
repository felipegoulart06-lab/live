<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CreatorController;
use App\Controllers\FileController;
use App\Controllers\HomeController;
use App\Controllers\InteractionController;
use App\Controllers\ListingController;
use App\Controllers\PageController;
use App\Core\Request;
use App\Core\Response;

/** @var \App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/anuncios', [ListingController::class, 'index']);
$router->get('/anuncios/{slug}', [ListingController::class, 'show']);
$router->get('/categorias/{slug}', [ListingController::class, 'category']);
$router->get('/criadores', [CreatorController::class, 'index']);
$router->get('/criadores/{slug}', [CreatorController::class, 'show']);
$router->get('/ajuda', [PageController::class, 'help']);
foreach (PageController::SLUGS as $slug) {
    $router->get('/' . $slug, [PageController::class, 'show']);
}

$router->post('/anuncios/{slug}/solicitar', [InteractionController::class, 'requestHours'], ['auth']);
$router->post('/anuncios/{slug}/mensagem', [InteractionController::class, 'message'], ['auth']);
$router->post('/favoritos', [InteractionController::class, 'favorite'], ['auth']);
$router->post('/denunciar', [InteractionController::class, 'report'], ['auth']);
$router->get('/arquivos/{uuid}', [FileController::class, 'download'], ['auth']);

$router->group('', ['guest'], static function ($router): void {
    $router->get('/login', [AuthController::class, 'loginForm']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/cadastro', [AuthController::class, 'registerForm']);
    $router->post('/cadastro', [AuthController::class, 'register']);
    $router->get('/recuperar-senha', [AuthController::class, 'forgotForm']);
    $router->post('/recuperar-senha', [AuthController::class, 'forgot']);
    $router->get('/recuperar-senha/{token}', [AuthController::class, 'resetForm']);
    $router->post('/recuperar-senha/{token}', [AuthController::class, 'reset']);
});
$router->post('/sair', [AuthController::class, 'logout'], ['auth']);

// Addresses used by earlier versions of the site keep working.
$permanent = static fn (string $to): \Closure => static fn (Request $r): Response => Response::redirect(url($to), 301);
$router->get('/entrar', $permanent('/login'));
$router->get('/criar-conta', $permanent('/cadastro'));
$router->get('/esqueci-senha', $permanent('/recuperar-senha'));
$router->get('/conta', [PageController::class, 'legacyAccount']);
$router->get('/buscar', static function (Request $r): Response {
    $query = array_filter(['q' => $r->query('q'), 'categoria' => $r->query('categoria')], static fn ($v) => is_string($v) && $v !== '');

    return Response::redirect(url('/anuncios' . ($query ? '?' . http_build_query($query) : '')), 301);
});
$router->get('/anuncio-{slug}', static fn (Request $r): Response => Response::redirect(listing_url((string) $r->param('slug')), 301));
$router->get('/servico/{category}/{slug}', static fn (Request $r): Response => Response::redirect(listing_url((string) $r->param('slug')), 301));
