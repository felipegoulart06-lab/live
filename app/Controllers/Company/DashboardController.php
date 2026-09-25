<?php

declare(strict_types=1);

namespace App\Controllers\Company;

use App\Controllers\Area\AreaController;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Queries\ListingQueries;
use App\Services\Deals;

final class DashboardController extends AreaController
{
    public function index(Request $request): Response
    {
        Deals::expireStale();
        $uid = $this->user()->id;

        $stats = Db::first(
            "SELECT
                (SELECT COUNT(*) FROM requests WHERE company_id = :u1 AND status = 'pending') AS pending_requests,
                (SELECT COUNT(*) FROM contracts WHERE company_id = :u2 AND status = 'awaiting_payment') AS awaiting_payment,
                (SELECT COUNT(*) FROM contracts WHERE company_id = :u3 AND status IN ('confirmed', 'in_progress')) AS open_contracts,
                (SELECT COUNT(*) FROM contracts WHERE company_id = :u4 AND status = 'completed') AS completed,
                (SELECT COALESCE(SUM(amount_cents), 0) FROM payments WHERE company_id = :u5 AND status IN ('paid', 'awaiting_payout', 'paid_out')) AS invested,
                (SELECT COALESCE(SUM(hours), 0) FROM contracts WHERE company_id = :u6 AND status = 'completed') AS hours",
            ['u1' => $uid, 'u2' => $uid, 'u3' => $uid, 'u4' => $uid, 'u5' => $uid, 'u6' => $uid]
        );

        return $this->render('company/dashboard', [
            'title' => 'Painel da empresa',
            'stats' => $stats,
            'toReview' => Db::all(
                "SELECT c.uuid, c.code, cp.display_name AS creator_name, l.title AS listing_title
                 FROM contracts c JOIN profiles cp ON cp.user_id = c.creator_id JOIN listings l ON l.id = c.listing_id
                 WHERE c.company_id = :u AND c.status = 'completed' AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.contract_id = c.id)
                 ORDER BY c.completed_at DESC LIMIT 5",
                ['u' => $uid]
            ),
            'recentRequests' => Db::all(
                'SELECT r.uuid, r.code, r.hours, r.total_cents, r.status, r.created_at, cp.display_name AS creator_name, l.title AS listing_title
                 FROM requests r JOIN profiles cp ON cp.user_id = r.creator_id JOIN listings l ON l.id = r.listing_id
                 WHERE r.company_id = :u ORDER BY r.created_at DESC LIMIT 5',
                ['u' => $uid]
            ),
            'openContracts' => Db::all(
                "SELECT c.uuid, c.code, c.status, c.hours, c.total_cents, c.scheduled_date, cp.display_name AS creator_name
                 FROM contracts c JOIN profiles cp ON cp.user_id = c.creator_id
                 WHERE c.company_id = :u AND c.status IN ('awaiting_payment', 'confirmed', 'in_progress')
                 ORDER BY c.created_at DESC LIMIT 5",
                ['u' => $uid]
            ),
            'suggestions' => ListingQueries::cards('l.rating_avg DESC, l.contracts_count DESC', 4),
        ]);
    }
}
