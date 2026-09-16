<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\GoogleAuthService;

final class GoogleAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthService $google = new GoogleAuthService())
    {
    }

    public function redirect(Request $request): Response
    {
        if (!$this->google->enabled()) {
            $this->withError('Login com Google ainda não foi configurado.');

            return $this->redirect('/entrar');
        }

        $intent = (string) $request->query('intent', 'buyer');

        return Response::redirect($this->google->authorizationUrl($intent));
    }

    public function callback(Request $request): Response
    {
        $result = $this->google->handleCallback($request);
        if (!($result['ok'] ?? false)) {
            $this->withError($result['message'] ?? 'Não foi possível entrar com o Google.');

            return $this->redirect('/entrar');
        }

        $intended = Session::get('intended');
        Session::forget('intended');
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            return $this->redirect($intended);
        }

        $user = $this->user();
        if ($user && $user->isStaff()) {
            return $this->redirect('/admin');
        }

        return $this->redirect('/conta');
    }
}
