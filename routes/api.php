<?php

declare(strict_types=1);

use App\Controllers\Api\V1\CatalogController;
use App\Core\Application;

$router = Application::boot()->router();

$router->group('/api/v1', [], function ($router): void {
    $router->get('/categories', [CatalogController::class, 'categories']);
    $router->get('/services', [CatalogController::class, 'services']);
});
