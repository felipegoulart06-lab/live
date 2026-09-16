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
        SqliteSchema::install(Database::pdo());
        self::ensureDemoData();
        \App\Services\SettingService::ensureDefaults();
        Session::start($config->get('session'));

        self::$instance = new self(new Router(), $config);

        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/admin.php';
        require BASE_PATH . '/routes/api.php';

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

    private static function ensureDemoData(): void
    {
        $count = (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return;
        }

        ob_start();
        require BASE_PATH . '/database/seed.php';
        require BASE_PATH . '/database/seed_media.php';
        require BASE_PATH . '/database/seed_service_detail.php';
        ob_end_clean();
    }

    public function run(): void
    {
        $request = Request::capture();

        $pipeline = [
            MaintenanceMiddleware::class,
            CsrfMiddleware::class,
        ];

        $response = (new Pipeline($pipeline))->handle($request, function (Request $request): Response {
            return $this->router->dispatch($request);
        });

        $response->send();
    }
}
