<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Every admin route also passes `auth` + `role:admin` middleware; master-only actions call Gate::master(). */
abstract class AdminController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data, int $status = 200): Response
    {
        return $this->view($view, $data, 'layouts/panel', $status);
    }

    /** @return array{0: ?string, 1: ?string} validated Y-m-d range from ?de= / ?ate= */
    protected function period(Request $request): array
    {
        $from = (string) $request->query('de', '');
        $to = (string) $request->query('ate', '');
        $valid = static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);

        return [$valid($from) ? $from : null, $valid($to) ? $to : null];
    }

    /** @return array<string, mixed> */
    protected function findByUuid(string $table, string $uuid, string $missing = 'Registro não encontrado.'): array
    {
        $row = Db::first("SELECT * FROM {$table} WHERE uuid = :u", ['u' => $uuid]);

        return $row ?? throw HttpException::notFound($missing);
    }

    protected function reason(Request $request, string $field = 'reason', int $min = 5): ?string
    {
        $reason = trim((string) $request->input($field, ''));

        return mb_strlen($reason) >= $min && mb_strlen($reason) <= 1000 ? $reason : null;
    }
}
