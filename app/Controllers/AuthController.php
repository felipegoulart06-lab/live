<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth = new AuthService())
    {
    }

    public function showLogin(Request $request): Response
    {
        return $this->authView('pages/auth/login', [
            'title' => 'Entrar',
        ]);
    }

    public function login(Request $request): Response
    {
        $result = $this->auth->attempt($request->all(), $request);
        if (!($result['ok'] ?? false)) {
            $this->withErrors($result['errors'] ?? [], ['email' => $request->input('email')]);
            if (isset($result['message'])) {
                $this->withError($result['message']);
            }

            return $this->redirect('/entrar');
        }

        $intended = \App\Core\Session::get('intended');
        \App\Core\Session::forget('intended');
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            return $this->redirect($intended);
        }

        return $this->redirect('/conta');
    }

    public function showRegister(Request $request): Response
    {
        return $this->authView('pages/auth/register', [
            'title' => 'Criar conta',
            'intent' => $request->query('intent', 'buyer'),
        ]);
    }

    public function register(Request $request): Response
    {
        $result = $this->auth->register($request->all(), $request);
        if (!($result['ok'] ?? false)) {
            $this->withErrors($result['errors'] ?? [], $request->all());
            if (isset($result['message'])) {
                $this->withError($result['message']);
            }

            return $this->redirect('/criar-conta');
        }

        $this->withSuccess('Conta criada. Bem-vindo à ' . brand_name() . '.');

        return $this->redirect('/conta');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $this->withSuccess('Você saiu da conta.');

        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->authView('pages/auth/forgot', ['title' => 'Recuperar senha']);
    }

    public function forgot(Request $request): Response
    {
        $result = $this->auth->requestPasswordReset((string) $request->input('email', ''));
        $this->withSuccess($result['message']);

        return $this->redirect('/esqueci-senha');
    }

    public function showReset(Request $request): Response
    {
        return $this->authView('pages/auth/reset', [
            'title' => 'Redefinir senha',
            'token' => (string) $request->param('token'),
        ]);
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->param('token');
        $result = $this->auth->resetPassword($token, $request->all());
        if (!($result['ok'] ?? false)) {
            $this->withErrors($result['errors'] ?? []);
            if (isset($result['message'])) {
                $this->withError($result['message']);
            }

            return $this->redirect('/redefinir-senha/' . rawurlencode($token));
        }

        $this->withSuccess($result['message'] ?? 'Senha atualizada.');

        return $this->redirect('/entrar');
    }

    public function verifyEmail(Request $request): Response
    {
        $ok = $this->auth->verifyEmail((string) $request->param('token'));
        $this->withSuccess($ok ? 'E-mail confirmado.' : 'Link inválido ou expirado.');

        return $this->redirect(Auth::check() ? '/conta' : '/entrar');
    }

    /** @param array<string, mixed> $data */
    private function authView(string $view, array $data): Response
    {
        $data['menuCategories'] = (new CategoryRepository())->menuTree();

        return $this->view($view, $data, 'layouts/auth');
    }
}
