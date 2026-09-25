<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Services\Deals;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        Deals::expireStale();
        [$since, $until, $period, $from, $to] = $this->window($request);
        $chart = in_array((string) $request->query('grafico', 'contratos'), ['contratos', 'anuncios', 'usuarios', 'faturamento'], true)
            ? (string) $request->query('grafico', 'contratos')
            : 'contratos';

        $totals = Db::first(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role IN ('creator', 'company') AND deleted_at IS NULL) AS users,
                (SELECT COUNT(*) FROM users WHERE role = 'creator' AND deleted_at IS NULL) AS creators,
                (SELECT COUNT(*) FROM users WHERE role = 'company' AND deleted_at IS NULL) AS companies,
                (SELECT COUNT(*) FROM users WHERE role IN ('creator', 'company') AND created_at >= :s1 AND created_at <= :u1) AS new_users,
                (SELECT COUNT(*) FROM listings WHERE status = 'active' AND deleted_at IS NULL) AS active_listings,
                (SELECT COUNT(*) FROM listings WHERE status = 'pending' AND deleted_at IS NULL) AS pending_listings,
                (SELECT COUNT(*) FROM requests WHERE created_at >= :s2 AND created_at <= :u2) AS requests,
                (SELECT COUNT(*) FROM contracts WHERE created_at >= :s3 AND created_at <= :u3) AS contracts,
                (SELECT COUNT(*) FROM contracts WHERE status IN ('confirmed', 'in_progress')) AS running_contracts,
                (SELECT COALESCE(SUM(amount_cents), 0) FROM payments WHERE status IN ('paid', 'awaiting_payout', 'paid_out') AND paid_at >= :s4 AND paid_at <= :u4) AS gmv,
                (SELECT COALESCE(SUM(net_cents), 0) FROM payments WHERE status = 'awaiting_payout') AS awaiting_payout,
                (SELECT COUNT(*) FROM payments WHERE status = 'pending') AS pending_payments,
                (SELECT COUNT(*) FROM reports WHERE status IN ('open', 'reviewing')) AS open_reports,
                (SELECT COUNT(*) FROM creators WHERE verification_requested_at IS NOT NULL AND is_verified = 0) AS verification_requests",
            [
                's1' => $since, 'u1' => $until, 's2' => $since, 'u2' => $until,
                's3' => $since, 'u3' => $until, 's4' => $since, 'u4' => $until,
            ]
        );

        $attention = (int) $totals['pending_listings']
            + (int) $totals['verification_requests']
            + (int) $totals['open_reports']
            + (int) $totals['pending_payments'];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("first day of -{$i} months"));
            $months[$key] = ['label' => $key, 'contratos' => 0, 'anuncios' => 0, 'usuarios' => 0, 'faturamento' => 0];
        }
        $startMonth = array_key_first($months) . '-01 00:00:00';
        foreach (Db::all('SELECT created_at, total_cents, status FROM contracts WHERE created_at >= :s', ['s' => $startMonth]) as $row) {
            $key = substr((string) $row['created_at'], 0, 7);
            if (!isset($months[$key])) {
                continue;
            }
            $months[$key]['contratos']++;
            if ($row['status'] !== 'cancelled') {
                $months[$key]['faturamento'] += (int) $row['total_cents'];
            }
        }
        foreach (Db::all('SELECT created_at FROM listings WHERE deleted_at IS NULL AND created_at >= :s', ['s' => $startMonth]) as $row) {
            $key = substr((string) $row['created_at'], 0, 7);
            if (isset($months[$key])) {
                $months[$key]['anuncios']++;
            }
        }
        foreach (Db::all("SELECT created_at FROM users WHERE role IN ('creator', 'company') AND deleted_at IS NULL AND created_at >= :s", ['s' => $startMonth]) as $row) {
            $key = substr((string) $row['created_at'], 0, 7);
            if (isset($months[$key])) {
                $months[$key]['usuarios']++;
            }
        }

        return $this->render('admin/dashboard', [
            'title' => 'Visão geral',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'chart' => $chart,
            'totals' => $totals,
            'attentionCount' => $attention,
            'months' => array_values($months),
            'pendingListings' => Db::all(
                "SELECT l.uuid, l.title, l.submitted_at, p.display_name FROM listings l JOIN profiles p ON p.user_id = l.creator_id
                 WHERE l.status = 'pending' AND l.deleted_at IS NULL ORDER BY l.submitted_at LIMIT 8"
            ),
            'recentReports' => Db::all(
                "SELECT uuid, target_type, reason, status, created_at FROM reports WHERE status IN ('open', 'reviewing') ORDER BY created_at DESC LIMIT 5"
            ),
            'recentActions' => Db::all(
                'SELECT a.description, a.created_at, p.display_name FROM admin_actions a LEFT JOIN profiles p ON p.user_id = a.admin_id ORDER BY a.created_at DESC, a.id DESC LIMIT 8'
            ),
        ]);
    }

    /** @return array{0:string,1:string,2:string,3:?string,4:?string} */
    private function window(Request $request): array
    {
        [$from, $to] = $this->period($request);
        if ($from || $to) {
            $since = ($from ?? '1970-01-01') . ' 00:00:00';
            $until = ($to ?? date('Y-m-d')) . ' 23:59:59';

            return [$since, $until, 'personalizado', $from, $to];
        }

        $period = in_array((string) $request->query('periodo', '30'), ['hoje', '7', '30'], true)
            ? (string) $request->query('periodo', '30')
            : '30';
        $until = date('Y-m-d H:i:s');
        $since = $period === 'hoje'
            ? date('Y-m-d 00:00:00')
            : date('Y-m-d H:i:s', strtotime('-' . $period . ' days'));

        return [$since, $until, $period, null, null];
    }
}
