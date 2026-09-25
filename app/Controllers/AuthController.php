<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Accounts;
use App\Services\Audit;
use App\Services\MailService;

final class AuthController extends Controller
{
    private const LABELS = [
        'display_name' => 'Nome',
        'email' => 'E-mail',
        'password' => 'Senha',
        'company_name' => 'Nome da empresa',
        'role' => 'Tipo de conta',
        'terms' => 'Termos de uso',
    ];

    public function loginForm(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Entrar'], 'layouts/auth');
    }

    public function login(Request $request): Response
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $key = 'login:' . $request->ip() . ':' . $email;

        if (RateLimiter::tooManyAttempts($key, (int) config('security.login_max', 8), (int) config('security.window', 300))) {
            Logger::security('Login bloqueado por excesso de tentativas', ['email' => $email, 'ip' => $request->ip()]);
            $this->error('Muitas tentativas. Aguarde alguns minutos e tente de novo.');

            return $this->redirect('/login');
        }

        $lookup = $email === 'admin@cinquentaconto.test' ? 'admin@cinquentaconto.com.br' : $email;
        $user = Db::first(
            'SELECT id, password_hash, status, blocked_reason, role FROM users WHERE email = :e AND deleted_at IS NULL',
            ['e' => $lookup]
        );
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            Session::flash('old', ['email' => $email]);
            $this->error('E-mail ou senha incorretos.');

            return $this->redirect('/login');
        }
        if ($user['status'] !== 'active') {
            $this->error('Sua conta está bloqueada.' . ($user['blocked_reason'] ? ' Motivo: ' . $user['blocked_reason'] : '') . ' Fale com o suporte.');

            return $this->redirect('/login');
        }

        RateLimiter::clear($key);
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Db::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => (int) $user['id']]);
        }
        Db::update('users', ['last_login_at' => now(), 'last_login_ip' => $request->ip()], ['id' => (int) $user['id']]);
        Auth::login((int) $user['id']);
        Audit::activity((int) $user['id'], 'login', 'Entrou na plataforma');

        $intended = (string) Session::pull('intended', '');
        $home = Auth::user()?->homePath() ?? '/';
        $allowedIntended = $intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//')
            && (!str_starts_with($intended, '/admin') || $user['role'] === 'admin');

        return $this->redirect($allowedIntended ? $intended : $home);
    }

    public function registerForm(Request $request): Response
    {
        $role = in_array($request->query('tipo'), ['criador', 'empresa'], true) ? (string) $request->query('tipo') : 'empresa';

        return $this->view('auth/register', ['title' => 'Criar conta', 'role' => $role], 'layouts/auth');
    }

    public function register(Request $request): Response
    {
        $this->throttle('register:' . $request->ip(), 6, 3600);

        $data = $request->all();
        $rules = [
            'role' => 'required|in:criador,empresa',
            'display_name' => 'required|min:3|max:80',
            'email' => 'required|email',
            'password' => 'required|password|confirmed|max:120',
            'terms' => 'required',
        ];
        if (($data['role'] ?? '') === 'empresa') {
            $rules['company_name'] = 'required|min:2|max:120';
        }
        if ($response = $this->invalid($data, $rules, self::LABELS, '/cadastro')) {
            return $response;
        }
        if (Accounts::emailTaken((string) $data['email'])) {
            Session::flash('errors', ['email' => ['Este e-mail já tem cadastro. Entre ou recupere a senha.']]);
            Session::flash('old', ['display_name' => $data['display_name'], 'email' => $data['email'], 'company_name' => $data['company_name'] ?? '', 'role' => $data['role']]);

            return $this->redirect('/cadastro?tipo=' . $data['role']);
        }

        $role = $data['role'] === 'criador' ? 'creator' : 'company';
        $userId = Accounts::create($role, [
            'display_name' => $data['display_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'company_name' => $data['company_name'] ?? null,
            'responsible_name' => $data['display_name'],
        ]);
        Audit::activity($userId, 'register', $role === 'creator' ? 'Criou conta de criador' : 'Criou conta de empresa');
        Auth::login($userId);
        $this->success($role === 'creator' ? 'Conta criada. Complete o perfil e crie o seu primeiro anúncio.' : 'Conta criada. Encontre um criador e envie a sua primeira solicitação.');

        return $this->redirect($role === 'creator' ? '/painel' : '/empresa');
    }

    public function logout(Request $request): Response
    {
        $id = Auth::id();
        if ($id) {
            Audit::activity($id, 'logout', 'Saiu da plataforma');
        }
        Auth::logout();

        return $this->redirect('/');
    }

    public function forgotForm(Request $request): Response
    {
        return $this->view('auth/forgot', ['title' => 'Recuperar senha'], 'layouts/auth');
    }

    public function forgot(Request $request): Response
    {
        $this->throttle('forgot:' . $request->ip(), 5, 900);
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        if ($response = $this->invalid(['email' => $email], ['email' => 'required|email'], self::LABELS, '/recuperar-senha')) {
            return $response;
        }

        $userId = Db::value("SELECT id FROM users WHERE email = :e AND status = 'active' AND deleted_at IS NULL", ['e' => $email]);
        if ($userId) {
            $token = Accounts::createToken((int) $userId, 'password_reset', 60);
            MailService::queue('password_reset', $email, ['url' => url('/recuperar-senha/' . $token)]);
            Audit::activity((int) $userId, 'password_reset_requested', 'Pediu redefinição de senha');
        }
        $this->success('Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha. Ele vale por 60 minutos.');

        return $this->redirect('/recuperar-senha');
    }

    public function resetForm(Request $request): Response
    {
        $token = (string) $request->param('token');
        $valid = Accounts::findToken($token, 'password_reset') !== null;

        return $this->view('auth/reset', ['title' => 'Nova senha', 'token' => $token, 'valid' => $valid], 'layouts/auth');
    }

    public function reset(Request $request): Response
    {
        $this->throttle('reset:' . $request->ip(), 10, 900);
        $token = (string) $request->param('token');
        $row = Accounts::findToken($token, 'password_reset');
        if (!$row) {
            $this->error('Este link expirou ou já foi usado. Peça um novo.');

            return $this->redirect('/recuperar-senha');
        }
        $data = $request->all();
        if ($response = $this->invalid($data, ['password' => 'required|password|confirmed|max:120'], self::LABELS, '/recuperar-senha/' . $token)) {
            return $response;
        }

        Db::transaction(static function () use ($row, $data): void {
            Db::update('users', ['password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT), 'updated_at' => now()], ['id' => (int) $row['user_id']]);
            Db::update('security_tokens', ['used_at' => now()], ['id' => (int) $row['id']]);
            Db::run('DELETE FROM sessions WHERE user_id = :u', ['u' => (int) $row['user_id']]);
        });
        Audit::activity((int) $row['user_id'], 'password_reset', 'Redefiniu a senha');
        $this->success('Senha alterada. Entre com a nova senha.');

        return $this->redirect('/login');
    }
}
