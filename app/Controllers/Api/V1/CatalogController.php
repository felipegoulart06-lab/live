<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;

final class CatalogController extends Controller
{
    public function categories(Request $request): Response
    {
        return $this->json([
            'data' => (new CategoryRepository())->menuTree(),
        ]);
    }

    public function services(Request $request): Response
    {
        return $this->json([
            'data' => (new ServiceRepository())->homeList('recent', 20),
        ]);
    }
}
