<?php

declare(strict_types=1);

use App\Controllers\Area\ContractController;
use App\Controllers\Area\MessageController;
use App\Controllers\Area\ProfileController;
use App\Controllers\Area\RequestController;
use App\Controllers\Company\DashboardController as CompanyDashboard;
use App\Controllers\Company\FavoriteController;
use App\Controllers\Creator\DashboardController as CreatorDashboard;
use App\Controllers\Creator\FinanceController;
use App\Controllers\Creator\ListingController;
use App\Controllers\NotificationController;

/** @var \App\Core\Router $router */

$router->group('notificacoes', ['auth'], static function ($router): void {
    $router->get('/', [NotificationController::class, 'index']);
    $router->post('/ler', [NotificationController::class, 'readAll']);
    $router->get('/{id}', [NotificationController::class, 'open']);
});

$router->group('painel', ['auth', 'role:creator'], static function ($router): void {
    $router->get('/', [CreatorDashboard::class, 'index']);

    $router->get('/anuncios', [ListingController::class, 'index']);
    $router->get('/anuncios/novo', [ListingController::class, 'create']);
    $router->post('/anuncios/novo', [ListingController::class, 'store']);
    $router->get('/anuncios/{id}', [ListingController::class, 'edit']);
    $router->get('/anuncios/{id}/previa', [ListingController::class, 'preview']);
    $router->post('/anuncios/{id}/etapa/{step}', [ListingController::class, 'saveStep']);
    $router->post('/anuncios/{id}/imagens', [ListingController::class, 'uploadImages']);
    $router->post('/anuncios/{id}/imagens/{image}/principal', [ListingController::class, 'primaryImage']);
    $router->post('/anuncios/{id}/imagens/{image}/remover', [ListingController::class, 'removeImage']);
    $router->post('/anuncios/{id}/enviar', [ListingController::class, 'submit']);
    $router->post('/anuncios/{id}/pausar', [ListingController::class, 'pause']);
    $router->post('/anuncios/{id}/reativar', [ListingController::class, 'reactivate']);
    $router->post('/anuncios/{id}/duplicar', [ListingController::class, 'duplicate']);
    $router->post('/anuncios/{id}/excluir', [ListingController::class, 'destroy']);

    $router->get('/solicitacoes', [RequestController::class, 'index']);
    $router->get('/solicitacoes/{id}', [RequestController::class, 'show']);
    $router->post('/solicitacoes/{id}/aceitar', [RequestController::class, 'accept']);
    $router->post('/solicitacoes/{id}/recusar', [RequestController::class, 'decline']);

    $router->get('/contratos', [ContractController::class, 'index']);
    $router->get('/contratos/{id}', [ContractController::class, 'show']);
    $router->post('/contratos/{id}/iniciar', [ContractController::class, 'start']);
    $router->post('/contratos/{id}/concluir', [ContractController::class, 'complete']);
    $router->post('/contratos/{id}/agenda', [ContractController::class, 'schedule']);
    $router->post('/contratos/{id}/cancelar', [ContractController::class, 'cancel']);
    $router->post('/contratos/{id}/arquivos', [ContractController::class, 'attach']);

    $router->get('/mensagens', [MessageController::class, 'index']);
    $router->get('/mensagens/{id}', [MessageController::class, 'show']);
    $router->post('/mensagens/{id}', [MessageController::class, 'send']);

    $router->get('/financeiro', [FinanceController::class, 'index']);

    $router->get('/perfil', [ProfileController::class, 'edit']);
    $router->post('/perfil', [ProfileController::class, 'update']);
    $router->post('/perfil/foto', [ProfileController::class, 'avatar']);
    $router->post('/perfil/disponibilidade', [ProfileController::class, 'availability']);
    $router->post('/perfil/verificacao', [ProfileController::class, 'requestVerification']);
    $router->post('/perfil/acesso', [ProfileController::class, 'security']);
});

$router->group('empresa', ['auth', 'role:company'], static function ($router): void {
    $router->get('/', [CompanyDashboard::class, 'index']);

    $router->get('/solicitacoes', [RequestController::class, 'index']);
    $router->get('/solicitacoes/{id}', [RequestController::class, 'show']);
    $router->post('/solicitacoes/{id}/cancelar', [RequestController::class, 'cancel']);

    $router->get('/contratos', [ContractController::class, 'index']);
    $router->get('/contratos/{id}', [ContractController::class, 'show']);
    $router->post('/contratos/{id}/cancelar', [ContractController::class, 'cancel']);
    $router->post('/contratos/{id}/avaliar', [ContractController::class, 'review']);
    $router->post('/contratos/{id}/arquivos', [ContractController::class, 'attach']);

    $router->get('/mensagens', [MessageController::class, 'index']);
    $router->get('/mensagens/{id}', [MessageController::class, 'show']);
    $router->post('/mensagens/{id}', [MessageController::class, 'send']);

    $router->get('/favoritos', [FavoriteController::class, 'index']);

    $router->get('/perfil', [ProfileController::class, 'edit']);
    $router->post('/perfil', [ProfileController::class, 'update']);
    $router->post('/perfil/foto', [ProfileController::class, 'avatar']);
    $router->post('/perfil/acesso', [ProfileController::class, 'security']);
});
