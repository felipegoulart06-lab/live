<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;

final class AccountController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->requireUser();
        (new UserRepository())->touchLastSeen($user->id);

        $view = $user->isSeller() ? 'seller/dashboard' : 'buyer/dashboard';

        return $this->view($view, [
            'title' => 'Painel',
            'user' => $user,
            'menuCategories' => (new CategoryRepository())->menuTree(),
        ], 'layouts/dashboard');
    }
}
