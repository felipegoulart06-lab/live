<?php

declare(strict_types=1);

namespace App\Controllers\Creator;

use App\Controllers\Area\AreaController;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Services\Deals;

final class DashboardController extends AreaController
{
    public function index(Request $request): Response
    {
        Deals::expireStale();
        $uid = $this->user()->id;

        $listings = Db::first(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                    COALESCE(SUM(views_count), 0) AS views,
                    COALESCE(SUM(contacts_count), 0) AS contacts
             FROM listings WHERE creator_id = :u AND deleted_at IS NULL",
            ['u' => $uid]
        );
        $money = Db::first(
            "SELECT COALESCE(SUM(CASE WHEN status IN ('awaiting_payout', 'paid_out') THEN net_cents ELSE 0 END), 0) AS earned,
                    COALESCE(SUM(CASE WHEN status = 'awaiting_payout' THEN net_cents ELSE 0 END), 0) AS to_receive,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN net_cents ELSE 0 END), 0) AS in_progress
             FROM payments WHERE creator_id = :u",
            ['u' => $uid]
        );
        $creator = Db::first('SELECT rating_avg, rating_count, contracts_count, hours_sold, is_verified FROM creators WHERE user_id = :u', ['u' => $uid]);
        $profile = Db::first('SELECT avatar_path, bio, headline, city FROM profiles WHERE user_id = :u', ['u' => $uid]);

        $stats = [
            'pending_requests' => (int) Db::value("SELECT COUNT(*) FROM requests WHERE creator_id = :u AND status = 'pending'", ['u' => $uid]),
            'open_contracts' => (int) Db::value("SELECT COUNT(*) FROM contracts WHERE creator_id = :u AND status IN ('awaiting_payment', 'confirmed', 'in_progress')", ['u' => $uid]),
            'total_requests' => (int) Db::value('SELECT COUNT(*) FROM requests WHERE creator_id = :u', ['u' => $uid]),
            'accepted_requests' => (int) Db::value("SELECT COUNT(*) FROM requests WHERE creator_id = :u AND status = 'accepted'", ['u' => $uid]),
        ];

        $perfMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("first day of -{$i} months"));
            $perfMonths[$key] = 0;
        }
        foreach (Db::all('SELECT created_at FROM contracts WHERE creator_id = :u AND status <> :x AND created_at >= :s', [
            'u' => $uid,
            'x' => 'cancelled',
            's' => array_key_first($perfMonths) . '-01 00:00:00',
        ]) as $row) {
            $key = substr((string) $row['created_at'], 0, 7);
            if (isset($perfMonths[$key])) {
                $perfMonths[$key]++;
            }
        }

        return $this->render('creator/dashboard', [
            'title' => 'Painel do criador',
            'perfMonths' => $perfMonths,
            'listings' => $listings,
            'money' => $money,
            'creator' => $creator,
            'stats' => $stats,
            'onboarding' => [
                ['done' => !empty($profile['avatar_path']), 'label' => 'Adicionar foto de perfil', 'href' => '/painel/perfil'],
                ['done' => mb_strlen((string) ($profile['bio'] ?? '')) >= 40 && !empty($profile['headline']), 'label' => 'Escrever título e biografia', 'href' => '/painel/perfil'],
                ['done' => (bool) Db::value('SELECT 1 FROM availability WHERE creator_id = :u', ['u' => $uid]), 'label' => 'Informar disponibilidade semanal', 'href' => '/painel/perfil#disponibilidade'],
                ['done' => (int) $listings['active'] + (int) $listings['pending'] > 0, 'label' => 'Enviar o primeiro anúncio para revisão', 'href' => '/painel/anuncios/novo'],
            ],
            'recentRequests' => Db::all(
                "SELECT r.uuid, r.code, r.hours, r.total_cents, r.status, r.created_at, r.expires_at, co.company_name, l.title AS listing_title
                 FROM requests r JOIN companies co ON co.user_id = r.company_id JOIN listings l ON l.id = r.listing_id
                 WHERE r.creator_id = :u ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC LIMIT 5",
                ['u' => $uid]
            ),
            'upcoming' => Db::all(
                "SELECT c.uuid, c.code, c.hours, c.status, c.scheduled_date, c.scheduled_time, co.company_name
                 FROM contracts c JOIN companies co ON co.user_id = c.company_id
                 WHERE c.creator_id = :u AND c.status IN ('awaiting_payment', 'confirmed', 'in_progress')
                 ORDER BY CASE WHEN c.scheduled_date IS NULL THEN 1 ELSE 0 END, c.scheduled_date, c.id LIMIT 5",
                ['u' => $uid]
            ),
            'rejectedListings' => Db::all(
                "SELECT uuid, title, rejection_reason FROM listings WHERE creator_id = :u AND status = 'rejected' AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT 3",
                ['u' => $uid]
            ),
        ]);
    }
}
