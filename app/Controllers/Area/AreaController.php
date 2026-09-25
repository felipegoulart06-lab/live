<?php

declare(strict_types=1);

namespace App\Controllers\Area;

use App\Core\Controller;
use App\Core\Response;

/** Pages inside the creator (/painel) and company (/empresa) areas. Role checks happen in the route middleware. */
abstract class AreaController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data, int $status = 200): Response
    {
        $user = $this->user();
        $data['area'] = $user->areaPrefix();

        return $this->view($view, $data, 'layouts/panel', $status);
    }

    protected function area(): string
    {
        return $this->user()->areaPrefix();
    }

    /** Column that ties a row to the signed-in user in requests/contracts/conversations. */
    protected function ownerColumn(): string
    {
        return $this->user()->isCreator() ? 'creator_id' : 'company_id';
    }

    /** The other side of the deal: company for creators, creator for companies. */
    protected function counterpartColumn(): string
    {
        return $this->user()->isCreator() ? 'company_id' : 'creator_id';
    }
}
