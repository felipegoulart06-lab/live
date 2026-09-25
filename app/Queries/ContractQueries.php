<?php

declare(strict_types=1);

namespace App\Queries;

use App\Core\Db;

final class ContractQueries
{
    /** Everything the contract page shows, for participants and for the admin. @return array<string, mixed> */
    public static function detail(array $contract): array
    {
        $id = (int) $contract['id'];
        $info = Db::first(
            'SELECT l.title AS listing_title, l.slug AS listing_slug, co.company_name, cop.display_name AS company_contact,
                    cp.display_name AS creator_name, cp.slug AS creator_slug, cp.avatar_path AS creator_avatar,
                    cv.uuid AS conversation_uuid, r.uuid AS request_uuid, r.code AS request_code
             FROM contracts c
             JOIN listings l ON l.id = c.listing_id
             JOIN companies co ON co.user_id = c.company_id
             JOIN profiles cop ON cop.user_id = c.company_id
             JOIN profiles cp ON cp.user_id = c.creator_id
             JOIN requests r ON r.id = c.request_id
             LEFT JOIN conversations cv ON cv.id = c.conversation_id
             WHERE c.id = :id',
            ['id' => $id]
        );

        return [
            'contract' => $contract + $info,
            'addons' => $contract['addons_json'] ? (json_decode((string) $contract['addons_json'], true) ?: []) : [],
            'events' => Db::all(
                'SELECT e.type, e.note, e.created_at, p.display_name AS actor_name, u.role AS actor_role
                 FROM contract_events e LEFT JOIN users u ON u.id = e.actor_id LEFT JOIN profiles p ON p.user_id = e.actor_id
                 WHERE e.contract_id = :id ORDER BY e.created_at, e.id',
                ['id' => $id]
            ),
            'payment' => Db::first('SELECT * FROM payments WHERE contract_id = :id ORDER BY id DESC LIMIT 1', ['id' => $id]),
            'attachments' => Db::all(
                "SELECT a.uuid, a.original_name, a.mime, a.size_bytes, a.created_at, p.display_name AS owner_name
                 FROM attachments a JOIN profiles p ON p.user_id = a.owner_id
                 WHERE a.context = 'contract' AND a.context_id = :id ORDER BY a.created_at DESC",
                ['id' => $id]
            ),
            'review' => Db::first('SELECT rating, comment, status, created_at FROM reviews WHERE contract_id = :id', ['id' => $id]),
        ];
    }
}
