<?php

declare(strict_types=1);

use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DealController;
use App\Controllers\Admin\ListingController;
use App\Controllers\Admin\ModerationController;
use App\Controllers\Admin\SystemController;
use App\Controllers\Admin\UserController;

/** @var \App\Core\Router $router */

$router->group('admin', ['auth', 'role:admin'], static function ($router): void {
    $router->get('/', [DashboardController::class, 'index']);

    $router->get('/anuncios', [ListingController::class, 'index']);
    $router->get('/anuncios/pendentes', [ListingController::class, 'pending']);
    $router->get('/anuncios/{id}', [ListingController::class, 'show']);
    $router->get('/anuncios/{id}/previa', [ListingController::class, 'preview']);
    $router->post('/anuncios/{id}', [ListingController::class, 'update']);
    $router->post('/anuncios/{id}/aprovar', [ListingController::class, 'approve']);
    $router->post('/anuncios/{id}/reprovar', [ListingController::class, 'reject']);
    $router->post('/anuncios/{id}/pausar', [ListingController::class, 'pause']);
    $router->post('/anuncios/{id}/reativar', [ListingController::class, 'reactivate']);
    $router->post('/anuncios/{id}/excluir', [ListingController::class, 'destroy']);

    $router->get('/criadores', [UserController::class, 'creators']);
    $router->get('/criadores/{id}', [UserController::class, 'show']);
    $router->post('/criadores/{id}/verificar', [UserController::class, 'verify']);
    $router->get('/empresas', [UserController::class, 'companies']);
    $router->get('/empresas/{id}', [UserController::class, 'show']);
    $router->get('/bloqueados', [UserController::class, 'blocked']);
    $router->post('/usuarios/{id}/bloquear', [UserController::class, 'block']);
    $router->post('/usuarios/{id}/desbloquear', [UserController::class, 'unblock']);
    $router->get('/administradores', [UserController::class, 'admins']);
    $router->post('/administradores', [UserController::class, 'storeAdmin']);
    $router->post('/administradores/{id}/nivel', [UserController::class, 'setAdminLevel']);

    $router->get('/solicitacoes', [DealController::class, 'requests']);
    $router->get('/contratos', [DealController::class, 'contracts']);
    $router->get('/contratos/{id}', [DealController::class, 'contract']);
    $router->post('/contratos/{id}/pagamento', [DealController::class, 'confirmPayment']);
    $router->post('/contratos/{id}/cancelar', [DealController::class, 'cancelContract']);
    $router->post('/contratos/{id}/observacao', [DealController::class, 'note']);
    $router->get('/pagamentos', [DealController::class, 'payments']);
    $router->post('/pagamentos/{id}/repasse', [DealController::class, 'payout']);
    $router->get('/mensagens', [DealController::class, 'conversations']);
    $router->get('/mensagens/{id}', [DealController::class, 'conversation']);

    $router->get('/categorias', [ContentController::class, 'categories']);
    $router->post('/categorias', [ContentController::class, 'saveCategory']);
    $router->post('/categorias/{id}/excluir', [ContentController::class, 'deleteCategory']);
    $router->get('/destaques', [ContentController::class, 'highlights']);
    $router->post('/destaques', [ContentController::class, 'addHighlight']);
    $router->post('/destaques/{id}/mover', [ContentController::class, 'moveHighlight']);
    $router->post('/destaques/{id}/remover', [ContentController::class, 'removeHighlight']);
    $router->get('/conteudo', [ContentController::class, 'home']);
    $router->post('/conteudo', [ContentController::class, 'saveHome']);
    $router->get('/faq', [ContentController::class, 'faqs']);
    $router->post('/faq', [ContentController::class, 'saveFaq']);
    $router->post('/faq/{id}/excluir', [ContentController::class, 'deleteFaq']);
    $router->get('/paginas', [ContentController::class, 'pages']);
    $router->post('/paginas', [ContentController::class, 'savePage']);

    $router->get('/denuncias', [ModerationController::class, 'reports']);
    $router->get('/denuncias/{id}', [ModerationController::class, 'report']);
    $router->post('/denuncias/{id}', [ModerationController::class, 'updateReport']);
    $router->get('/avaliacoes', [ModerationController::class, 'reviews']);
    $router->post('/avaliacoes/{id}', [ModerationController::class, 'moderateReview']);

    $router->get('/configuracoes', [SystemController::class, 'settings']);
    $router->post('/configuracoes', [SystemController::class, 'saveSettings']);
    $router->get('/atividade', [SystemController::class, 'activity']);
    $router->get('/notificacoes', [SystemController::class, 'notifications']);
    $router->post('/notificacoes', [SystemController::class, 'broadcast']);
});
