<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\GoogleAuthController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\SearchController;
use App\Controllers\ServiceController;
use App\Core\Application;

$router = Application::boot()->router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/buscar', [SearchController::class, 'index']);
$router->get('/anuncio-{slug}', [ServiceController::class, 'show']);
$router->post('/anuncio-{slug}', [ServiceController::class, 'handle']);
$router->get('/anuncio', [ServiceController::class, 'show']);
$router->post('/anuncio', [ServiceController::class, 'handle']);
$router->get('/servico/{category}/{slug}', [ServiceController::class, 'show']);
$router->post('/servico/{category}/{slug}/favoritar', [ServiceController::class, 'favorite']);
$router->post('/servico/{category}/{slug}/contratar', [ServiceController::class, 'hire']);
$router->post('/servico/{category}/{slug}/falar', [ServiceController::class, 'contact']);
$router->post('/servico/{category}/{slug}/orcamento', [ServiceController::class, 'quote']);

$router->get('/entrar', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/entrar', [AuthController::class, 'login'], ['guest']);
$router->get('/entrar/google', [GoogleAuthController::class, 'redirect'], ['guest']);
$router->get('/entrar/google/retorno', [GoogleAuthController::class, 'callback'], ['guest']);
$router->get('/criar-conta', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/criar-conta', [AuthController::class, 'register'], ['guest']);
$router->post('/sair', [AuthController::class, 'logout'], ['auth']);
$router->get('/esqueci-senha', [AuthController::class, 'showForgot'], ['guest']);
$router->post('/esqueci-senha', [AuthController::class, 'forgot'], ['guest']);
$router->get('/redefinir-senha/{token}', [AuthController::class, 'showReset'], ['guest']);
$router->post('/redefinir-senha/{token}', [AuthController::class, 'reset'], ['guest']);
$router->get('/confirmar-email/{token}', [AuthController::class, 'verifyEmail']);

$router->get('/conta', [AccountController::class, 'dashboard'], ['auth']);
$router->get('/pagina', [PageController::class, 'show']);
$router->get('/ajuda', [PageController::class, 'show']);
$router->get('/sobre', [PageController::class, 'show']);
$router->get('/como-funciona', [PageController::class, 'show']);
$router->get('/termos', [PageController::class, 'show']);
$router->get('/privacidade', [PageController::class, 'show']);
$router->get('/contato', [PageController::class, 'show']);
$router->get('/suporte', [PageController::class, 'show']);
$router->get('/p/{slug}', [PageController::class, 'show']);
