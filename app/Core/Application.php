<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\CsrfMiddleware;
use App\Middleware\MaintenanceMiddleware;

final class Application
{
    private static ?self $instance = null;

    private function __construct(
        private readonly Router $router,
        private readonly Config $config
    ) {
    }

    public static function boot(): self
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        Env::load(BASE_PATH . '/.env');
        date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Sao_Paulo'));

        $config = Config::load();
        Database::connect($config->get('database') ?? []);
        Schema::install();
        self::ensureDemoData();
        \App\Services\OperatorAccounts::ensure();

        self::$instance = new self(new Router(), $config);
        Session::start($config->get('session') ?? []);

        $router = self::$instance->router;
        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/area.php';
        require BASE_PATH . '/routes/admin.php';

        return self::$instance;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(): Config
    {
        return $this->config;
    }

    /** A fresh local SQLite file gets the demo data; Postgres is seeded explicitly with `database/seed.php`. */
    private static function ensureDemoData(): void
    {
        if (Database::isPostgres() || (int) Db::value('SELECT COUNT(*) FROM users') > 0) {
            return;
        }
        (new \App\Seed\DemoSeeder())->run();
    }

    public function run(): void
    {
        $request = Request::capture();

        try {
            $response = (new Pipeline([MaintenanceMiddleware::class, CsrfMiddleware::class]))
                ->handle($request, fn (Request $request): Response => $this->router->dispatch($request));
        } catch (UploadException $e) {
            $response = self::backWithError($request, $e->getMessage());
        } catch (HttpException $e) {
            // 422 = business rule the user can fix (e.g. "complete o checklist"): show it on the page they came from.
            $response = $e->status === 422 && !$request->wantsJson() && !$request->isAjax()
                ? self::backWithError($request, $e->getMessage())
                : self::errorResponse($request, $e->status, $e->getMessage());
        }

        $response->withSecurityHeaders()->send();
    }

    private static function backWithError(Request $request, string $message): Response
    {
        if ($request->wantsJson() || $request->isAjax()) {
            return Response::json(['message' => $message], 422);
        }
        Session::flash('error', $message);
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $sameHost = $referer !== '' && parse_url($referer, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST);

        return Response::redirect($sameHost ? $referer : url('/'));
    }

    public static function errorResponse(Request $request, int $status, string $message): Response
    {
        if ($request->wantsJson() || $request->isAjax()) {
            return Response::json(['message' => $message], $status);
        }
        $titles = [403 => 'Acesso negado', 404 => 'Página não encontrada', 429 => 'Muitas tentativas', 500 => 'Algo deu errado'];

        return Response::view('errors/error', [
            'title' => $titles[$status] ?? 'Erro',
            'status' => $status,
            'message' => $message,
        ], 'layouts/public', $status);
    }
}
