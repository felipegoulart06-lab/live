<?php

declare(strict_types=1);

use App\Controllers\Admin\AuditAdminController;
use App\Controllers\Admin\CategoryAdminController;
use App\Controllers\Admin\ContentAdminController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FinanceAdminController;
use App\Controllers\Admin\FreelancerController;
use App\Controllers\Admin\OrderAdminController;
use App\Controllers\Admin\PageAdminController;
use App\Controllers\Admin\ProjectAdminController;
use App\Controllers\Admin\ServiceAdminController;
use App\Controllers\Admin\SettingAdminController;
use App\Controllers\Admin\UserAdminController;
use App\Core\Application;

$router = Application::boot()->router();

$router->group('/admin', ['auth', 'admin'], function ($router): void {
    $router->get('/', [DashboardController::class, 'index']);

    $router->get('/usuarios', [UserAdminController::class, 'index'], ['permission:users.view']);
    $router->get('/usuarios/{id}/editar', [UserAdminController::class, 'edit'], ['permission:users.manage']);
    $router->post('/usuarios/{id}', [UserAdminController::class, 'update'], ['permission:users.manage']);

    $router->get('/profissionais', [FreelancerController::class, 'index'], ['permission:users.manage']);
    $router->get('/profissionais/novo', [FreelancerController::class, 'create'], ['permission:users.manage']);
    $router->post('/profissionais', [FreelancerController::class, 'store'], ['permission:users.manage']);
    $router->get('/profissionais/{id}/editar', [FreelancerController::class, 'edit'], ['permission:users.manage']);
    $router->post('/profissionais/{id}', [FreelancerController::class, 'update'], ['permission:users.manage']);
    $router->post('/profissionais/{id}/desativar', [FreelancerController::class, 'deactivate'], ['permission:users.manage']);

    $router->get('/servicos', [ServiceAdminController::class, 'index'], ['permission:services.moderate']);
    $router->post('/servicos/{id}/status', [ServiceAdminController::class, 'updateStatus'], ['permission:services.moderate']);
    $router->post('/servicos/{id}/destaque', [ServiceAdminController::class, 'toggleFeatured'], ['permission:services.moderate']);

    $router->get('/pedidos', [OrderAdminController::class, 'index'], ['permission:orders.view']);
    $router->get('/pedidos/{id}', [OrderAdminController::class, 'show'], ['permission:orders.view']);
    $router->post('/pedidos/{id}/status', [OrderAdminController::class, 'updateStatus'], ['permission:orders.view']);

    $router->get('/projetos', [ProjectAdminController::class, 'index'], ['permission:orders.view']);
    $router->post('/projetos/{id}/status', [ProjectAdminController::class, 'updateStatus'], ['permission:orders.view']);

    $router->get('/pagamentos', [FinanceAdminController::class, 'payments'], ['permission:finance.manage']);
    $router->get('/saques', [FinanceAdminController::class, 'withdrawals'], ['permission:finance.manage']);
    $router->post('/saques/{id}', [FinanceAdminController::class, 'reviewWithdrawal'], ['permission:finance.manage']);

    $router->get('/categorias', [CategoryAdminController::class, 'index'], ['permission:cms.manage']);
    $router->get('/categorias/nova', [CategoryAdminController::class, 'create'], ['permission:cms.manage']);
    $router->post('/categorias', [CategoryAdminController::class, 'store'], ['permission:cms.manage']);
    $router->get('/categorias/{id}/editar', [CategoryAdminController::class, 'edit'], ['permission:cms.manage']);
    $router->post('/categorias/{id}', [CategoryAdminController::class, 'update'], ['permission:cms.manage']);
    $router->post('/categorias/{id}/subcategorias', [CategoryAdminController::class, 'storeSub'], ['permission:cms.manage']);
    $router->post('/categorias/{id}/subcategorias/{sub}/excluir', [CategoryAdminController::class, 'deleteSub'], ['permission:cms.manage']);

    $router->get('/paginas', [PageAdminController::class, 'index'], ['permission:cms.manage']);
    $router->get('/paginas/nova', [PageAdminController::class, 'create'], ['permission:cms.manage']);
    $router->post('/paginas', [PageAdminController::class, 'store'], ['permission:cms.manage']);
    $router->get('/paginas/{id}/editar', [PageAdminController::class, 'edit'], ['permission:cms.manage']);
    $router->post('/paginas/{id}', [PageAdminController::class, 'update'], ['permission:cms.manage']);
    $router->post('/paginas/{id}/excluir', [PageAdminController::class, 'destroy'], ['permission:cms.manage']);

    $router->get('/banners', [ContentAdminController::class, 'banners'], ['permission:cms.manage']);
    $router->post('/banners', [ContentAdminController::class, 'saveBanner'], ['permission:cms.manage']);
    $router->post('/banners/{id}/excluir', [ContentAdminController::class, 'deleteBanner'], ['permission:cms.manage']);

    $router->get('/faqs', [ContentAdminController::class, 'faqs'], ['permission:cms.manage']);
    $router->post('/faqs', [ContentAdminController::class, 'saveFaq'], ['permission:cms.manage']);
    $router->post('/faqs/{id}/excluir', [ContentAdminController::class, 'deleteFaq'], ['permission:cms.manage']);

    $router->get('/depoimentos', [ContentAdminController::class, 'testimonials'], ['permission:cms.manage']);
    $router->post('/depoimentos', [ContentAdminController::class, 'saveTestimonial'], ['permission:cms.manage']);
    $router->post('/depoimentos/{id}/excluir', [ContentAdminController::class, 'deleteTestimonial'], ['permission:cms.manage']);

    $router->get('/configuracoes', [SettingAdminController::class, 'edit'], ['permission:settings.manage']);
    $router->post('/configuracoes', [SettingAdminController::class, 'update'], ['permission:settings.manage']);

    $router->get('/auditoria', [AuditAdminController::class, 'index'], ['permission:settings.manage']);
});
